/**
 * External dependencies
 */
import { addFilter, removeAllFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	getSettingsPaymentsProviderRoutes,
	registerSettingsPaymentsProviderRoute,
	resetSettingsPaymentsProviderRoutesForTesting,
	SETTINGS_PAYMENTS_PROVIDER_ROUTES_FILTER,
} from '../provider-routes';

describe( 'settings payments provider routes', () => {
	beforeEach( () => {
		resetSettingsPaymentsProviderRoutesForTesting();
		removeAllFilters( SETTINGS_PAYMENTS_PROVIDER_ROUTES_FILTER );
	} );

	afterEach( () => {
		resetSettingsPaymentsProviderRoutesForTesting();
		removeAllFilters( SETTINGS_PAYMENTS_PROVIDER_ROUTES_FILTER );
	} );

	it( 'returns registered provider routes in deterministic order', () => {
		const secondElement = <div>Second route</div>;
		const firstElement = <div>First route</div>;

		registerSettingsPaymentsProviderRoute( {
			id: 'second-provider',
			path: '/second-provider/overview',
			order: 20,
			element: secondElement,
		} );
		registerSettingsPaymentsProviderRoute( {
			id: 'first-provider',
			path: 'first-provider/overview',
			order: 10,
			element: firstElement,
		} );

		expect( getSettingsPaymentsProviderRoutes() ).toEqual( [
			{
				id: 'first-provider',
				path: '/first-provider/overview',
				order: 10,
				element: firstElement,
			},
			{
				id: 'second-provider',
				path: '/second-provider/overview',
				order: 20,
				element: secondElement,
			},
		] );
	} );

	it( 'allows extensions to add provider routes through the filter seam', () => {
		const nativeElement = <div>Native route</div>;
		const extensionElement = <div>Extension route</div>;

		registerSettingsPaymentsProviderRoute( {
			id: 'native-provider',
			path: '/native-provider/overview',
			order: 10,
			element: nativeElement,
		} );

		addFilter(
			SETTINGS_PAYMENTS_PROVIDER_ROUTES_FILTER,
			'woocommerce/test-provider-route',
			( routes ) => [
				...routes,
				{
					id: 'extension-provider',
					path: '/extension-provider/settings',
					order: 15,
					element: extensionElement,
				},
			]
		);

		expect( getSettingsPaymentsProviderRoutes() ).toEqual( [
			{
				id: 'native-provider',
				path: '/native-provider/overview',
				order: 10,
				element: nativeElement,
			},
			{
				id: 'extension-provider',
				path: '/extension-provider/settings',
				order: 15,
				element: extensionElement,
			},
		] );
	} );

	it( 'fails closed when two provider routes use the same path', () => {
		registerSettingsPaymentsProviderRoute( {
			id: 'first-provider',
			path: '/shared/path',
			element: <div>First route</div>,
		} );
		registerSettingsPaymentsProviderRoute( {
			id: 'second-provider',
			path: 'shared/path',
			element: <div>Second route</div>,
		} );

		expect( () => getSettingsPaymentsProviderRoutes() ).toThrow(
			'Duplicate Payments settings provider route path registered: /shared/path'
		);
	} );
} );
