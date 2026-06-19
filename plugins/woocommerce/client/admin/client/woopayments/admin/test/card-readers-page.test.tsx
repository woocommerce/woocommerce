/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { WooPaymentsCardReadersPage } from '../card-readers/page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPaymentsCardReadersPage', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
	} );

	it( 'loads and renders connected card readers', async () => {
		mockApiFetch.mockResolvedValue( [
			{
				id: 'tmr_active',
				device_type: 'bbpos_wisepos_e',
				is_active: true,
			},
			{
				id: 'tmr_inactive',
				device_type: 'stripe_m2',
				is_active: false,
			},
		] );

		render( <WooPaymentsCardReadersPage /> );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading card readers…'
		);

		expect(
			await screen.findByRole( 'heading', {
				name: 'Connected card readers',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Card readers are marked as active if they’ve processed one or more transactions during the current billing cycle. To connect or disconnect card readers, use the WooCommerce mobile application.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'region', { name: 'Connected card readers' } )
		).toHaveClass( 'woocommerce-woopayments-card-readers__settings-card' );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/readers?limit=10',
			method: 'GET',
		} );
		expect(
			screen.getByRole( 'columnheader', { name: 'Reader ID' } )
		).toBeInTheDocument();
		expect(
			await screen.findByRole( 'cell', { name: 'tmr_active' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'cell', { name: 'bbpos_wisepos_e' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Active' ) ).toHaveClass(
			'woocommerce-woopayments-card-readers__status-badge',
			'is-active'
		);
		expect( screen.getByText( 'Inactive' ) ).toHaveClass(
			'woocommerce-woopayments-card-readers__status-badge',
			'is-inactive'
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Card readers loaded.'
		);
	} );

	it( 'announces empty card reader results', async () => {
		mockApiFetch.mockResolvedValue( [] );

		render( <WooPaymentsCardReadersPage /> );

		expect( await screen.findByRole( 'status' ) ).toHaveTextContent(
			'No card readers found.'
		);
		expect(
			screen.queryByRole( 'button', { name: /view options/i } )
		).not.toBeInTheDocument();
	} );

	it( 'announces loading errors', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'Readers unavailable.' ) );

		render( <WooPaymentsCardReadersPage /> );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Readers unavailable.'
		);
	} );
} );
