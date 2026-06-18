/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { WooPaymentsCapitalPage } from '../capital/page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPaymentsCapitalPage', () => {
	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockApiFetch.mockReset();
	} );

	it( 'loads Capital loans and active loan summary from preserved endpoints', async () => {
		mockApiFetch
			.mockResolvedValueOnce( {
				details: {
					advance_amount: 100000,
					advance_paid_out_at: 1729505500,
					currency: 'usd',
					current_repayment_interval: {
						due_at: 1735294500,
						paid_amount: 20000,
						remaining_amount: 30000,
					},
					fee_amount: 10000,
					paid_amount: 20000,
					remaining_amount: 90000,
					repayments_begin_at: 1730110500,
					withhold_rate: 0.15,
				},
			} )
			.mockResolvedValueOnce( {
				data: [
					{
						stripe_loan_id: 'loan_test',
						amount: 100000,
						currency: 'usd',
						fee_amount: 10000,
						withhold_rate: 0.15,
						paid_out_at: '2024-10-28 10:11:40',
						first_paydown_at: null,
						fully_paid_at: null,
					},
				],
			} );

		render( <WooPaymentsCapitalPage /> );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading Capital Loans…'
		);

		expect(
			await screen.findByRole( 'heading', {
				name: 'Active loan overview',
			} )
		).toBeInTheDocument();
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/capital/active_loan_summary',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/capital/loans',
			method: 'GET',
		} );
		expect(
			screen.getByText( '$200.00 of $1,100.00' )
		).toBeInTheDocument();
		expect( screen.getAllByText( '15%' ) ).toHaveLength( 2 );
		expect(
			screen.getByRole( 'link', {
				name: 'Oct 28, 2024 - view transactions for loan loan_test',
			} )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions&loan_id_is=loan_test'
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Capital Loans loaded.'
		);
	} );

	it( 'normalizes provider date-time strings before rendering dates', async () => {
		const RealDate = Date;
		class RejectSpaceSeparatedDate extends RealDate {
			constructor( value?: string | number | Date ) {
				if (
					typeof value === 'string' &&
					/^\d{4}-\d{2}-\d{2} /.test( value )
				) {
					super( Number.NaN );
					return;
				}

				if ( undefined === value ) {
					super();
					return;
				}

				super( value );
			}
		}

		global.Date = RejectSpaceSeparatedDate as DateConstructor;
		mockApiFetch.mockResolvedValueOnce( {} ).mockResolvedValueOnce( {
			data: [
				{
					stripe_loan_id: 'loan_date_string',
					amount: 100000,
					currency: 'usd',
					fee_amount: 10000,
					withhold_rate: 0.15,
					paid_out_at: '2024-10-28 10:11:40',
					first_paydown_at: '2024-11-04 00:00:00',
					fully_paid_at: null,
				},
			],
		} );

		try {
			render( <WooPaymentsCapitalPage /> );

			expect(
				await screen.findByRole( 'link', {
					name: 'Oct 28, 2024 - view transactions for loan loan_date_string',
				} )
			).toBeInTheDocument();
			expect( screen.getByText( 'Nov 4, 2024' ) ).toBeInTheDocument();
		} finally {
			global.Date = RealDate;
		}
	} );

	it( 'announces empty Capital loan results', async () => {
		mockApiFetch.mockResolvedValueOnce( {} ).mockResolvedValueOnce( {
			data: [],
		} );

		render( <WooPaymentsCapitalPage /> );

		expect(
			await screen.findByText( 'No Capital loans found.', {
				selector: '.woocommerce-woopayments-capital__empty',
			} )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'No Capital loans found.'
		);
	} );

	it( 'announces loading errors', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'Capital unavailable.' ) );

		render( <WooPaymentsCapitalPage /> );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Capital unavailable.'
		);
	} );
} );
