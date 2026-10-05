import { beforeEach, describe, expect, it, vi } from 'vitest';

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
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		queueRecordEvent: vi.fn(),
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
vi.mock( '@wordpress/data', () => {
	const mock = {
		dispatch: vi.fn(),
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
vi.mock( '@wordpress/icons', () => {
	const mock = {
		chartBar: 'chart-bar-icon',
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
		sprintf: ( format, value ) => format.replace( '%s', value ),
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
const registerWithReports = async ( analytics ) => {
	const commands = [];
	dispatch.mockReturnValue( {
		registerCommand: ( command ) => commands.push( command ),
	} );
	if ( analytics === undefined ) {
		delete window.wcCommandPaletteAnalytics;
	} else {
		window.wcCommandPaletteAnalytics = analytics;
	}
	vi.resetModules(),
		await ( async () => {
			// eslint-disable-next-line @typescript-eslint/no-require-imports -- Load after each injected report state is installed.
			await import( '../index' );
		} )();
	domReady.mock.calls[ 0 ][ 0 ]();
	return commands;
};
describe( 'Analytics Command Palette', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it.each( [
		{
			caseName: 'missing global',
			analytics: undefined,
		},
		{
			caseName: 'null global',
			analytics: null,
		},
		{
			caseName: 'missing reports',
			analytics: {},
		},
		{
			caseName: 'non-array reports',
			analytics: {
				reports: {},
			},
		},
		{
			caseName: 'empty reports',
			analytics: {
				reports: [],
			},
		},
	] )(
		'does not register commands for an invalid injected report state: $caseName',
		async ( { analytics } ) => {
			expect( await registerWithReports( analytics ) ).toEqual( [] );
			expect( dispatch ).not.toHaveBeenCalled();
		}
	);
	it( 'registers injected Analytics reports with exact destinations and tracking', async () => {
		const commands = await registerWithReports( {
			reports: [
				{
					title: 'Revenue',
					path: '/analytics/revenue',
				},
				{
					title: 'Orders',
					path: '/analytics/orders',
				},
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
			[
				'admin.php',
				{
					page: 'wc-admin',
					path: '/analytics/revenue',
				},
			],
			[
				'admin.php',
				{
					page: 'wc-admin',
					path: '/analytics/orders',
				},
			],
		] );
		expect( queueRecordEvent.mock.calls ).toEqual(
			commands.map( ( command ) => [
				'woocommerce_command_palette_submit',
				{
					name: command.name,
					origin: undefined,
				},
			] )
		);
	} );
} );
