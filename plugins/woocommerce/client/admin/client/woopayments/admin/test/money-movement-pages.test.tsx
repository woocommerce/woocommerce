/**
 * External dependencies
 */
import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, useNavigate } from 'react-router-dom';

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
	getWooPaymentsTimeline,
	getWooPaymentsTransactions,
	getWooPaymentsTransactionsSummary,
	getWooPaymentsTransactionsExportUrl,
	getWooPaymentsAuthorizations,
	getWooPaymentsAuthorization,
	getWooPaymentsAuthorizationsSummary,
	getWooPaymentsDisputesExportUrl,
	closeWooPaymentsDispute,
	captureWooPaymentsAuthorization,
	cancelWooPaymentsAuthorization,
	requestWooPaymentsDisputesExport,
	requestWooPaymentsTransactionsExport,
	refundWooPaymentsCharge,
} from '../money-movement/data';

const mockCreateSuccessNotice = jest.fn();
const mockCreateErrorNotice = jest.fn();

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );

	return {
		...actual,
		dispatch: jest.fn( ( storeName ) => {
			if ( storeName === 'core/notices' ) {
				return {
					createSuccessNotice: mockCreateSuccessNotice,
					createErrorNotice: mockCreateErrorNotice,
				};
			}

			return actual.dispatch( storeName );
		} ),
	};
} );

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
			label?: ReactNode;
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
			<div role="row">
				{ fields.map( ( field ) => (
					<div key={ field.id } role="columnheader">
						{ field.label }
					</div>
				) ) }
			</div>
			{ data.map( ( item ) => (
				<div
					key={ String(
						item.id ||
							item.transaction_id ||
							item.payment_intent_id ||
							item.order_id
					) }
				>
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
	getWooPaymentsTimeline: jest.fn(),
	getWooPaymentsTransactions: jest.fn(),
	getWooPaymentsTransactionsSummary: jest.fn(),
	getWooPaymentsAuthorizations: jest.fn(),
	getWooPaymentsAuthorization: jest.fn(),
	getWooPaymentsAuthorizationsSummary: jest.fn(),
	captureWooPaymentsAuthorization: jest.fn(),
	cancelWooPaymentsAuthorization: jest.fn(),
	closeWooPaymentsDispute: jest.fn(),
	getWooPaymentsDisputesSummary: jest.fn(),
	requestWooPaymentsTransactionsExport: jest.fn(),
	getWooPaymentsTransactionsExportUrl: jest.fn(),
	requestWooPaymentsDisputesExport: jest.fn(),
	getWooPaymentsDisputesExportUrl: jest.fn(),
	refundWooPaymentsCharge: jest.fn(),
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
const mockGetTimeline = getWooPaymentsTimeline as jest.MockedFunction<
	typeof getWooPaymentsTimeline
>;
const mockGetTransactionsSummary =
	getWooPaymentsTransactionsSummary as jest.MockedFunction<
		typeof getWooPaymentsTransactionsSummary
	>;
const mockGetAuthorizations =
	getWooPaymentsAuthorizations as jest.MockedFunction<
		typeof getWooPaymentsAuthorizations
	>;
const mockGetAuthorization = getWooPaymentsAuthorization as jest.MockedFunction<
	typeof getWooPaymentsAuthorization
>;
const mockGetAuthorizationsSummary =
	getWooPaymentsAuthorizationsSummary as jest.MockedFunction<
		typeof getWooPaymentsAuthorizationsSummary
	>;
const mockCaptureAuthorization =
	captureWooPaymentsAuthorization as jest.MockedFunction<
		typeof captureWooPaymentsAuthorization
	>;
const mockCancelAuthorization =
	cancelWooPaymentsAuthorization as jest.MockedFunction<
		typeof cancelWooPaymentsAuthorization
	>;
const mockRefundCharge = refundWooPaymentsCharge as jest.MockedFunction<
	typeof refundWooPaymentsCharge
>;
const mockCloseDispute = closeWooPaymentsDispute as jest.MockedFunction<
	typeof closeWooPaymentsDispute
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

const RouteChangeButton = ( { to }: { to: string } ) => {
	const navigate = useNavigate();

	return (
		<button type="button" onClick={ () => navigate( to ) }>
			Load another transaction
		</button>
	);
};

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
		mockGetTimeline.mockReset();
		mockGetTransactionsSummary.mockReset();
		mockGetAuthorizations.mockReset();
		mockGetAuthorization.mockReset();
		mockGetAuthorizationsSummary.mockReset();
		mockCaptureAuthorization.mockReset();
		mockCancelAuthorization.mockReset();
		mockRefundCharge.mockReset();
		mockCloseDispute.mockReset();
		mockGetDisputesSummary.mockReset();
		mockRequestTransactionsExport.mockReset();
		mockGetTransactionsExportUrl.mockReset();
		mockRequestDisputesExport.mockReset();
		mockGetDisputesExportUrl.mockReset();
		mockCreateSuccessNotice.mockReset();
		mockCreateErrorNotice.mockReset();
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

	it( 'renders uncaptured authorizations in a separate transactions tab', async () => {
		mockGetAuthorizations.mockResolvedValue( {
			data: [
				{
					payment_intent_id: 'pi_auth',
					charge_id: 'ch_auth',
					order_id: 123,
					created: '2026-06-12T10:30:00Z',
					risk_level: 1,
					amount: 5000,
					currency: 'usd',
					customer_name: 'Ada Lovelace',
					customer_email: 'ada@example.com',
					customer_country: 'US',
				},
			],
			total_count: 1,
		} );
		mockGetAuthorizationsSummary.mockResolvedValue( {
			count: 1,
			total: 5000,
			currency: 'usd',
			all_currencies: [ 'usd' ],
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?tab=uncaptured',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			screen.getByRole( 'link', { name: 'Transactions' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Uncaptured' } )
		).toHaveAttribute( 'aria-current', 'page' );

		expect(
			await screen.findByRole( 'columnheader', {
				name: 'Authorized date',
			} )
		).toBeInTheDocument();
		[
			'Capture by',
			'Order',
			'Risk',
			'Amount',
			'Customer',
			'Actions',
		].forEach( ( label ) => {
			expect(
				screen.getByRole( 'columnheader', { name: label } )
			).toBeInTheDocument();
		} );
		expect(
			screen.getByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Cancel authorization for order #123',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'Ada Lovelace' ) ).toBeInTheDocument();
	} );

	it( 'uses sanitized uncaptured query state and separate DataViews preferences', async () => {
		mockGetAuthorizations.mockResolvedValue( {
			data: [
				{
					payment_intent_id: 'pi_auth',
					order_id: 123,
					created: '2026-06-12T10:30:00Z',
					amount: 5000,
					currency: 'usd',
				},
			],
			total_count: 1,
		} );
		mockGetAuthorizationsSummary.mockResolvedValue( {
			count: 1,
			total: 5000,
			currency: 'usd',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?tab=uncaptured&search=Ada&loan_id_is=loan_test',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'searchbox', {
				name: 'Search uncaptured transactions',
			} )
		).toHaveValue( 'Ada' );

		expect( mockGetAuthorizations ).toHaveBeenCalledWith(
			expect.objectContaining( {
				search: 'Ada',
			} )
		);
		expect( mockGetAuthorizations ).not.toHaveBeenCalledWith(
			expect.objectContaining( {
				loan_id_is: 'loan_test',
			} )
		);

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', {
					name: 'Mock change DataViews columns',
				} )
			);
		} );

		expect(
			window.localStorage.getItem(
				'woocommerce_woopayments_money_movement_view_authorizations'
			)
		).toContain( '"fields":["type","amount"]' );
		expect(
			window.localStorage.getItem(
				'woocommerce_woopayments_money_movement_view_transactions'
			)
		).toBeNull();
	} );

	it( 'keeps authorization capture pending and dispatches a success notice', async () => {
		let resolveCapture: ( value: unknown ) => void = () => undefined;
		const capturePromise = new Promise( ( resolve ) => {
			resolveCapture = resolve;
		} );

		mockGetAuthorizations.mockResolvedValue( {
			data: [
				{
					payment_intent_id: 'pi_auth',
					order_id: 123,
					created: '2026-06-12T10:30:00Z',
					amount: 5000,
					currency: 'usd',
				},
			],
			total_count: 1,
		} );
		mockGetAuthorizationsSummary.mockResolvedValue( {
			count: 1,
			total: 5000,
			currency: 'usd',
		} );
		mockCaptureAuthorization.mockReturnValueOnce(
			capturePromise as Promise< never >
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?tab=uncaptured',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		);

		expect(
			await screen.findByRole( 'button', {
				name: 'Capturing authorization for order #123',
			} )
		).toBeDisabled();

		await act( async () => {
			resolveCapture( {
				id: 'pi_auth',
				status: 'succeeded',
			} );
			await capturePromise;
			await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		} );

		await waitFor( () =>
			expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
				'Payment for order #123 captured successfully.'
			)
		);
		expect( mockCaptureAuthorization ).toHaveBeenCalledWith(
			123,
			'pi_auth'
		);
	} );

	it( 'reloads uncaptured summary data after a successful authorization action', async () => {
		mockGetAuthorizations
			.mockResolvedValueOnce( {
				data: [
					{
						payment_intent_id: 'pi_auth',
						order_id: 123,
						created: '2026-06-12T10:30:00Z',
						amount: 5000,
						currency: 'usd',
					},
				],
				total_count: 1,
			} )
			.mockResolvedValueOnce( {
				data: [],
				total_count: 0,
			} );
		mockGetAuthorizationsSummary
			.mockResolvedValueOnce( {
				count: 1,
				total: 5000,
				currency: 'usd',
			} )
			.mockResolvedValueOnce( {
				count: 0,
				total: 0,
				currency: 'usd',
			} );
		mockCaptureAuthorization.mockResolvedValueOnce( {
			id: 'pi_auth',
			status: 'succeeded',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?tab=uncaptured',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByText( '1 uncaptured transactions' )
		).toBeInTheDocument();
		expect( screen.getAllByText( '$50.00' ) ).not.toHaveLength( 0 );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		);

		await waitFor( () => {
			expect( mockGetAuthorizations ).toHaveBeenCalledTimes( 2 );
			expect( mockGetAuthorizationsSummary ).toHaveBeenCalledTimes( 2 );
		} );
		expect(
			await screen.findByText( '0 uncaptured transactions' )
		).toBeInTheDocument();
		expect( screen.getByText( '$0.00' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'dispatches an error notice when canceling an authorization fails', async () => {
		mockGetAuthorizations.mockResolvedValue( {
			data: [
				{
					payment_intent_id: 'pi_auth',
					order_id: 123,
					created: '2026-06-12T10:30:00Z',
					amount: 5000,
					currency: 'usd',
				},
			],
			total_count: 1,
		} );
		mockGetAuthorizationsSummary.mockResolvedValue( {
			count: 1,
			total: 5000,
			currency: 'usd',
		} );
		mockCancelAuthorization.mockRejectedValueOnce(
			new Error( 'Authorization already canceled.' )
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions?tab=uncaptured',
				] }
			>
				<WooPaymentsTransactionsPage />
			</MemoryRouter>
		);

		const cancelButton = await screen.findByRole( 'button', {
			name: 'Cancel authorization for order #123',
		} );

		await act( async () => {
			await userEvent.click( cancelButton );
		} );

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Unable to cancel authorization for order #123. Authorization already canceled.'
			)
		);
		expect( mockCancelAuthorization ).toHaveBeenCalledWith(
			123,
			'pi_auth'
		);
	} );

	it( 'announces loaded disputes and routes actionable rows to transaction details', async () => {
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
			name: 'Respond now to fraudulent dispute dp_test from transaction details',
		} );
		expect( challengeLink ).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions%2Fdetails&id=ch_test'
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
				name: 'Respond now to fraudulent dispute dp_test from transaction details',
			} )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions%2Fdetails&id=ch_test'
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
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_test',
				balance_transaction: {
					id: 'txn_test',
					fee: 180,
					net: 4820,
				},
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				billing_details: {
					email: 'ada@example.com',
					name: 'Ada Lovelace',
				},
				payment_method_details: {
					type: 'card',
					card: {
						brand: 'visa',
						last4: '4242',
					},
				},
				outcome: {
					risk_level: 'normal',
				},
				order: {
					id: 123,
					number: '123',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( {
			data: [
				{
					type: 'captured',
					message: 'Payment captured.',
					created: 1781712060,
				},
			],
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

		expect(
			await screen.findByRole( 'heading', { name: 'Payment details' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'pi_test' ) ).toBeInTheDocument();
		expect( screen.getByText( 'ch_test' ) ).toBeInTheDocument();
		expect( screen.getByText( 'txn_test' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Ada Lovelace' ) ).toBeInTheDocument();
		expect( screen.getByText( 'ada@example.com' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Order #123' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Visa ending in 4242' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Normal' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '$50.00' ).length ).toBeGreaterThan( 0 );
		expect( screen.getByText( '$1.80' ) ).toBeInTheDocument();
		expect( screen.getByText( '$48.20' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Payment captured.' ) ).toBeInTheDocument();
		expect( mockGetPaymentIntent ).toHaveBeenCalledWith( 'pi_test' );
		expect( mockGetTimeline ).toHaveBeenCalledWith( 'pi_test' );
		expect( mockGetTransaction ).not.toHaveBeenCalled();
	} );

	it( 'opens the full refund modal from transaction details and reloads after a successful refund', async () => {
		const refundablePaymentIntent = {
			id: 'pi_refund',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_refund',
				payment_intent: 'pi_refund',
				balance_transaction: 'txn_refund',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				captured: true,
				amount_refunded: 0,
				refunded: false,
				order: {
					id: 123,
					number: '123',
					url: 'http://example.com/wp-admin/post.php?post=123&action=edit',
				},
			},
		};
		mockGetPaymentIntent
			.mockResolvedValueOnce( refundablePaymentIntent )
			.mockResolvedValueOnce( {
				...refundablePaymentIntent,
				charge: {
					...refundablePaymentIntent.charge,
					amount_refunded: 5000,
					refunded: true,
				},
			} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockRefundCharge.mockResolvedValue( {
			id: 555,
			order_id: 123,
			amount: '50.00',
			reason: '',
			status: 'completed',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_refund&transaction_id=txn_refund',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const refundActionsButton = await screen.findByRole( 'button', {
			name: 'Transaction actions',
		} );
		await act( async () => {
			await userEvent.click( refundActionsButton );
		} );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'menuitem', { name: 'Refund in full' } )
			);
		} );

		const dialog = await screen.findByRole( 'dialog', {
			name: 'Refund transaction',
		} );
		expect(
			within( dialog ).getByText( ( _content, element ) => {
				return (
					element?.tagName.toLowerCase() === 'p' &&
					element.textContent ===
						'This will issue a full refund of $50.00 to the customer.'
				);
			} )
		).toBeInTheDocument();
		expect(
			within( dialog ).getByRole( 'link', { name: 'Go to the order' } )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/post.php?post=123&action=edit'
		);
		await act( async () => {
			await userEvent.click( within( dialog ).getByLabelText( 'Other' ) );
		} );
		await act( async () => {
			await userEvent.click(
				within( dialog ).getByRole( 'button', {
					name: 'Refund transaction',
				} )
			);
		} );

		await waitFor( () =>
			expect( mockRefundCharge ).toHaveBeenCalledWith( {
				chargeId: 'ch_refund',
				amount: 5000,
				reason: null,
				orderId: 123,
			} )
		);
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Refunded payment #pi_refund.'
		);
		expect( mockGetPaymentIntent ).toHaveBeenCalledTimes( 2 );
		await waitFor( () =>
			expect(
				screen.queryByRole( 'dialog', { name: 'Refund transaction' } )
			).not.toBeInTheDocument()
		);
		await waitFor( () =>
			expect(
				screen.getByRole( 'heading', { name: 'Payment details' } )
			).toHaveFocus()
		);
	} );

	it( 'keeps partial refund navigation available when a full refund is no longer available', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_partial_refund',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_partial_refund',
				payment_intent: 'pi_partial_refund',
				balance_transaction: 'txn_partial_refund',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				captured: true,
				amount_refunded: 1000,
				refunded: false,
				order: {
					id: 123,
					number: '123',
					url: 'http://example.com/wp-admin/post.php?post=123&action=edit',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_partial_refund&transaction_id=txn_partial_refund',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const refundActionsButton = await screen.findByRole( 'button', {
			name: 'Transaction actions',
		} );
		await act( async () => {
			await userEvent.click( refundActionsButton );
		} );

		expect(
			screen.queryByRole( 'menuitem', { name: 'Refund in full' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'menuitem', { name: 'Partial refund' } )
		).toBeInTheDocument();
	} );

	it( 'does not offer transaction detail refunds when the charge is not order-backed', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_no_order',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_no_order',
				payment_intent: 'pi_no_order',
				balance_transaction: 'txn_no_order',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				captured: true,
				amount_refunded: 0,
				refunded: false,
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_no_order&transaction_id=txn_no_order',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'heading', { name: 'Payment details' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Transaction actions' } )
		).not.toBeInTheDocument();
	} );

	it( 'derives refund order ids from native order URLs when the detail payload omits the order id', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_order_url',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_order_url',
				payment_intent: 'pi_order_url',
				balance_transaction: 'txn_order_url',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				captured: true,
				amount_refunded: 0,
				refunded: false,
				order: {
					number: '123',
					url: 'http://example.com/wp-admin/admin.php?page=wc-orders&action=edit&id=123',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_order_url&transaction_id=txn_order_url',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const refundActionsButton = await screen.findByRole( 'button', {
			name: 'Transaction actions',
		} );
		await act( async () => {
			await userEvent.click( refundActionsButton );
		} );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'menuitem', { name: 'Refund in full' } )
			);
		} );
		await act( async () => {
			await userEvent.click(
				await screen.findByLabelText( 'Requested by customer' )
			);
		} );
		const refundButton = await screen.findByRole( 'button', {
			name: 'Refund transaction',
		} );
		await act( async () => {
			await userEvent.click( refundButton );
		} );

		await waitFor( () =>
			expect( mockRefundCharge ).toHaveBeenCalledWith(
				expect.objectContaining( {
					orderId: 123,
					reason: 'requested_by_customer',
				} )
			)
		);
	} );

	it( 'warns when a full refund will close an open inquiry', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_inquiry_refund',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_inquiry_refund',
				payment_intent: 'pi_inquiry_refund',
				balance_transaction: 'txn_inquiry_refund',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				captured: true,
				amount_refunded: 0,
				refunded: false,
				order: {
					id: 123,
					number: '123',
					url: 'http://example.com/wp-admin/post.php?post=123&action=edit',
				},
				dispute: {
					id: 'du_inquiry',
					status: 'warning_needs_response',
					reason: 'fraudulent',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_inquiry_refund&transaction_id=txn_inquiry_refund',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const refundActionsButton = await screen.findByRole( 'button', {
			name: 'Transaction actions',
		} );
		await act( async () => {
			await userEvent.click( refundActionsButton );
		} );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'menuitem', { name: 'Refund in full' } )
			);
		} );

		expect(
			await screen.findByText(
				'Issuing a refund will close the inquiry, returning the amount in question back to the cardholder. No additional fees apply.'
			)
		).toBeInTheDocument();
	} );

	it( 'keeps the refund modal open and dispatches an error notice when refunding fails', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_refund_error',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_refund_error',
				payment_intent: 'pi_refund_error',
				balance_transaction: 'txn_refund_error',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				captured: true,
				amount_refunded: 0,
				refunded: false,
				order: {
					id: 123,
					number: '123',
					url: 'http://example.com/wp-admin/post.php?post=123&action=edit',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockRefundCharge.mockRejectedValue( new Error( 'Gateway failed.' ) );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_refund_error&transaction_id=txn_refund_error',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const refundActionsButton = await screen.findByRole( 'button', {
			name: 'Transaction actions',
		} );
		await act( async () => {
			await userEvent.click( refundActionsButton );
		} );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'menuitem', { name: 'Refund in full' } )
			);
		} );
		const refundButton = await screen.findByRole( 'button', {
			name: 'Refund transaction',
		} );
		await act( async () => {
			await userEvent.click( refundButton );
		} );

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'There has been an error refunding the payment #pi_refund_error. Please try again later. Gateway failed.'
			)
		);
		expect(
			screen.getByRole( 'dialog', { name: 'Refund transaction' } )
		).toBeInTheDocument();
		expect( mockGetPaymentIntent ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'captures an uncaptured authorization from transaction details and reloads the detail data', async () => {
		mockGetPaymentIntent
			.mockResolvedValueOnce( {
				id: 'pi_auth',
				status: 'requires_capture',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				charge: {
					id: 'ch_auth',
					balance_transaction: 'txn_auth',
					type: 'charge',
					amount: 5000,
					currency: 'usd',
					created: 1781712000,
					payment_intent: 'pi_auth',
					captured: false,
					amount_refunded: 0,
					order: {
						id: 123,
						number: '123',
					},
				},
			} )
			.mockResolvedValueOnce( {
				id: 'pi_auth',
				status: 'succeeded',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				charge: {
					id: 'ch_auth',
					balance_transaction: 'txn_auth',
					type: 'charge',
					amount: 5000,
					currency: 'usd',
					created: 1781712000,
					payment_intent: 'pi_auth',
					captured: true,
					amount_refunded: 0,
					order: {
						id: 123,
						number: '123',
					},
				},
			} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_auth',
			order_id: 123,
			captured: false,
			created: '2026-06-12T10:30:00Z',
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockResolvedValue( {
			id: 'pi_auth',
			status: 'succeeded',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const captureButton = await screen.findByRole( 'button', {
			name: 'Capture authorization for order #123',
		} );

		await act( async () => {
			await userEvent.click( captureButton );
		} );

		await waitFor( () =>
			expect( mockCaptureAuthorization ).toHaveBeenCalledWith(
				123,
				'pi_auth'
			)
		);
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Payment for order #123 captured successfully.'
		);
		expect( mockGetPaymentIntent ).toHaveBeenCalledTimes( 2 );
		expect( mockGetAuthorization ).toHaveBeenCalledTimes( 1 );
		expect(
			screen.queryByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		).not.toBeInTheDocument();
		await waitFor( () =>
			expect(
				screen.getByRole( 'heading', { name: 'Payment details' } )
			).toHaveFocus()
		);
	} );

	it( 'keeps transaction detail capture pending while the authorization request is running', async () => {
		let resolveCapture: ( value: unknown ) => void = () => undefined;
		const capturePromise = new Promise( ( resolve ) => {
			resolveCapture = resolve;
		} );

		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_auth',
			status: 'requires_capture',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_auth',
				balance_transaction: 'txn_auth',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_auth',
				captured: false,
				amount_refunded: 0,
				order: {
					id: 123,
					number: '123',
				},
			},
		} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_auth',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockReturnValueOnce(
			capturePromise as Promise< never >
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		);

		const pendingCaptureButton = await screen.findByRole( 'button', {
			name: 'Capturing authorization for order #123',
		} );
		expect( pendingCaptureButton ).not.toBeDisabled();
		expect( pendingCaptureButton ).toHaveAttribute(
			'aria-disabled',
			'true'
		);
		expect( pendingCaptureButton ).toHaveFocus();

		await act( async () => {
			resolveCapture( {
				id: 'pi_auth',
				status: 'succeeded',
			} );
			await capturePromise;
		} );
	} );

	it( 'does not overwrite a newer transaction detail route after a pending authorization action completes', async () => {
		let resolveCapture: ( value: unknown ) => void = () => undefined;
		const capturePromise = new Promise( ( resolve ) => {
			resolveCapture = resolve;
		} );

		mockGetPaymentIntent
			.mockResolvedValueOnce( {
				id: 'pi_auth',
				status: 'requires_capture',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				charge: {
					id: 'ch_auth',
					balance_transaction: 'txn_auth',
					type: 'charge',
					amount: 5000,
					currency: 'usd',
					created: 1781712000,
					payment_intent: 'pi_auth',
					captured: false,
					amount_refunded: 0,
					order: {
						id: 123,
						number: '123',
					},
				},
			} )
			.mockResolvedValueOnce( {
				id: 'pi_other',
				status: 'succeeded',
				amount: 9900,
				currency: 'usd',
				created: 1781712100,
				charge: {
					id: 'ch_other',
					balance_transaction: 'txn_other',
					type: 'charge',
					amount: 9900,
					currency: 'usd',
					created: 1781712100,
					payment_intent: 'pi_other',
					captured: true,
					order: {
						id: 456,
						number: '456',
					},
				},
			} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_auth',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockReturnValueOnce(
			capturePromise as Promise< never >
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
				] }
			>
				<RouteChangeButton to="/woopayments/transactions/details?id=pi_other&transaction_id=txn_other" />
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Load another transaction' } )
		);

		expect( await screen.findByText( 'pi_other' ) ).toBeInTheDocument();

		await act( async () => {
			resolveCapture( {
				id: 'pi_auth',
				status: 'succeeded',
			} );
			await capturePromise;
			await Promise.resolve();
		} );

		expect( screen.getByText( 'pi_other' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'pi_auth' ) ).not.toBeInTheDocument();
		expect( mockGetPaymentIntent ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'surfaces authorization action failures after navigating to another detail route', async () => {
		let rejectCapture: ( error: Error ) => void = () => undefined;
		const capturePromise = new Promise( ( resolve, reject ) => {
			rejectCapture = reject;
		} );

		mockGetPaymentIntent
			.mockResolvedValueOnce( {
				id: 'pi_auth',
				status: 'requires_capture',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				charge: {
					id: 'ch_auth',
					balance_transaction: 'txn_auth',
					type: 'charge',
					amount: 5000,
					currency: 'usd',
					created: 1781712000,
					payment_intent: 'pi_auth',
					captured: false,
					amount_refunded: 0,
					order: {
						id: 123,
						number: '123',
					},
				},
			} )
			.mockResolvedValueOnce( {
				id: 'pi_other',
				status: 'succeeded',
				amount: 9900,
				currency: 'usd',
				created: 1781712100,
				charge: {
					id: 'ch_other',
					balance_transaction: 'txn_other',
					type: 'charge',
					amount: 9900,
					currency: 'usd',
					created: 1781712100,
					payment_intent: 'pi_other',
					captured: true,
					order: {
						id: 456,
						number: '456',
					},
				},
			} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_auth',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockReturnValueOnce(
			capturePromise as Promise< never >
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
				] }
			>
				<RouteChangeButton to="/woopayments/transactions/details?id=pi_other&transaction_id=txn_other" />
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Load another transaction' } )
		);
		expect( await screen.findByText( 'pi_other' ) ).toBeInTheDocument();

		await act( async () => {
			rejectCapture( new Error( 'Authorization already captured.' ) );
			await capturePromise.catch( () => undefined );
		} );

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Unable to capture authorization for order #123. Authorization already captured.'
			)
		);
		expect( screen.getByText( 'pi_other' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'pi_auth' ) ).not.toBeInTheDocument();
	} );

	it( 'does not steal focus after authorization action when focus moved while pending', async () => {
		let resolveCapture: ( value: unknown ) => void = () => undefined;
		const capturePromise = new Promise( ( resolve ) => {
			resolveCapture = resolve;
		} );

		mockGetPaymentIntent
			.mockResolvedValueOnce( {
				id: 'pi_auth',
				status: 'requires_capture',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				charge: {
					id: 'ch_auth',
					balance_transaction: 'txn_auth',
					type: 'charge',
					amount: 5000,
					currency: 'usd',
					created: 1781712000,
					payment_intent: 'pi_auth',
					captured: false,
					amount_refunded: 0,
					order: {
						id: 123,
						number: '123',
					},
				},
			} )
			.mockResolvedValueOnce( {
				id: 'pi_auth',
				status: 'succeeded',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				charge: {
					id: 'ch_auth',
					balance_transaction: 'txn_auth',
					type: 'charge',
					amount: 5000,
					currency: 'usd',
					created: 1781712000,
					payment_intent: 'pi_auth',
					captured: true,
					amount_refunded: 0,
					order: {
						id: 123,
						number: '123',
					},
				},
			} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_auth',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockReturnValueOnce(
			capturePromise as Promise< never >
		);

		render(
			<>
				<button type="button">Outside focus target</button>
				<MemoryRouter
					initialEntries={ [
						'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
					] }
				>
					<WooPaymentsTransactionDetailsPage />
				</MemoryRouter>
			</>
		);

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		);
		const outsideFocusTarget = screen.getByRole( 'button', {
			name: 'Outside focus target',
		} );
		outsideFocusTarget.focus();
		expect( outsideFocusTarget ).toHaveFocus();

		await act( async () => {
			resolveCapture( {
				id: 'pi_auth',
				status: 'succeeded',
			} );
			await capturePromise;
		} );

		await waitFor( () =>
			expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
				'Payment for order #123 captured successfully.'
			)
		);
		expect( outsideFocusTarget ).toHaveFocus();
	} );

	it( 'surfaces transaction detail authorization action failures', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_auth',
			status: 'requires_capture',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_auth',
				balance_transaction: 'txn_auth',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_auth',
				captured: false,
				amount_refunded: 0,
				order: {
					id: 123,
					number: '123',
				},
			},
		} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_auth',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockRejectedValue(
			new Error( 'Authorization already captured.' )
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const captureButton = await screen.findByRole( 'button', {
			name: 'Capture authorization for order #123',
		} );

		await act( async () => {
			await userEvent.click( captureButton );
			await Promise.resolve();
		} );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', {
					name: 'Capture authorization for order #123',
				} )
			).toBeEnabled()
		);
		expect(
			screen.getByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		).toHaveFocus();

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Unable to capture authorization for order #123. Authorization already captured.'
			)
		);
	} );

	it( 'surfaces authorization detail load failures for otherwise capturable transactions', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_auth',
			status: 'requires_capture',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_auth',
				balance_transaction: 'txn_auth',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_auth',
				captured: false,
				amount_refunded: 0,
				order: {
					id: 123,
					number: '123',
				},
			},
		} );
		mockGetAuthorization.mockRejectedValue( new Error( 'Auth API down.' ) );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_auth&transaction_id=txn_auth',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByText( 'Auth API down.', {
				selector: '.woocommerce-woopayments-money-movement__status',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Capture authorization for order #123',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'approves a fraud-review transaction from transaction details', async () => {
		let resolveCapture: ( value: unknown ) => void = () => undefined;
		const capturePromise = new Promise( ( resolve ) => {
			resolveCapture = resolve;
		} );

		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_review',
			status: 'requires_capture',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_review',
				balance_transaction: 'txn_review',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_review',
				captured: false,
				amount_refunded: 0,
				order: {
					id: 123,
					number: '123',
					fraud_meta_box_type: 'review',
				},
			},
		} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_review',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCaptureAuthorization.mockReturnValueOnce(
			capturePromise as Promise< never >
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_review&transaction_id=txn_review',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'button', { name: 'Block transaction' } )
		).toBeInTheDocument();
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Approve transaction' } )
		);

		await waitFor( () =>
			expect( mockCaptureAuthorization ).toHaveBeenCalledWith(
				123,
				'pi_review'
			)
		);
		const pendingApproveButton = await screen.findByRole( 'button', {
			name: 'Approving transaction for order #123',
		} );
		expect( pendingApproveButton ).not.toBeDisabled();
		expect( pendingApproveButton ).toHaveAttribute(
			'aria-disabled',
			'true'
		);
		expect( pendingApproveButton ).toHaveFocus();

		await act( async () => {
			resolveCapture( {
				id: 'pi_review',
				status: 'succeeded',
			} );
			await capturePromise;
		} );

		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Payment for order #123 captured successfully.'
		);
	} );

	it( 'blocks a fraud-review transaction from transaction details', async () => {
		let resolveCancel: ( value: unknown ) => void = () => undefined;
		const cancelPromise = new Promise( ( resolve ) => {
			resolveCancel = resolve;
		} );

		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_review',
			status: 'requires_capture',
			charge: {
				id: 'ch_review',
				balance_transaction: 'txn_review',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_review',
				captured: false,
				amount_refunded: 0,
				order: {
					id: 123,
					number: '123',
					fraud_meta_box_type: 'review',
				},
			},
		} );
		mockGetAuthorization.mockResolvedValue( {
			payment_intent_id: 'pi_review',
			order_id: 123,
			captured: false,
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCancelAuthorization.mockReturnValueOnce(
			cancelPromise as Promise< never >
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_review&transaction_id=txn_review',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Block transaction' } )
		);

		await waitFor( () =>
			expect( mockCancelAuthorization ).toHaveBeenCalledWith(
				123,
				'pi_review'
			)
		);
		const pendingBlockButton = await screen.findByRole( 'button', {
			name: 'Blocking transaction for order #123',
		} );
		expect( pendingBlockButton ).not.toBeDisabled();
		expect( pendingBlockButton ).toHaveAttribute( 'aria-disabled', 'true' );
		expect( pendingBlockButton ).toHaveFocus();

		await act( async () => {
			resolveCancel( {
				id: 'pi_review',
				status: 'canceled',
			} );
			await cancelPromise;
		} );

		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Payment for order #123 canceled successfully.'
		);
	} );

	it( 'renders awaiting-response dispute decisions from transaction details', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_test',
				balance_transaction: {
					id: 'txn_test',
					fee: 180,
					net: 4820,
				},
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				billing_details: {
					email: 'ada@example.com',
					name: 'Ada Lovelace',
				},
				payment_method_details: {
					type: 'card',
					card: {
						brand: 'visa',
						last4: '4242',
					},
				},
				dispute: {
					id: 'dp_test',
					status: 'needs_response',
					reason: 'fraudulent',
					evidence_details: {
						due_by: 1781913600,
					},
					amount: 5000,
					currency: 'usd',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'heading', { name: 'Dispute details' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Response needed' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Challenge dispute' } )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fdisputes%2Fchallenge&id=dp_test'
		);
		expect(
			screen.getByRole( 'button', { name: 'Accept dispute' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'Learn more about responding to disputes',
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/fraud-and-disputes/managing-disputes/#responding'
		);
	} );

	it( 'accepts a dispute from the transaction detail decision layer', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status: 'needs_response',
					reason: 'fraudulent',
					amount: 5000,
					currency: 'usd',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCloseDispute.mockResolvedValue( {
			id: 'dp_test',
			status: 'lost',
			reason: 'fraudulent',
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

		const acceptButton = await screen.findByRole( 'button', {
			name: 'Accept dispute',
		} );

		await act( async () => {
			await userEvent.click( acceptButton );
		} );
		expect(
			screen.getByRole( 'heading', { name: 'Accept the dispute?' } )
		).toBeInTheDocument();
		const acceptDialog = screen.getByRole( 'dialog' );

		await act( async () => {
			await userEvent.click(
				within( acceptDialog ).getByRole( 'button', {
					name: 'Accept dispute',
				} )
			);
		} );

		await waitFor( () =>
			expect( mockCloseDispute ).toHaveBeenCalledWith( 'dp_test' )
		);
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Dispute accepted.'
		);
		expect( await screen.findByText( 'Lost' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Dispute details' } )
		).toHaveFocus();
	} );

	it( 'does not steal focus after accepting a dispute when the modal was dismissed while pending', async () => {
		let resolveCloseDispute: ( value: {
			id: string;
			status: string;
			reason: string;
		} ) => void = () => {};
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status: 'needs_response',
					reason: 'fraudulent',
					amount: 5000,
					currency: 'usd',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCloseDispute.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveCloseDispute = resolve;
			} )
		);

		render(
			<>
				<button type="button">Outside focus target</button>
				<MemoryRouter
					initialEntries={ [
						'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
					] }
				>
					<WooPaymentsTransactionDetailsPage />
				</MemoryRouter>
			</>
		);

		const acceptButton = await screen.findByRole( 'button', {
			name: 'Accept dispute',
		} );

		await act( async () => {
			await userEvent.click( acceptButton );
		} );
		const acceptDialog = screen.getByRole( 'dialog' );
		await act( async () => {
			await userEvent.click(
				within( acceptDialog ).getByRole( 'button', {
					name: 'Accept dispute',
				} )
			);
		} );
		await waitFor( () =>
			expect( mockCloseDispute ).toHaveBeenCalledWith( 'dp_test' )
		);

		await act( async () => {
			await userEvent.click(
				within( acceptDialog ).getByRole( 'button', {
					name: 'Cancel',
				} )
			);
		} );
		const outsideFocusTarget = screen.getByRole( 'button', {
			name: 'Outside focus target',
		} );
		outsideFocusTarget.focus();
		expect( outsideFocusTarget ).toHaveFocus();

		await act( async () => {
			resolveCloseDispute( {
				id: 'dp_test',
				status: 'lost',
				reason: 'fraudulent',
			} );
			await Promise.resolve();
		} );

		expect( await screen.findByText( 'Lost' ) ).toBeInTheDocument();
		expect( outsideFocusTarget ).toHaveFocus();
	} );

	it( 'restores focus after accepting a dispute when the pending modal dismiss leaves focus unstable', async () => {
		let resolveCloseDispute: ( value: {
			id: string;
			status: string;
			reason: string;
		} ) => void = () => {};
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			status: 'succeeded',
			amount: 5000,
			currency: 'usd',
			created: 1781712000,
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status: 'needs_response',
					reason: 'fraudulent',
					amount: 5000,
					currency: 'usd',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCloseDispute.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveCloseDispute = resolve;
			} )
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const acceptButton = await screen.findByRole( 'button', {
			name: 'Accept dispute',
		} );

		await act( async () => {
			await userEvent.click( acceptButton );
		} );
		const acceptDialog = screen.getByRole( 'dialog' );
		await act( async () => {
			await userEvent.click(
				within( acceptDialog ).getByRole( 'button', {
					name: 'Accept dispute',
				} )
			);
		} );
		await waitFor( () =>
			expect( mockCloseDispute ).toHaveBeenCalledWith( 'dp_test' )
		);

		await act( async () => {
			await userEvent.click(
				within( acceptDialog ).getByRole( 'button', {
					name: 'Cancel',
				} )
			);
		} );

		await act( async () => {
			resolveCloseDispute( {
				id: 'dp_test',
				status: 'lost',
				reason: 'fraudulent',
			} );
			await Promise.resolve();
		} );

		expect( await screen.findByText( 'Lost' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', { name: 'Dispute details' } )
		).toHaveFocus();
	} );

	it( 'surfaces dispute accept failures from transaction details', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status: 'needs_response',
					reason: 'fraudulent',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );
		mockCloseDispute.mockRejectedValue( new Error( 'Close failed' ) );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const acceptButton = await screen.findByRole( 'button', {
			name: 'Accept dispute',
		} );

		await act( async () => {
			await userEvent.click( acceptButton );
		} );
		const acceptDialog = screen.getByRole( 'dialog' );
		await act( async () => {
			await userEvent.click(
				within( acceptDialog ).getByRole( 'button', {
					name: 'Accept dispute',
				} )
			);
		} );

		await waitFor( () =>
			expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
				'Close failed'
			)
		);
	} );

	it( 'shows inquiry refund guidance without issuing a refund inline', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status: 'warning_needs_response',
					reason: 'product_not_received',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'link', { name: 'Submit evidence' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Issue refund' } )
		).toHaveAttribute( 'aria-disabled', 'true' );
		expect(
			screen.getByRole( 'button', { name: 'Issue refund' } )
		).toHaveAccessibleDescription(
			'Issue the refund from the full refund flow before responding to this inquiry.'
		);
	} );

	it( 'renders resolved dispute guidance and submitted evidence links', async () => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status: 'under_review',
					reason: 'fraudulent',
					metadata: {
						__evidence_submitted_at: '1781712200',
					},
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByText(
				"The customer's bank is reviewing your submitted evidence. This process can take more than 60 days."
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View submitted evidence' } )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fdisputes%2Fchallenge&id=dp_test'
		);
	} );

	it.each( [
		[
			'won',
			'You won this dispute. The disputed amount and dispute fee have been returned to your account.',
		],
		[
			'lost',
			'This dispute was lost. The disputed amount and dispute fee have been deducted from your account.',
		],
	] )( 'renders %s dispute outcome guidance', async ( status, message ) => {
		mockGetPaymentIntent.mockResolvedValue( {
			id: 'pi_test',
			charge: {
				id: 'ch_test',
				balance_transaction: 'txn_test',
				type: 'charge',
				amount: 5000,
				currency: 'usd',
				created: 1781712000,
				payment_intent: 'pi_test',
				dispute: {
					id: 'dp_test',
					status,
					reason: 'fraudulent',
				},
			},
		} );
		mockGetTimeline.mockResolvedValue( { data: [] } );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByText( message ) ).toBeInTheDocument();
	} );

	it( 'renders reference-shaped timeline events with datetime values', async () => {
		const eventDatetime = 1781712200;
		const expectedEventDate = new Date(
			eventDatetime * 1000
		).toLocaleDateString( undefined, {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		} );

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
		mockGetTimeline.mockResolvedValue( {
			data: [
				{
					type: 'fraud_outcome_manual_approve',
					datetime: eventDatetime,
					user: {
						username: 'admin',
					},
				},
			],
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

		expect(
			await screen.findByText( 'Payment was approved by admin' )
		).toBeInTheDocument();
		expect(
			screen.getAllByText( expectedEventDate ).length
		).toBeGreaterThan( 0 );
		expect( mockGetTimeline ).toHaveBeenCalledWith( 'pi_test' );
	} );

	it( 'announces timeline errors through the stable transaction detail status region', async () => {
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
		mockGetTimeline.mockRejectedValue(
			new Error( 'Timeline provider failed.' )
		);

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=pi_test&transaction_id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		const statusRegion = screen.getByRole( 'status' );
		expect( statusRegion ).toHaveTextContent(
			'Loading transaction details…'
		);
		expect( await screen.findByText( 'pi_test' ) ).toBeInTheDocument();
		expect( screen.getByText( 'txn_test' ) ).toBeInTheDocument();
		await waitFor( () =>
			expect( statusRegion ).toHaveTextContent(
				'Timeline provider failed.'
			)
		);
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
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
