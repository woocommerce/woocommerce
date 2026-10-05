import {
	afterEach,
	beforeAll,
	beforeEach,
	describe,
	expect,
	it,
	vi,
} from 'vitest';

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { queueRecordEvent, recordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line import/no-unresolved -- Provided by WordPress in the wp-admin runtime.
import { store as commandsStore } from '@wordpress/commands';
import { dispatch, useSelect } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { box, plus } from '@wordpress/icons';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { registerCommandWithTracking } from '../register-command-with-tracking';
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		queueRecordEvent: vi.fn(),
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/commands', () => {
	const mock = {
		store: 'commands-store',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/core-data', () => {
	const mock = {
		store: 'core-store',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', () => {
	const mock = {
		dispatch: vi.fn(),
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/dom-ready', () => {
	const mock = vi.fn();
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '@wordpress/html-entities', () => {
	const mock = {
		decodeEntities: ( value ) => value.replace( '&amp;', '&' ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/i18n', () => {
	const mock = {
		__: ( value ) => value,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/icons', () => {
	const mock = {
		box: 'box-icon',
		plus: 'plus-icon',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/url', () => {
	const mock = {
		addQueryArgs: vi.fn(
			( base, args ) => `#${ base }-${ JSON.stringify( args ) }`
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'registerCommandWithTracking', () => {
	it( 'forwards callback arguments exactly once', () => {
		vi.clearAllMocks();
		const registerCommand = vi.fn();
		dispatch.mockReturnValue( {
			registerCommand,
		} );
		const callback = vi.fn();
		const firstArgument = {
			sentinel: 'first',
		};
		const secondArgument = {
			sentinel: 'second',
		};
		registerCommandWithTracking( {
			name: 'woocommerce/test-command',
			label: 'Test command',
			icon: 'test-icon',
			callback,
		} );
		const registeredCallback =
			registerCommand.mock.calls[ 0 ][ 0 ].callback;
		registeredCallback( firstArgument, secondArgument );
		expect( callback ).toHaveBeenCalledTimes( 1 );
		expect( callback ).toHaveBeenCalledWith(
			firstArgument,
			secondArgument
		);
	} );
} );
describe( 'Command Palette', () => {
	let registeredCommands;
	let registeredLoader;
	let startEntry;
	const runEntry = () => {
		const commandDispatcher = {
			registerCommand: ( command ) => registeredCommands.push( command ),
			registerCommandLoader: ( loader ) => {
				registeredLoader = loader;
			},
		};
		dispatch.mockReturnValue( commandDispatcher );
		startEntry();
	};
	beforeAll( async () => {
		// eslint-disable-next-line @typescript-eslint/no-require-imports -- Load only after the entry-point mocks are installed.
		await import( '../index' );
		startEntry = domReady.mock.calls[ 0 ][ 0 ];
	} );
	beforeEach( () => {
		vi.clearAllMocks();
		registeredCommands = [];
		registeredLoader = undefined;
		runEntry();
	} );
	afterEach( () => {
		vi.useRealTimers();
	} );
	it( 'registers static commands and product loader with exact behavior', () => {
		expect( dispatch ).toHaveBeenLastCalledWith( commandsStore );
		expect(
			registeredCommands.map( ( command ) => ( {
				name: command.name,
				label: command.label,
				icon: command.icon,
			} ) )
		).toEqual( [
			{
				name: 'woocommerce/add-new-product',
				label: 'Add new product',
				icon: plus,
			},
			{
				name: 'woocommerce/add-new-order',
				label: 'Add new order',
				icon: plus,
			},
			{
				name: 'woocommerce/view-products',
				label: 'Products',
				icon: box,
			},
			{
				name: 'woocommerce/view-orders',
				label: 'Orders',
				icon: box,
			},
		] );
		expect( registeredLoader.name ).toBe( 'woocommerce/product' );
		const destinations = [
			[
				'post-new.php',
				{
					post_type: 'product',
				},
			],
			[
				'admin.php',
				{
					page: 'wc-orders',
					action: 'new',
				},
			],
			[
				'edit.php',
				{
					post_type: 'product',
				},
			],
			[
				'admin.php',
				{
					page: 'wc-orders',
				},
			],
		];
		registeredCommands.forEach( ( command, index ) => {
			command.callback();
			expect( decodeURIComponent( window.location.hash ) ).toBe(
				addQueryArgs.mock.results[ index ].value
			);
		} );
		expect( addQueryArgs.mock.calls ).toEqual( destinations );
		expect( queueRecordEvent.mock.calls ).toEqual(
			registeredCommands.map( ( command ) => [
				'woocommerce_command_palette_submit',
				{
					name: command.name,
					origin: undefined,
				},
			] )
		);
	} );
	it( 'loads products, tracks searches, and navigates through product commands', () => {
		vi.useFakeTimers();
		const state = {
			records: undefined,
			isLoading: true,
		};
		const getEntityRecords = vi.fn( () => state.records );
		const hasFinishedResolution = vi.fn( () => ! state.isLoading );
		useSelect.mockImplementation( ( callback ) =>
			callback( () => ( {
				getEntityRecords,
				hasFinishedResolution,
			} ) )
		);
		const { result, rerender, unmount } = renderHook(
			( { search } ) =>
				registeredLoader.hook( {
					search,
				} ),
			{
				initialProps: {
					search: '',
				},
			}
		);
		expect( getEntityRecords ).toHaveBeenLastCalledWith(
			'postType',
			'product',
			{
				search: undefined,
				per_page: 10,
				orderby: 'date',
				status: [ 'publish', 'future', 'draft', 'pending', 'private' ],
			}
		);
		expect( result.current ).toEqual( {
			commands: [],
			isLoading: true,
		} );
		state.records = [
			{
				id: 12,
				title: {
					rendered: 'Bread &amp; Butter',
				},
			},
			{
				id: 13,
				title: {},
			},
		];
		state.isLoading = false;
		rerender( {
			search: 'bread',
		} );
		expect( getEntityRecords ).toHaveBeenLastCalledWith(
			'postType',
			'product',
			{
				search: 'bread',
				per_page: 10,
				orderby: 'relevance',
				status: [ 'publish', 'future', 'draft', 'pending', 'private' ],
			}
		);
		expect( result.current ).toMatchObject( {
			isLoading: false,
			commands: [
				{
					name: 'product-12',
					searchLabel: 'Bread &amp; Butter 12',
					label: 'Bread & Butter',
					icon: box,
				},
				{
					name: 'product-13',
					searchLabel: 'undefined 13',
					label: '(no title)',
					icon: box,
				},
			],
		} );
		const close = vi.fn();
		result.current.commands[ 0 ].callback( {
			close,
		} );
		expect( addQueryArgs ).toHaveBeenLastCalledWith( 'post.php', {
			post: 12,
			action: 'edit',
		} );
		expect( decodeURIComponent( window.location.hash ) ).toBe(
			addQueryArgs.mock.results.at( -1 ).value
		);
		expect( close ).toHaveBeenCalledTimes( 1 );
		expect( queueRecordEvent ).toHaveBeenLastCalledWith(
			'woocommerce_command_palette_submit',
			{
				name: 'woocommerce/product',
			}
		);
		vi.advanceTimersByTime( 300 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'woocommerce_command_palette_search',
			{
				value: 'bread',
			}
		);
		rerender( {
			search: 'butter',
		} );
		expect( vi.getTimerCount() ).toBe( 1 );
		unmount();
		expect( vi.getTimerCount() ).toBe( 0 );
	} );
	it( 'does not track a product search after unmount', () => {
		vi.useFakeTimers();
		const getEntityRecords = vi.fn( () => [] );
		const hasFinishedResolution = vi.fn( () => true );
		useSelect.mockImplementation( ( callback ) =>
			callback( () => ( {
				getEntityRecords,
				hasFinishedResolution,
			} ) )
		);
		const { unmount } = renderHook( () =>
			registeredLoader.hook( {
				search: 'bread',
			} )
		);
		unmount();
		vi.advanceTimersByTime( 300 );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
