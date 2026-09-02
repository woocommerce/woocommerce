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

jest.mock( '@woocommerce/tracks', () => ( {
	queueRecordEvent: jest.fn(),
	recordEvent: jest.fn(),
} ) );
jest.mock( '@wordpress/commands', () => ( { store: 'commands-store' } ) );
jest.mock( '@wordpress/core-data', () => ( { store: 'core-store' } ) );
jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );
jest.mock( '@wordpress/dom-ready', () => jest.fn() );
jest.mock( '@wordpress/html-entities', () => ( {
	decodeEntities: ( value ) => value.replace( '&amp;', '&' ),
} ) );
jest.mock( '@wordpress/i18n', () => ( { __: ( value ) => value } ) );
jest.mock( '@wordpress/icons', () => ( {
	box: 'box-icon',
	plus: 'plus-icon',
} ) );
jest.mock( '@wordpress/url', () => ( {
	addQueryArgs: jest.fn(
		( base, args ) => `#${ base }-${ JSON.stringify( args ) }`
	),
} ) );

describe( 'registerCommandWithTracking', () => {
	it( 'forwards callback arguments exactly once', () => {
		jest.clearAllMocks();
		const registerCommand = jest.fn();
		dispatch.mockReturnValue( { registerCommand } );
		const callback = jest.fn();
		const firstArgument = { sentinel: 'first' };
		const secondArgument = { sentinel: 'second' };

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

	beforeAll( () => {
		// eslint-disable-next-line @typescript-eslint/no-require-imports -- Load only after the entry-point mocks are installed.
		require( '../index' );
		startEntry = domReady.mock.calls[ 0 ][ 0 ];
	} );

	beforeEach( () => {
		jest.clearAllMocks();
		registeredCommands = [];
		registeredLoader = undefined;
		runEntry();
	} );

	afterEach( () => {
		jest.useRealTimers();
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
			[ 'post-new.php', { post_type: 'product' } ],
			[ 'admin.php', { page: 'wc-orders', action: 'new' } ],
			[ 'edit.php', { post_type: 'product' } ],
			[ 'admin.php', { page: 'wc-orders' } ],
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
				{ name: command.name, origin: undefined },
			] )
		);
	} );

	it( 'loads products, tracks searches, and navigates through product commands', () => {
		jest.useFakeTimers();
		const state = { records: undefined, isLoading: true };
		const getEntityRecords = jest.fn( () => state.records );
		const hasFinishedResolution = jest.fn( () => ! state.isLoading );
		useSelect.mockImplementation( ( callback ) =>
			callback( () => ( { getEntityRecords, hasFinishedResolution } ) )
		);

		const { result, rerender, unmount } = renderHook(
			( { search } ) => registeredLoader.hook( { search } ),
			{ initialProps: { search: '' } }
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
		expect( result.current ).toEqual( { commands: [], isLoading: true } );

		state.records = [
			{ id: 12, title: { rendered: 'Bread &amp; Butter' } },
			{ id: 13, title: {} },
		];
		state.isLoading = false;
		rerender( { search: 'bread' } );

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

		const close = jest.fn();
		result.current.commands[ 0 ].callback( { close } );
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
			{ name: 'woocommerce/product' }
		);

		jest.advanceTimersByTime( 300 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'woocommerce_command_palette_search',
			{ value: 'bread' }
		);

		rerender( { search: 'butter' } );
		expect( jest.getTimerCount() ).toBe( 1 );
		unmount();
		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	it( 'does not track a product search after unmount', () => {
		jest.useFakeTimers();
		const getEntityRecords = jest.fn( () => [] );
		const hasFinishedResolution = jest.fn( () => true );
		useSelect.mockImplementation( ( callback ) =>
			callback( () => ( { getEntityRecords, hasFinishedResolution } ) )
		);

		const { unmount } = renderHook( () =>
			registeredLoader.hook( { search: 'bread' } )
		);

		unmount();
		jest.advanceTimersByTime( 300 );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
