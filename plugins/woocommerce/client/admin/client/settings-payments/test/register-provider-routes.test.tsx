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

			expect( routes ).toHaveLength( 1 );
			expect( routes[ 0 ] ).toMatchObject( {
				id: 'woopayments-overview',
				path: '/woopayments/overview',
				order: 100,
			} );
			expect( routes[ 0 ].path ).not.toMatch( /^\/payments\// );
		} );
	} );
} );
