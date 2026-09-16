/**
 * External dependencies
 */
import { queueRecordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line import/no-unresolved -- Provided by WordPress in the wp-admin runtime.
import { store as commandsStore } from '@wordpress/commands';
import { dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { chartBar } from '@wordpress/icons';
import { addQueryArgs } from '@wordpress/url';

jest.mock( '@woocommerce/tracks', () => ( { queueRecordEvent: jest.fn() } ) );
jest.mock( '@wordpress/commands', () => ( { store: 'commands-store' } ) );
jest.mock( '@wordpress/data', () => ( { dispatch: jest.fn() } ) );
jest.mock( '@wordpress/dom-ready', () => jest.fn() );
jest.mock( '@wordpress/icons', () => ( { chartBar: 'chart-bar-icon' } ) );
jest.mock( '@wordpress/i18n', () => ( {
	__: ( value ) => value,
	sprintf: ( format, value ) => format.replace( '%s', value ),
} ) );
jest.mock( '@wordpress/url', () => ( {
	addQueryArgs: jest.fn(
		( base, args ) => `#${ base }-${ JSON.stringify( args ) }`
	),
} ) );

const registerWithReports = ( analytics ) => {
	const commands = [];
	dispatch.mockReturnValue( {
		registerCommand: ( command ) => commands.push( command ),
	} );
	if ( analytics === undefined ) {
		delete window.wcCommandPaletteAnalytics;
	} else {
		window.wcCommandPaletteAnalytics = analytics;
	}
	jest.isolateModules( () => {
		// eslint-disable-next-line @typescript-eslint/no-require-imports -- Load after each injected report state is installed.
		require( '../index' );
	} );
	domReady.mock.calls[ 0 ][ 0 ]();
	return commands;
};

describe( 'Analytics Command Palette', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it.each( [
		{ caseName: 'missing global', analytics: undefined },
		{ caseName: 'missing reports', analytics: {} },
		{ caseName: 'non-array reports', analytics: { reports: {} } },
		{ caseName: 'empty reports', analytics: { reports: [] } },
	] )(
		'does not register commands for an invalid injected report state: $caseName',
		( { analytics } ) => {
			expect( registerWithReports( analytics ) ).toEqual( [] );
			expect( dispatch ).not.toHaveBeenCalled();
		}
	);

	it( 'registers injected Analytics reports with exact destinations and tracking', () => {
		const commands = registerWithReports( {
			reports: [
				{ title: 'Revenue', path: '/analytics/revenue' },
				{ title: 'Orders', path: '/analytics/orders' },
			],
		} );

		expect( dispatch ).toHaveBeenCalledWith( commandsStore );
		expect(
			commands.map( ( command ) => ( {
				name: command.name,
				label: command.label,
				icon: command.icon,
			} ) )
		).toEqual( [
			{
				name: 'woocommerce/analytics/revenue',
				label: 'WooCommerce Analytics: Revenue',
				icon: chartBar,
			},
			{
				name: 'woocommerce/analytics/orders',
				label: 'WooCommerce Analytics: Orders',
				icon: chartBar,
			},
		] );

		commands.forEach( ( command, index ) => {
			command.callback();
			expect( decodeURIComponent( window.location.hash ) ).toBe(
				addQueryArgs.mock.results[ index ].value
			);
		} );
		expect( addQueryArgs.mock.calls ).toEqual( [
			[ 'admin.php', { page: 'wc-admin', path: '/analytics/revenue' } ],
			[ 'admin.php', { page: 'wc-admin', path: '/analytics/orders' } ],
		] );
		expect( queueRecordEvent.mock.calls ).toEqual(
			commands.map( ( command ) => [
				'woocommerce_command_palette_submit',
				{ name: command.name, origin: undefined },
			] )
		);
	} );
} );
