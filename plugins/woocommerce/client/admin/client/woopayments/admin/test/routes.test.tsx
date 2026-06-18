/**
 * Internal dependencies
 */
import {
	getSettingsPaymentsProviderRoutes,
	resetSettingsPaymentsProviderRoutesForTesting,
} from '~/settings-payments/provider-routes';

describe( 'WooPayments Settings Payments routes', () => {
	beforeEach( () => {
		resetSettingsPaymentsProviderRoutesForTesting();
	} );

	afterEach( () => {
		resetSettingsPaymentsProviderRoutesForTesting();
	} );

	it( 'registers WooPayments under the Settings Payments route seam', async () => {
		await import( '../routes' );

		const routes = getSettingsPaymentsProviderRoutes();

		expect( routes ).toHaveLength( 1 );
		expect( routes[ 0 ] ).toMatchObject( {
			id: 'woopayments-overview',
			path: '/woopayments/overview',
			order: 100,
		} );
		expect( routes[ 0 ].element ).toBeDefined();
		expect( routes[ 0 ].path ).not.toMatch( /^\/payments\// );
		expect( JSON.stringify( routes ) ).not.toContain(
			'wc-pay-welcome-page'
		);
	} );
} );
