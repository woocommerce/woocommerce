/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
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

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPayments Settings Payments routes', () => {
	beforeAll( async () => {
		resetSettingsPaymentsProviderRoutesForTesting();
		await import( '../routes' );
	} );

	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockApiFetch.mockReset();
	} );

	afterAll( () => {
		resetSettingsPaymentsProviderRoutesForTesting();
	} );

	it( 'registers WooPayments under the Settings Payments route seam', () => {
		const routes = getSettingsPaymentsProviderRoutes();

		expect( routes ).toHaveLength( 14 );
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
				id: 'woopayments-express-checkout-settings',
				path: '/woopayments/settings/express-checkout/:methodId',
				order: 91,
			},
			{
				id: 'woopayments-fraud-protection-settings',
				path: '/woopayments/settings/fraud-protection',
				order: 92,
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
				id: 'woopayments-payout-details',
				path: '/woopayments/payouts/details',
				order: 111,
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
			{
				id: 'woopayments-card-readers',
				path: '/woopayments/card-readers',
				order: 125,
			},
			{
				id: 'woopayments-capital',
				path: '/woopayments/loans',
				order: 126,
			},
			{
				id: 'woopayments-documents',
				path: '/woopayments/documents',
				order: 127,
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

	it( 'loads the express checkout settings route from a dedicated chunk', () => {
		const source = fs.readFileSync(
			path.resolve( __dirname, '../routes.tsx' ),
			'utf8'
		);

		expect( source ).toContain(
			'webpackChunkName: "settings-payments-woopayments-express-checkout-settings"'
		);
	} );

	it( 'loads the fraud protection settings route from a dedicated chunk', () => {
		const source = fs.readFileSync(
			path.resolve( __dirname, '../routes.tsx' ),
			'utf8'
		);

		expect( source ).toContain(
			'webpackChunkName: "settings-payments-woopayments-fraud-protection-settings"'
		);
	} );

	it( 'loads the Documents route from a dedicated chunk', () => {
		const source = fs.readFileSync(
			path.resolve( __dirname, '../routes.tsx' ),
			'utf8'
		);

		expect( source ).toContain(
			'webpackChunkName: "settings-payments-woopayments-documents"'
		);
	} );

	it( 'announces lazy route loading through a status fallback', async () => {
		mockApiFetch
			.mockResolvedValueOnce( {} )
			.mockResolvedValueOnce( {} )
			.mockResolvedValueOnce( {
				data: [],
			} );

		const route = getSettingsPaymentsProviderRoutes().find(
			( { path: routePath } ) => routePath === '/woopayments/loans'
		);

		expect( route ).toBeDefined();
		if ( ! route ) {
			throw new Error(
				'Expected the WooPayments Capital route to exist.'
			);
		}

		render( route.element );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading WooPayments…'
		);
		expect( screen.getByRole( 'status' ) ).toHaveAttribute(
			'aria-busy',
			'true'
		);
		expect(
			await screen.findByText( 'No Capital loans found.', {
				selector: '.woocommerce-woopayments-capital__empty',
			} )
		).toBeInTheDocument();
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
