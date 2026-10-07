/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import { dispatch, select } from '@wordpress/data';
import { onboardingStore } from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ConnectedLayout from '../layout';
import { getAdminSetting } from '~/utils/admin-settings';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn(),
	getNewPath: jest.fn().mockReturnValue( 'home-path' ),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	...jest.requireActual( '~/utils/admin-settings' ),
	getAdminSetting: jest.fn(),
} ) );

jest.mock( '../stats-overview', () =>
	jest.fn().mockReturnValue( <div>[StatsOverview]</div> )
);

jest.mock( '../../inbox-panel', () =>
	jest.fn().mockReturnValue( <div>[InboxPanel]</div> )
);

jest.mock( '../../store-management-links', () => ( {
	StoreManagementLinks: jest
		.fn()
		.mockReturnValue( <div>[StoreManagementLinks]</div> ),
} ) );

jest.mock( '../activity-panel', () => ( {
	ActivityPanel: jest.fn().mockReturnValue( <div>[ActivityPanel]</div> ),
} ) );

jest.mock( '@wordpress/element', () => {
	return {
		...jest.requireActual( '@wordpress/element' ),
		Suspense: ( { children } ) => <div>{ children }</div>,
		lazy: () => () => <div>[TaskList]</div>,
	};
} );

const TASK_LISTS = [
	{
		id: 'setup',
		isVisible: true,
		tasks: [ { id: 'products' }, { id: 'payments' } ],
	},
	{ id: 'extended', isVisible: true, tasks: [] },
];

// What /onboarding/tasks returns when every list is hidden: the lists are
// still present, but their tasks are stripped server-side.
const HIDDEN_TASK_LISTS = [
	{ id: 'setup', isVisible: false, tasks: [] },
	{ id: 'extended', isVisible: false, tasks: [] },
];

// The onboarding resolvers reach the network through their own copy of
// @wordpress/api-fetch, so mock the fetch layer underneath instead of the module.
global.fetch = jest.fn();

const jsonResponse = ( data ) => ( {
	status: 200,
	ok: true,
	headers: {
		get: ( name ) =>
			name.toLowerCase() === 'content-type' ? 'application/json' : null,
	},
	json: () => Promise.resolve( data ),
	text: () => Promise.resolve( JSON.stringify( data ) ),
} );

// Answers every request the connected Layout triggers; `tasks` is the
// /onboarding/tasks response — pass 'fail' to simulate a network failure.
const mockRequests = ( tasks ) => {
	global.fetch.mockImplementation( ( url = '' ) => {
		if ( url.includes( '/onboarding/tasks' ) ) {
			return tasks === 'fail'
				? Promise.reject( new TypeError( 'Failed to fetch' ) )
				: Promise.resolve( jsonResponse( tasks ) );
		}
		if ( url.includes( 'users/me' ) ) {
			return Promise.resolve(
				jsonResponse( { capabilities: { manage_woocommerce: true } } )
			);
		}
		return Promise.resolve( jsonResponse( {} ) );
	} );
};

const waitForTaskListsResolution = async () => {
	await waitFor( () =>
		expect(
			select( onboardingStore ).hasFinishedResolution( 'getTaskLists' )
		).toBe( true )
	);
};

describe( 'Homescreen Layout stale task redirect (connected)', () => {
	const historyReplace = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		getHistory.mockReturnValue( {
			replace: historyReplace,
		} );
		getNewPath.mockReturnValue( 'home-path' );
		getAdminSetting.mockImplementation( ( name, fallback ) =>
			name === 'visibleTaskListIds' ? [ 'setup' ] : fallback
		);
		dispatch( onboardingStore ).invalidateResolution( 'getTaskLists', [] );
	} );

	// This test runs first on purpose: it needs the store in its cold state,
	// before any successful fetch has populated the task lists.
	it( 'does not redirect and keeps the task URL when the fetch fails', async () => {
		mockRequests( 'fail' );
		render( <ConnectedLayout query={ { task: 'payments' } } /> );

		await waitForTaskListsResolution();
		expect( historyReplace ).not.toHaveBeenCalled();
	} );

	it( 'redirects home when the task matches no fetched task', async () => {
		mockRequests( TASK_LISTS );
		render( <ConnectedLayout query={ { task: 'shipping' } } /> );

		await waitFor( () =>
			expect( historyReplace ).toHaveBeenCalledWith( 'home-path' )
		);
		expect( getNewPath ).toHaveBeenCalledWith( {}, '/', {} );
	} );

	it( 'does not redirect when the task exists', async () => {
		mockRequests( TASK_LISTS );
		render( <ConnectedLayout query={ { task: 'payments' } } /> );

		await waitForTaskListsResolution();
		expect( historyReplace ).not.toHaveBeenCalled();
	} );

	it( 'redirects home for a stale task when no task list is visible', async () => {
		getAdminSetting.mockImplementation( ( name, fallback ) =>
			name === 'visibleTaskListIds' ? [] : fallback
		);
		mockRequests( HIDDEN_TASK_LISTS );
		render( <ConnectedLayout query={ { task: 'payments' } } /> );

		await waitFor( () =>
			expect( historyReplace ).toHaveBeenCalledWith( 'home-path' )
		);
	} );
} );
