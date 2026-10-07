/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import {
	dangerouslyGetExperimentAssignment,
	loadExperimentAssignment,
} from '@woocommerce/explat';
import type { TaskListType, TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TaskLists } from '../task-lists';
import {
	ASSIGNMENT_TIMEOUT_MS,
	MARKETPLACE_TASK_EXPERIMENT_NAME,
} from '../marketplace-task-experiment';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn().mockReturnValue( {} ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/explat', () => ( {
	dangerouslyGetExperimentAssignment: jest.fn(),
	loadExperimentAssignment: jest.fn(),
} ) );
jest.mock( '@woocommerce/tracks' );

jest.mock( '../components/task-list', () => ( {
	TaskList: ( { tasks }: TaskListType ) => (
		<ol>
			{ tasks.map( ( task ) => (
				<li key={ task.id }>{ task.title }</li>
			) ) }
		</ol>
	),
} ) );

jest.mock( '../components/task', () => ( {
	Task: () => <div>task-screen</div>,
} ) );

jest.mock( '../setup-task-list', () => ( {} ) );

jest.mock( '../components/placeholder', () => ( {
	TasksPlaceholder: () => <div>task-placeholder</div>,
} ) );

const CONTROL_TITLE = 'Enhance your store with extensions';
const NOW = Date.UTC( 2026, 10, 1 );
const HOME_ORDER = [ 'Grow your business', CONTROL_TITLE ];
const FIRST_ORDER = [ CONTROL_TITLE, 'Grow your business' ];

const makeTaskLists = (
	taskOverrides: Partial< TaskType > = {},
	listOverrides: Partial< TaskListType > = {}
) => [
	{
		id: 'extended',
		isVisible: true,
		tasks: [
			{ id: 'marketing', title: 'Grow your business' },
			{ id: 'extend-store', title: CONTROL_TITLE, ...taskOverrides },
		],
		...listOverrides,
	},
];

const mockTaskLists = ( taskLists: unknown[] ) =>
	( useSelect as jest.Mock ).mockReturnValue( {
		isResolving: false,
		taskLists,
	} );

const assignment = (
	variationName: string | null,
	overrides: Record< string, unknown > = {}
) => ( {
	experimentName: MARKETPLACE_TASK_EXPERIMENT_NAME,
	variationName,
	retrievedTimestamp: NOW,
	ttl: 60,
	...overrides,
} );

const mockAssignment = ( variationName: string | null ) =>
	( loadExperimentAssignment as jest.Mock ).mockResolvedValue(
		assignment( variationName )
	);

const mockCache = ( cached: ReturnType< typeof assignment > ) =>
	( dangerouslyGetExperimentAssignment as jest.Mock ).mockReturnValue(
		cached
	);

const titles = () =>
	screen.queryAllByRole( 'listitem' ).map( ( item ) => item.textContent );

describe( 'Marketplace task experiment', () => {
	const originalWcTracks = window.wcTracks;

	beforeEach( () => {
		jest.clearAllMocks();
		jest.spyOn( Date, 'now' ).mockReturnValue( NOW );
		window.wcTracks = { isEnabled: true };
		mockTaskLists( makeTaskLists() );
		// What the ExPlat client returns when nothing is stored.
		mockCache(
			assignment( null, { isFallbackExperimentAssignment: true } )
		);
	} );

	afterEach( () => {
		window.wcTracks = originalWcTracks;
		jest.restoreAllMocks();
		jest.useRealTimers();
	} );

	it.each( [
		[ 'control', HOME_ORDER ],
		[
			'copy_payments_shipping_marketing',
			[
				'Grow your business',
				'Add payments, shipping and marketing extensions',
			],
		],
		[
			'copy_free_and_paid',
			[ 'Grow your business', 'Browse free and paid extensions' ],
		],
		[ 'first_position', FIRST_ORDER ],
		[ 'unknown_variation', HOME_ORDER ],
		[ null, HOME_ORDER ],
	] )(
		'shows the placeholder, then the %s list',
		async ( variationName, expectedTitles ) => {
			mockAssignment( variationName );
			render( <TaskLists query={ {} } /> );

			expect(
				screen.getByText( 'task-placeholder' )
			).toBeInTheDocument();
			await screen.findAllByRole( 'listitem' );
			expect( titles() ).toEqual( expectedTitles );
			expect( loadExperimentAssignment ).toHaveBeenCalledWith(
				MARKETPLACE_TASK_EXPERIMENT_NAME
			);
		}
	);

	it.each( [
		[
			'tracking is off',
			() => ( window.wcTracks.isEnabled = false ),
			HOME_ORDER,
		],
		[
			'the experiment has ended',
			() =>
				jest
					.spyOn( Date, 'now' )
					.mockReturnValue( Date.UTC( 2027, 2, 1 ) ),
			HOME_ORDER,
		],
		[
			'the task is complete',
			() => mockTaskLists( makeTaskLists( { isComplete: true } ) ),
			HOME_ORDER,
		],
		[
			'the task is dismissed',
			() => mockTaskLists( makeTaskLists( { isDismissed: true } ) ),
			HOME_ORDER,
		],
		[
			'the task is not in the list',
			() =>
				mockTaskLists( [
					{
						...makeTaskLists()[ 0 ],
						tasks: [
							{ id: 'marketing', title: 'Grow your business' },
						],
					},
				] ),
			[ 'Grow your business' ],
		],
		[
			'the list is not visible',
			() => mockTaskLists( makeTaskLists( {}, { isVisible: false } ) ),
			[],
		],
	] )(
		'renders control without a request when %s',
		( _, arrange, expectedTitles ) => {
			arrange();
			mockAssignment( 'first_position' );
			mockCache( assignment( 'first_position' ) );
			render( <TaskLists query={ {} } /> );

			expect( titles() ).toEqual( expectedTitles );
			expect( loadExperimentAssignment ).not.toHaveBeenCalled();
			expect( dangerouslyGetExperimentAssignment ).not.toHaveBeenCalled();
		}
	);

	it( 'does not request an assignment on a task screen', () => {
		mockAssignment( 'first_position' );
		render( <TaskLists query={ { task: 'extend-store' } } /> );

		expect( screen.getByText( 'task-screen' ) ).toBeInTheDocument();
		expect( loadExperimentAssignment ).not.toHaveBeenCalled();
	} );

	it.each( [
		[ 'a live stored assignment without a request', {}, FIRST_ORDER, 0 ],
		[ 'a fresh request for an expired one', { ttl: -1 }, HOME_ORDER, 1 ],
	] )(
		'uses %s',
		async ( _, cacheOverrides, expectedTitles, requestCount ) => {
			mockCache( assignment( 'first_position', cacheOverrides ) );
			mockAssignment( 'control' );
			render( <TaskLists query={ {} } /> );

			await screen.findAllByRole( 'listitem' );
			expect( titles() ).toEqual( expectedTitles );
			expect( loadExperimentAssignment ).toHaveBeenCalledTimes(
				requestCount
			);
		}
	);

	it( 'keeps the variation for the mount after the task is dismissed', async () => {
		mockAssignment( 'first_position' );
		const { rerender } = render( <TaskLists query={ {} } /> );
		await screen.findAllByRole( 'listitem' );

		mockTaskLists( makeTaskLists( { isDismissed: true } ) );
		rerender( <TaskLists query={ {} } /> );

		expect( titles() ).toEqual( FIRST_ORDER );
		expect( loadExperimentAssignment ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'falls back to control when the assignment is slow, and ignores a late answer', async () => {
		jest.useFakeTimers( { now: NOW } );
		let resolveLate: ( value: unknown ) => void = () => {};
		( loadExperimentAssignment as jest.Mock ).mockReturnValue(
			new Promise( ( resolve ) => ( resolveLate = resolve ) )
		);
		render( <TaskLists query={ {} } /> );

		expect( screen.getByText( 'task-placeholder' ) ).toBeInTheDocument();
		await act( async () => {
			jest.advanceTimersByTime( ASSIGNMENT_TIMEOUT_MS );
		} );
		expect( titles() ).toEqual( HOME_ORDER );

		await act( async () => {
			resolveLate( assignment( 'first_position' ) );
		} );
		expect( titles() ).toEqual( HOME_ORDER );
	} );
} );
