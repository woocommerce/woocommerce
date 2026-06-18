/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import fs from 'fs';
import path from 'path';

/**
 * Internal dependencies
 */
import {
	getSettingsPaymentsProviderRoutes,
	resetSettingsPaymentsProviderRoutesForTesting,
} from '~/settings-payments/provider-routes';
import { SettingsPaymentsWoopayments } from '~/settings-payments/settings-payments-woopayments';

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

		expect( routes ).toHaveLength( 8 );
		expect(
			routes.map( ( { id, path: routePath, order } ) => ( {
				id,
				path: routePath,
				order,
			} ) )
		).toEqual( [
			{
				id: 'woopayments-settings',
				path: '/woopayments/settings',
				order: 90,
			},
			{
				id: 'woopayments-overview',
				path: '/woopayments/overview',
				order: 100,
			},
			{
				id: 'woopayments-payouts',
				path: '/woopayments/payouts',
				order: 110,
			},
			{
				id: 'woopayments-transactions',
				path: '/woopayments/transactions',
				order: 120,
			},
			{
				id: 'woopayments-transaction-details',
				path: '/woopayments/transactions/details',
				order: 121,
			},
			{
				id: 'woopayments-disputes',
				path: '/woopayments/disputes',
				order: 122,
			},
			{
				id: 'woopayments-dispute-details',
				path: '/woopayments/disputes/details',
				order: 123,
			},
			{
				id: 'woopayments-dispute-challenge',
				path: '/woopayments/disputes/challenge',
				order: 124,
			},
		] );
		routes.forEach( ( route ) => {
			expect( route.element ).toBeDefined();
		} );
		expect(
			routes.some( ( route ) => /^\/payments\//.test( route.path ) )
		).toBe( false );
		expect( JSON.stringify( routes ) ).not.toContain(
			'wc-pay-welcome-page'
		);
	} );

	it( 'loads the settings route from a dedicated settings chunk', () => {
		const source = fs.readFileSync(
			path.resolve( __dirname, '../routes.tsx' ),
			'utf8'
		);

		expect( source ).toContain(
			'webpackChunkName: "settings-payments-woopayments-settings"'
		);
	} );

	it( 'renders the native settings page from the legacy WooPayments settings shell', () => {
		render( <SettingsPaymentsWoopayments /> );

		expect(
			screen.getByRole( 'heading', {
				name: 'WooPayments settings',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'region', {
				name: 'WooPayments settings',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( /Loading WooPayments settings/ )
		).toBeInTheDocument();
		expect(
			screen.queryByText( /Native settings placeholder/ )
		).not.toBeInTheDocument();
	} );
} );
