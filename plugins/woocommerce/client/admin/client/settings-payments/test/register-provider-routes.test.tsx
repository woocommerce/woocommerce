describe( 'Settings Payments provider route bootstrap', () => {
	beforeEach( () => {
		jest.resetModules();
	} );

	it( 'registers Core-owned provider routes from the bootstrap module', async () => {
		await jest.isolateModulesAsync( async () => {
			const {
				getSettingsPaymentsProviderRoutes,
				resetSettingsPaymentsProviderRoutesForTesting,
			} = await import( '../provider-routes' );

			resetSettingsPaymentsProviderRoutesForTesting();

			await import( '../register-provider-routes' );

			const routes = getSettingsPaymentsProviderRoutes();

			expect(
				routes.map( ( { path: routePath } ) => routePath )
			).toEqual( [
				'/woopayments/settings',
				'/woopayments/overview',
				'/woopayments/payouts',
				'/woopayments/payouts/details',
				'/woopayments/transactions',
				'/woopayments/transactions/details',
				'/woopayments/disputes',
				'/woopayments/disputes/details',
				'/woopayments/disputes/challenge',
				'/woopayments/card-readers',
				'/woopayments/loans',
			] );
			routes.forEach( ( route ) => {
				expect( route.path ).not.toMatch( /^\/payments\// );
			} );
		} );
	} );
} );
