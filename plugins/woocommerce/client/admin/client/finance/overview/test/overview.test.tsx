/**
 * External dependencies
 */
import { render, screen, waitFor, within } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { FinanceOverview } from '..';
import type { Balance, FinanceProvider } from '../../types';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `https://example.test/wp-admin/${ path }`,
	getSetting: () => ( {} ),
} ) );

const mockApiFetch = apiFetch as unknown as jest.Mock;

const provider = (
	id: string,
	title: string,
	dataTypes: FinanceProvider[ 'data_types' ] = [
		{ type: 'balance', schema_version: 1 },
		{ type: 'payouts', schema_version: 1 },
	]
): FinanceProvider => ( {
	provider_id: id,
	title,
	icon_url: null,
	data_types: dataTypes,
} );

const balance = ( overrides: Partial< Balance > = {} ): Balance => ( {
	provider_id: 'bacs',
	currency: 'USD',
	amount: '100.00',
	available_amount: '80.00',
	payout_link: null,
	...overrides,
} );

const page = ( items: Balance[] ) => ( {
	schema_version: 1,
	items,
	has_more: false,
	next_cursor: null,
	prev_cursor: null,
} );

const mockEndpoints = ( responses: Record< string, unknown > ) => {
	mockApiFetch.mockImplementation( ( { path }: { path: string } ) => {
		const match = Object.keys( responses ).find( ( key ) =>
			path.endsWith( key )
		);
		return match
			? Promise.resolve( responses[ match ] )
			: Promise.reject( {
					code: 'not_found',
					message: `No mock for ${ path }`,
			  } );
	} );
};

describe( 'FinanceOverview', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'shows the empty state with a link to Payments settings when there are no providers', async () => {
		mockEndpoints( { '/providers': { providers: [] } } );

		render( <FinanceOverview /> );

		expect(
			await screen.findByText( 'No payout providers' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Manage payment providers' } )
		).toHaveAttribute(
			'href',
			'https://example.test/wp-admin/admin.php?page=wc-settings&tab=checkout'
		);
	} );

	it( 'renders one row per provider with balances per currency and the payout link', async () => {
		mockEndpoints( {
			'/providers': {
				providers: [
					provider( 'bacs', 'Bank transfer' ),
					provider( 'cheque', 'Cheque', [
						{ type: 'payouts', schema_version: 1 },
					] ),
				],
			},
			'/providers/bacs/balance': page( [
				balance( {
					payout_link: {
						title: 'Instant payout',
						url: 'https://provider.test/payout',
					},
				} ),
				balance( {
					currency: 'EUR',
					amount: '50.00',
					available_amount: null,
				} ),
			] ),
		} );

		render( <FinanceOverview /> );

		await screen.findByRole( 'link', { name: 'Instant payout' } );
		const bacsRow = screen.getByRole( 'row', { name: /Bank transfer/ } );
		expect(
			within( bacsRow ).getByText( 'Bank transfer' )
		).toBeInTheDocument();
		expect(
			within( bacsRow ).getAllByText( /100/ ).length
		).toBeGreaterThan( 0 );
		expect( within( bacsRow ).getAllByText( /50/ ).length ).toBeGreaterThan(
			0
		);
		expect( within( bacsRow ).getByText( '—' ) ).toBeInTheDocument();
		expect(
			within( bacsRow ).getByRole( 'link', { name: 'Instant payout' } )
		).toHaveAttribute( 'href', 'https://provider.test/payout' );

		const chequeRow = screen.getByRole( 'row', { name: /Cheque/ } );
		expect(
			within( chequeRow ).getByText( 'Balance not available' )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'link', { name: 'Add or manage providers' } )
		).toBeInTheDocument();
		expect( mockApiFetch ).not.toHaveBeenCalledWith( {
			path: expect.stringContaining( '/providers/cheque/balance' ),
		} );
	} );

	it( 'shows the balance error inside the provider row', async () => {
		mockEndpoints( {
			'/providers': {
				providers: [ provider( 'bacs', 'Bank transfer' ) ],
			},
		} );

		render( <FinanceOverview /> );

		await waitFor( () =>
			expect(
				screen.getByText(
					'No mock for /wc-admin/payments/finance/providers/bacs/balance'
				)
			).toBeInTheDocument()
		);
	} );

	it( 'shows the providers error as a notice', async () => {
		mockApiFetch.mockRejectedValue( {
			code: 'rest_forbidden',
			message: 'Sorry, you are not allowed to do that.',
		} );

		render( <FinanceOverview /> );

		// The message is also announced in a live region, so match the visible notice.
		expect(
			(
				await screen.findAllByText(
					'Sorry, you are not allowed to do that.'
				)
			).some( ( element ) => element.closest( '.components-notice' ) )
		).toBe( true );
	} );
} );
