/**
 * External dependencies
 */
import { cleanup, render, screen } from '@testing-library/react';
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
jest.mock( '../overview', () => () => 'Overview route loaded' );
jest.mock(
	'../money-movement/transactions',
	() => () => 'Transactions route loaded'
);
jest.mock( '../money-movement/disputes', () => () => 'Disputes route loaded' );
jest.mock( '../../settings', () => {
	const React = jest.requireActual( 'react' );
	const WooPaymentsSettingsPage = () =>
		React.createElement(
			'section',
			{ 'aria-label': 'WooPayments settings' },
			React.createElement( 'h1', null, 'WooPayments settings' ),
			React.createElement( 'p', null, 'Loading WooPayments settings' )
		);

	return {
		__esModule: true,
		default: () => 'Settings route loaded',
		WooPaymentsSettingsPage,
	};
} );
jest.mock(
	'../../settings/express-checkout',
	() => () => 'Express settings route loaded'
);
jest.mock(
	'../../settings/fraud-protection/advanced',
	() => () => 'Fraud settings route loaded'
);

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const unavailableMessage = 'This WooPayments admin area is unavailable.';

const protectedRouteAvailability = {
	gatewayEnabled: true,
	accountState: 'full',
	allowedRoutes: {
		'/woopayments/settings': true,
		'/woopayments/overview': true,
		'/woopayments/payouts': true,
		'/woopayments/payouts/details': true,
		'/woopayments/transactions': true,
		'/woopayments/transactions/details': true,
		'/woopayments/disputes': true,
		'/woopayments/disputes/details': true,
		'/woopayments/disputes/challenge': true,
		'/woopayments/reports': true,
		'/woopayments/card-readers': true,
		'/woopayments/loans': true,
		'/woopayments/documents': true,
	},
};

const setAdminRouteAvailability = (
	allowedRoutes: Record< string, boolean >
) => {
	window.wcSettings = {
		adminUrl: 'http://example.com/wp-admin',
		admin: {
			woopaymentsSettings: {
				adminRouteAvailability: {
					...protectedRouteAvailability,
					allowedRoutes: {
						...protectedRouteAvailability.allowedRoutes,
						...allowedRoutes,
					},
				},
			},
		},
	};
};

const getRouteElement = ( routePath: string ) => {
	const route = getSettingsPaymentsProviderRoutes().find(
		( { path: registeredPath } ) => registeredPath === routePath
	);

	expect( route ).toBeDefined();
	if ( ! route ) {
		throw new Error(
			`Expected the WooPayments route ${ routePath } to exist.`
		);
	}

	return route.element;
};

const expectRouteUnavailable = ( routePath: string ) => {
	render( getRouteElement( routePath ) );

	expect( screen.getByRole( 'status' ) ).toHaveTextContent(
		unavailableMessage
	);
	expect(
		screen.queryByText( 'Loading WooPayments…' )
	).not.toBeInTheDocument();
	expect( mockApiFetch ).not.toHaveBeenCalled();
};

describe( 'WooPayments Settings Payments routes', () => {
	beforeAll( async () => {
		resetSettingsPaymentsProviderRoutesForTesting();
		await import( '../routes' );
	} );

	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		delete (
			window as typeof window & {
				wcpaySettings?: unknown;
			}
		 ).wcpaySettings;
		mockApiFetch.mockReset();
	} );

	afterAll( () => {
		resetSettingsPaymentsProviderRoutesForTesting();
	} );

	it( 'registers WooPayments under the Settings Payments route seam', () => {
		const routes = getSettingsPaymentsProviderRoutes();

		expect( routes ).toHaveLength( 15 );
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
				id: 'woopayments-reports',
				path: '/woopayments/reports',
				order: 122,
			},
			{
				id: 'woopayments-disputes',
				path: '/woopayments/disputes',
				order: 123,
			},
			{
				id: 'woopayments-dispute-details',
				path: '/woopayments/disputes/details',
				order: 124,
			},
			{
				id: 'woopayments-dispute-challenge',
				path: '/woopayments/disputes/challenge',
				order: 125,
			},
			{
				id: 'woopayments-card-readers',
				path: '/woopayments/card-readers',
				order: 126,
			},
			{
				id: 'woopayments-capital',
				path: '/woopayments/loans',
				order: 127,
			},
			{
				id: 'woopayments-documents',
				path: '/woopayments/documents',
				order: 128,
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

	it( 'loads the Reports route from a dedicated chunk', () => {
		const source = fs.readFileSync(
			path.resolve( __dirname, '../routes.tsx' ),
			'utf8'
		);

		expect( source ).toContain(
			'webpackChunkName: "settings-payments-woopayments-reports"'
		);
	} );

	it( 'does not load the Reports chunk when the Reports feature flag is disabled', () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
			admin: {
				woopaymentsSettings: {
					featureFlags: {
						reportsArea: false,
					},
					adminRouteAvailability: protectedRouteAvailability,
				},
			},
		};

		const route = getSettingsPaymentsProviderRoutes().find(
			( { path: routePath } ) => routePath === '/woopayments/reports'
		);

		expect( route ).toBeDefined();
		if ( ! route ) {
			throw new Error(
				'Expected the WooPayments Reports route to exist.'
			);
		}

		render( route.element );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Reports are unavailable.'
		);
		expect(
			screen.queryByText( 'Loading WooPayments…' )
		).not.toBeInTheDocument();
	} );

	it( 'uses the legacy Reports feature flag when native settings do not provide one', () => {
		(
			window as typeof window & {
				wcpaySettings?: {
					featureFlags?: {
						reportsArea?: boolean;
					};
				};
			}
		 ).wcpaySettings = {
			featureFlags: {
				reportsArea: false,
			},
		};
		setAdminRouteAvailability( {
			'/woopayments/reports': true,
		} );

		render( getRouteElement( '/woopayments/reports' ) );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Reports are unavailable.'
		);
		expect(
			screen.queryByText( 'Loading WooPayments…' )
		).not.toBeInTheDocument();
	} );

	it.each( [
		[ 'Capital', '/woopayments/loans' ],
		[ 'Documents', '/woopayments/documents' ],
		[ 'Card Readers', '/woopayments/card-readers' ],
		[ 'Reports', '/woopayments/reports' ],
		[ 'Payout details', '/woopayments/payouts/details' ],
		[ 'Transaction details', '/woopayments/transactions/details' ],
		[ 'Dispute details', '/woopayments/disputes/details' ],
		[ 'Dispute challenge', '/woopayments/disputes/challenge' ],
	] )(
		'renders an unavailable status for the %s route when route availability denies access',
		( _routeName, routePath ) => {
			setAdminRouteAvailability( {
				[ routePath ]: false,
			} );

			expectRouteUnavailable( routePath );
		}
	);

	it.each( [
		[ 'Overview', '/woopayments/overview' ],
		[ 'Transactions', '/woopayments/transactions' ],
		[ 'Disputes', '/woopayments/disputes' ],
	] )(
		'renders an unavailable status for the %s route when route availability is missing',
		( _routeName, routePath ) => {
			expectRouteUnavailable( routePath );
		}
	);

	it.each( [
		[ 'Overview', '/woopayments/overview' ],
		[ 'Transactions', '/woopayments/transactions' ],
		[ 'Disputes', '/woopayments/disputes' ],
	] )(
		'renders an unavailable status for the %s route when route availability does not name the route',
		( _routeName, routePath ) => {
			window.wcSettings = {
				adminUrl: 'http://example.com/wp-admin',
				admin: {
					woopaymentsSettings: {
						adminRouteAvailability: {
							...protectedRouteAvailability,
							allowedRoutes: {
								'/woopayments/settings': true,
							},
						},
					},
				},
			};

			expectRouteUnavailable( routePath );
		}
	);

	it.each( [
		[ 'settings', '/woopayments/settings', 'Settings route loaded' ],
		[
			'express checkout settings',
			'/woopayments/settings/express-checkout/:methodId',
			'Express settings route loaded',
		],
		[
			'fraud protection settings',
			'/woopayments/settings/fraud-protection',
			'Fraud settings route loaded',
		],
	] )(
		'keeps the %s route loadable when protected admin routes are denied',
		async ( _routeName, routePath, loadedText ) => {
			setAdminRouteAvailability( {
				'/woopayments/overview': false,
				'/woopayments/payouts': false,
				'/woopayments/payouts/details': false,
				'/woopayments/transactions': false,
				'/woopayments/transactions/details': false,
				'/woopayments/disputes': false,
				'/woopayments/disputes/details': false,
				'/woopayments/disputes/challenge': false,
				'/woopayments/reports': false,
				'/woopayments/card-readers': false,
				'/woopayments/loans': false,
				'/woopayments/documents': false,
			} );

			render( getRouteElement( routePath ) );

			expect( screen.getByRole( 'status' ) ).toHaveTextContent(
				'Loading WooPayments…'
			);
			expect(
				screen.queryByText( unavailableMessage )
			).not.toBeInTheDocument();
			expect( await screen.findByText( loadedText ) ).toBeInTheDocument();
		}
	);

	it.each( [
		[ 'settings', '/woopayments/settings', 'Settings route loaded' ],
		[
			'express checkout settings',
			'/woopayments/settings/express-checkout/:methodId',
			'Express settings route loaded',
		],
		[
			'fraud protection settings',
			'/woopayments/settings/fraud-protection',
			'Fraud settings route loaded',
		],
	] )(
		'keeps the %s route loadable when route availability is missing',
		async ( _routeName, routePath, loadedText ) => {
			render( getRouteElement( routePath ) );

			expect(
				screen.queryByText( unavailableMessage )
			).not.toBeInTheDocument();
			expect( await screen.findByText( loadedText ) ).toBeInTheDocument();
		}
	);

	it( 'keeps restricted-account routes available only for reduced-access surfaces', async () => {
		setAdminRouteAvailability( {
			'/woopayments/overview': true,
			'/woopayments/transactions': true,
			'/woopayments/disputes': true,
			'/woopayments/payouts': false,
			'/woopayments/reports': false,
			'/woopayments/card-readers': false,
			'/woopayments/loans': false,
			'/woopayments/documents': false,
		} );

		const availableRoutes = [
			{
				path: '/woopayments/overview',
				loadedText: 'Overview route loaded',
			},
			{
				path: '/woopayments/transactions',
				loadedText: 'Transactions route loaded',
			},
			{
				path: '/woopayments/disputes',
				loadedText: 'Disputes route loaded',
			},
		];

		for ( const { loadedText, path: routePath } of availableRoutes ) {
			render( getRouteElement( routePath ) );
			expect( screen.getByRole( 'status' ) ).toHaveTextContent(
				'Loading WooPayments…'
			);
			expect(
				screen.queryByText( unavailableMessage )
			).not.toBeInTheDocument();
			expect( await screen.findByText( loadedText ) ).toBeInTheDocument();
			cleanup();
		}

		[
			'/woopayments/payouts',
			'/woopayments/reports',
			'/woopayments/card-readers',
			'/woopayments/loans',
			'/woopayments/documents',
		].forEach( ( routePath ) => {
			mockApiFetch.mockReset();
			expectRouteUnavailable( routePath );
			cleanup();
		} );
	} );

	it( 'announces lazy route loading through a status fallback', async () => {
		setAdminRouteAvailability( {
			'/woopayments/loans': true,
		} );
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
			await screen.findByText(
				'No Capital loans found.',
				{
					selector: '.woocommerce-woopayments-capital__empty',
				},
				{
					timeout: 3000,
				}
			)
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
