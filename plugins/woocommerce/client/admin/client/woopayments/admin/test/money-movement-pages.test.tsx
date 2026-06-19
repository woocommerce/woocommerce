/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { WooPaymentsDisputesPage } from '../money-movement/disputes-page';
import { WooPaymentsTransactionDetailsPage } from '../money-movement/transaction-details-page';
import { WooPaymentsTransactionsPage } from '../money-movement/transactions-page';
import {
	getWooPaymentsDisputes,
	getWooPaymentsDisputesSummary,
	getWooPaymentsCharge,
	getWooPaymentsPaymentIntent,
	getWooPaymentsTransaction,
	getWooPaymentsTransactions,
	getWooPaymentsTransactionsSummary,
	getWooPaymentsTransactionsExportUrl,
	getWooPaymentsDisputesExportUrl,
	requestWooPaymentsDisputesExport,
	requestWooPaymentsTransactionsExport,
} from '../money-movement/data';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( {
		data = [],
		fields = [],
		header,
		onChangeView,
		searchLabel,
		view = {},
	}: {
		data?: Array< Record< string, unknown > >;
		fields?: Array< {
			id: string;
			render?: ( props: {
				item: Record< string, unknown >;
			} ) => ReactNode;
		} >;
		header?: ReactNode;
		onChangeView?: ( view: Record< string, unknown > ) => void;
		searchLabel?: string;
		view?: { search?: string; fields?: string[] };
	} ) => (
		<div data-testid="money-movement-dataviews">
			{ searchLabel && (
				<input
					type="search"
					aria-label={ searchLabel }
					value={ view.search || '' }
					readOnly
				/>
			) }
			<button
				type="button"
				onClick={ () =>
					onChangeView?.( {
						...view,
						fields: [ 'type', 'amount' ],
						layout: {
							table: {
								density: 'compact',
							},
						},
					} )
				}
			>
				Mock change DataViews columns
			</button>
			{ header }
			{ data.map( ( item ) => (
				<div key={ String( item.id || item.transaction_id ) }>
					{ fields.map( ( field ) => (
						<div key={ field.id }>
							{ field.render
								? field.render( { item } )
								: String( item[ field.id ] || '' ) }
						</div>
					) ) }
				</div>
			) ) }
		</div>
	),
} ) );

jest.mock( '../../promotions/spotlight', () => ( {
	SpotlightPromotion: () => <div>Spotlight promotion</div>,
} ) );

jest.mock( '../money-movement/data', () => ( {
	getWooPaymentsDisputes: jest.fn(),
	getWooPaymentsCharge: jest.fn(),
	getWooPaymentsPaymentIntent: jest.fn(),
	getWooPaymentsTransaction: jest.fn(),
	getWooPaymentsTransactions: jest.fn(),
	getWooPaymentsTransactionsSummary: jest.fn(),
	getWooPaymentsDisputesSummary: jest.fn(),
	requestWooPaymentsTransactionsExport: jest.fn(),
	getWooPaymentsTransactionsExportUrl: jest.fn(),
	requestWooPaymentsDisputesExport: jest.fn(),
	getWooPaymentsDisputesExportUrl: jest.fn(),
} ) );

const mockGetTransactions = getWooPaymentsTransactions as jest.MockedFunction<
	typeof getWooPaymentsTransactions
>;
const mockGetDisputes = getWooPaymentsDisputes as jest.MockedFunction<
	typeof getWooPaymentsDisputes
>;
const mockGetCharge = getWooPaymentsCharge as jest.MockedFunction<
	typeof getWooPaymentsCharge
>;
const mockGetPaymentIntent = getWooPaymentsPaymentIntent as jest.MockedFunction<
	typeof getWooPaymentsPaymentIntent
>;
const mockGetTransaction = getWooPaymentsTransaction as jest.MockedFunction<
	typeof getWooPaymentsTransaction
>;
const mockGetTransactionsSummary =
	getWooPaymentsTransactionsSummary as jest.MockedFunction<
		typeof getWooPaymentsTransactionsSummary
	>;
const mockGetDisputesSummary =
	getWooPaymentsDisputesSummary as jest.MockedFunction<
		typeof getWooPaymentsDisputesSummary
	>;
const mockRequestTransactionsExport =
	requestWooPaymentsTransactionsExport as jest.MockedFunction<
		typeof requestWooPaymentsTransactionsExport
	>;
const mockGetTransactionsExportUrl =
	getWooPaymentsTransactionsExportUrl as jest.MockedFunction<
		typeof getWooPaymentsTransactionsExportUrl
	>;
const mockRequestDisputesExport =
	requestWooPaymentsDisputesExport as jest.MockedFunction<
		typeof requestWooPaymentsDisputesExport
	>;
const mockGetDisputesExportUrl =
	getWooPaymentsDisputesExportUrl as jest.MockedFunction<
		typeof getWooPaymentsDisputesExportUrl
	>;

describe( 'WooPayments money movement pages', () => {
	let anchorClickSpy: jest.SpyInstance;

	beforeEach( () => {
		anchorClickSpy = jest
			.spyOn( HTMLAnchorElement.prototype, 'click' )
			.mockImplementation();
		window.localStorage.clear();
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockGetTransactions.mockReset();
		mockGetDisputes.mockReset();
		mockGetCharge.mockReset();
		mockGetPaymentIntent.mockReset();
		mockGetTransaction.mockReset();
		mockGetTransactionsSummary.mockReset();
		mockGetDisputesSummary.mockReset();
		mockRequestTransactionsExport.mockReset();
		mockGetTransactionsExportUrl.mockReset();
		mockRequestDisputesExport.mockReset();
		mockGetDisputesExportUrl.mockReset();
	} );

	afterEach( () => {
		anchorClickSpy.mockRestore();
	} );

	it( 'announces loaded transactions and gives row links clear purpose', async () => {
		mockGetTransactions.mockResolvedValue( {
			data: [
				{
					id: 'txn_test',
					type: 'charge',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			total_count: 1,
			total: 5000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter initialEntries={ [ '/woopayments/transactions' ] }>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect( screen.getByText( 'Spotlight promotion' ) ).toBeInTheDocument();

		expect(
			await screen.findByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_test',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Transactions loaded.' )
		).toBeInTheDocument();
	} );

	it( 'builds transaction list links with payment ids and transaction context', async () => {
		mockGetTransactions.mockResolvedValue( {
			data: [
				{
					transaction_id: 'txn_test',
					payment_intent_id: 'pi_test',
					charge_id: 'ch_test',
					type: 'charge',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			total_count: 1,
			total: 5000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter initialEntries={ [ '/woopayments/transactions' ] }>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_test',
			} )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions%2Fdetails&id=pi_test&transaction_id=txn_test&transaction_type=charge'
		);
	} );

	it( 'announces empty transaction results from a stable status region', async () => {
		mockGetTransactions.mockResolvedValue( { data: [] } );
		mockGetTransactionsSummary.mockResolvedValue( {
			total_count: 0,
			total: 0,
			currency: 'usd',
		} );

		render(
			<MemoryRouter initialEntries={ [ '/woopayments/transactions' ] }>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findAllByText( 'No transactions found.' )
		).not.toHaveLength( 0 );
	} );

	it( 'uses URL query state for the transactions request and summary', async () => {
		mockGetTransactions.mockResolvedValue( {
			data: [
				{
					transaction_id: 'txn_loan',
					payment_intent_id: 'pi_loan',
					type: 'charge',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
			total_count: 42,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			total_count: 42,
			total: 5000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?page=2&pagesize=50&sort=amount&direction=asc&loan_id_is=loan_test',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_loan',
			} )
		).toBeInTheDocument();
		expect( mockGetTransactions ).toHaveBeenCalledWith(
			expect.objectContaining( {
				page: 2,
				pagesize: 50,
				sort: 'amount',
				direction: 'asc',
				loan_id_is: 'loan_test',
			} )
		);
		expect( mockGetTransactionsSummary ).toHaveBeenCalledWith(
			expect.objectContaining( {
				loan_id_is: 'loan_test',
			} )
		);
	} );

	it( 'offers searchable transaction exports with the active query', async () => {
		mockGetTransactions.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			total_count: 0,
			total: 0,
			currency: 'usd',
		} );
		mockRequestTransactionsExport.mockResolvedValue( {
			export_id: 'export_test',
		} );
		mockGetTransactionsExportUrl.mockResolvedValue( {
			download_url: 'https://example.com/transactions.csv',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?search=Ada&store_currency_is=usd',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'searchbox', {
				name: 'Search transactions',
			} )
		).toHaveValue( 'Ada' );

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Download transactions' } )
			);
		} );
		expect(
			await screen.findByText(
				'Your transactions export has started downloading.'
			)
		).toHaveAttribute( 'role', 'status' );

		expect( mockRequestTransactionsExport ).toHaveBeenCalledWith(
			expect.objectContaining( {
				search: 'Ada',
				store_currency_is: 'usd',
			} )
		);
		expect( mockGetTransactionsExportUrl ).toHaveBeenCalledWith(
			'export_test'
		);
	} );

	it( 'persists transaction DataViews preferences without changing the REST query', async () => {
		mockGetTransactions.mockResolvedValue( {
			data: [
				{
					transaction_id: 'txn_test',
					payment_intent_id: 'pi_test',
					type: 'charge',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
			total_count: 1,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			total_count: 1,
			total: 5000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/transactions?search=Ada' ] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_test',
			} )
		).toBeInTheDocument();

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', {
					name: 'Mock change DataViews columns',
				} )
			);
		} );

		expect(
			window.localStorage.getItem(
				'woocommerce_woopayments_money_movement_view_transactions'
			)
		).toContain( '"fields":["type","amount"]' );
		expect( mockGetTransactions ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				search: 'Ada',
			} )
		);
		expect( mockGetTransactions ).not.toHaveBeenLastCalledWith(
			expect.objectContaining( {
				fields: expect.anything(),
				layout: expect.anything(),
			} )
		);
	} );

	it( 'announces loaded disputes and routes actionable rows to challenge evidence', async () => {
		mockGetDisputes.mockResolvedValue( {
			data: [
				{
					id: 'dp_test',
					charge_id: 'ch_test',
					reason: 'fraudulent',
					status: 'needs_response',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
				{
					id: 'dp_closed',
					charge_id: 'ch_closed',
					reason: 'fraudulent',
					status: 'won',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
		} );
		mockGetDisputesSummary.mockResolvedValue( {
			total_count: 2,
			total: 10000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter initialEntries={ [ '/woopayments/disputes' ] }>
				<WooPaymentsDisputesPage />
			</MemoryRouter>
		);

		expect( screen.getByText( 'Spotlight promotion' ) ).toBeInTheDocument();

		const challengeLink = await screen.findByRole( 'link', {
			name: 'Respond now to fraudulent dispute dp_test',
		} );
		expect( challengeLink ).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fdisputes%2Fchallenge&id=dp_test'
		);
		expect(
			screen.getByRole( 'link', {
				name: 'View transaction details for Fraudulent dispute dp_closed',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'Disputes loaded.' ) ).toBeInTheDocument();
	} );

	it( 'uses URL query state for disputes and exposes reference-style response actions', async () => {
		mockGetDisputes.mockResolvedValue( {
			data: [
				{
					id: 'dp_test',
					charge_id: 'ch_test',
					reason: 'fraudulent',
					status: 'needs_response',
					date: '2026-06-18',
					evidence_due_by: 1781913600,
					amount: 5000,
					currency: 'usd',
				},
			],
			total_count: 1,
		} );
		mockGetDisputesSummary.mockResolvedValue( {
			total_count: 1,
			total: 5000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/disputes?page=3&pagesize=10&status_is=needs_response&store_currency_is=usd',
				] }
			>
				<WooPaymentsDisputesPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'link', {
				name: 'Respond now to fraudulent dispute dp_test',
			} )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fdisputes%2Fchallenge&id=dp_test'
		);
		expect( mockGetDisputes ).toHaveBeenCalledWith(
			expect.objectContaining( {
				page: 3,
				pagesize: 10,
				status_is: 'needs_response',
				store_currency_is: 'usd',
			} )
		);
		expect( mockGetDisputesSummary ).toHaveBeenCalledWith(
			expect.objectContaining( {
				status_is: 'needs_response',
				store_currency_is: 'usd',
			} )
		);
	} );

	it( 'offers dispute exports with the active query', async () => {
		mockGetDisputes.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );
		mockGetDisputesSummary.mockResolvedValue( {
			total_count: 0,
			total: 0,
			currency: 'usd',
		} );
		mockRequestDisputesExport.mockResolvedValue( {
			export_id: 'export_test',
		} );
		mockGetDisputesExportUrl.mockResolvedValue( {
			download_url: 'https://example.com/disputes.csv',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/disputes?status_is=needs_response',
				] }
			>
				<WooPaymentsDisputesPage />
			</MemoryRouter>
		);

		expect(
			await screen.findAllByText( 'No disputes found.' )
		).not.toHaveLength( 0 );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Download disputes' } )
			);
		} );
		expect(
			await screen.findByText(
				'Your disputes export has started downloading.'
			)
		).toHaveAttribute( 'role', 'status' );

		expect( mockRequestDisputesExport ).toHaveBeenCalledWith(
			expect.objectContaining( {
				status_is: 'needs_response',
			} )
		);
		expect( mockGetDisputesExportUrl ).toHaveBeenCalledWith(
			'export_test'
		);
	} );

	it( 'announces transaction detail loading from a stable status region', () => {
		mockGetTransaction.mockImplementation( () => new Promise( () => {} ) );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading transaction details…'
		);
	} );

	it( 'announces transaction detail errors from a stable alert region', async () => {
		mockGetTransaction.mockRejectedValue( new Error( 'Provider failed' ) );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Provider failed'
		);
	} );

	it( 'loads payment intent details when the route id is a payment intent', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			charge: {
				id: 'ch_test',
				balance_transaction: { id: 'txn_test' },
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
			},
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByText( 'txn_test' ) ).toBeInTheDocument();
		expect( mockGetPaymentIntent ).toHaveBeenCalledWith( 'pi_test' );
		expect( mockGetTransaction ).not.toHaveBeenCalled();
	} );

	it( 'loads charge details when the route id is a charge fallback', async () => {
		mockGetCharge.mockResolvedValue( {
			id: 'ch_test',
			payment_intent: 'pi_test',
			balance_transaction: 'txn_test',
			type: 'charge',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
		} );
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
			},
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=ch_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByText( 'txn_test' ) ).toBeInTheDocument();
		expect( mockGetCharge ).toHaveBeenCalledWith( 'ch_test' );
		expect( mockGetPaymentIntent ).toHaveBeenCalledWith( 'pi_test' );
		expect( mockGetTransaction ).not.toHaveBeenCalled();
	} );
} );
