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
const ACCOUNT_PATH = '/wc-admin/settings/payments/woopayments/account';
const SUMMARY_PATH = '/wc/v3/payments/capital/active_loan_summary';
const LOANS_PATH = '/wc/v3/payments/capital/loans';

const activeLoanSummary = {
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
};

const activeLoan = {
	stripe_loan_id: 'loan_test',
	amount: 100000,
	currency: 'usd',
	fee_amount: 10000,
	withhold_rate: 0.15,
	paid_out_at: '2024-10-28 10:11:40',
	first_paydown_at: null,
	fully_paid_at: null,
};

const paidLoan = {
	stripe_loan_id: 'loan_paid',
	amount: 50000,
	currency: 'usd',
	fee_amount: 5000,
	withhold_rate: 0.1,
	paid_out_at: '2024-09-01 10:11:40',
	first_paydown_at: '2024-09-08 00:00:00',
	fully_paid_at: '2024-10-01 00:00:00',
};

const mockCapitalApi = ( {
	summary = activeLoanSummary,
	loans = [ activeLoan ],
	account = {
		account: {
			connected: true,
			live: true,
			mode: 'live',
			test_mode: false,
			test_drive: false,
			sandbox: false,
		},
		urls: {},
	},
}: {
	summary?: Record< string, unknown >;
	loans?: Array< Record< string, unknown > >;
	account?: Record< string, unknown >;
} = {} ) => {
	mockApiFetch.mockImplementation( ( ( options ) => {
		const path = String( options?.path || '' );

		if ( path === SUMMARY_PATH ) {
			return Promise.resolve( summary );
		}

		if ( path === LOANS_PATH ) {
			return Promise.resolve( { data: loans } );
		}

		if ( path === ACCOUNT_PATH ) {
			return Promise.resolve( account );
		}

		return Promise.reject( new Error( `Unexpected path ${ path }` ) );
	} ) as typeof apiFetch );
};

describe( 'WooPaymentsCapitalPage', () => {
	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockApiFetch.mockReset();
	} );

	it( 'loads Capital loans and active loan summary from preserved endpoints', async () => {
		mockCapitalApi();

		render( <WooPaymentsCapitalPage /> );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading Capital Loans…'
		);

		expect(
			await screen.findByRole( 'heading', {
				name: 'Active loan overview',
			} )
		).toBeInTheDocument();
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/capital/active_loan_summary',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/capital/loans',
			method: 'GET',
		} );
		expect(
			screen.getByText( '$200.00 of $1,100.00' )
		).toBeInTheDocument();
		expect( screen.getAllByText( '15%' ) ).toHaveLength( 2 );
		expect(
			screen.getByRole( 'link', {
				name: 'View transactions for loan loan_test',
			} )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions&loan_id_is=loan_test'
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Capital Loans loaded.'
		);
	} );

	it( 'renders the reference Capital loan summary and row affordances', async () => {
		mockCapitalApi( {
			loans: [ activeLoan, paidLoan ],
		} );

		render( <WooPaymentsCapitalPage /> );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Active loan overview',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View transactions' } )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions&loan_id_is=loan_test'
		);
		expect(
			screen.getByText( /Repaid this period \(until / )
		).toBeInTheDocument();
		expect(
			screen.getByText( '$200.00 of $500.00 minimum' )
		).toBeInTheDocument();
		expect( screen.getByText( '2 loans' ) ).toBeInTheDocument();
		expect( screen.getByText( '$1,500.00 total' ) ).toBeInTheDocument();
		expect( screen.getByText( '$150.00 fixed fees' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Active' ) ).toHaveClass(
			'woocommerce-woopayments-capital__status-chip',
			'is-active'
		);
		expect( screen.getByText( /Paid off:/ ) ).toHaveClass(
			'woocommerce-woopayments-capital__status-chip',
			'is-paid-off'
		);
		expect(
			screen.getByRole( 'link', {
				name: 'View transactions for loan loan_test',
			} )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions&loan_id_is=loan_test'
		);
		expect(
			screen.queryByRole( 'link', {
				name: '$1,000.00 - view transactions for loan loan_test',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'shows a test mode notice for Capital loans when the connected account is in test mode', async () => {
		mockCapitalApi( {
			account: {
				account: {
					connected: true,
					live: false,
					mode: 'test',
					test_mode: true,
					test_drive: true,
					sandbox: false,
				},
				urls: {},
			},
		} );

		render( <WooPaymentsCapitalPage /> );

		expect(
			await screen.findByText( 'Viewing test loans.' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'WooPayments settings' } )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings'
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
		mockCapitalApi( {
			summary: {},
			loans: [
				{
					...activeLoan,
					stripe_loan_id: 'loan_date_string',
					first_paydown_at: '2024-11-04 00:00:00',
				},
			],
		} );

		try {
			render( <WooPaymentsCapitalPage /> );

			expect(
				await screen.findByRole( 'link', {
					name: 'View transactions for loan loan_date_string',
				} )
			).toBeInTheDocument();
			expect( screen.getByText( 'Nov 4, 2024' ) ).toBeInTheDocument();
		} finally {
			global.Date = RealDate;
		}
	} );

	it( 'announces empty Capital loan results', async () => {
		mockCapitalApi( { summary: {}, loans: [] } );

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
