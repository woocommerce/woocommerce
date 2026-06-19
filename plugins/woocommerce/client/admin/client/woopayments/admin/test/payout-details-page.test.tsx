/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import { speak } from '@wordpress/a11y';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { WooPaymentsPayouts } from '../payouts';
import { WooPaymentsPayoutDetailsPage } from '../payout-details';
import {
	getWooPaymentsDeposit,
	getWooPaymentsDeposits,
	getWooPaymentsDepositsSummary,
	getWooPaymentsDepositsExportUrl,
	requestWooPaymentsDepositsExport,
} from '../overview/data';
import {
	getWooPaymentsTransactions,
	getWooPaymentsTransactionsSummary,
} from '../money-movement/data';

jest.mock( '../overview/data', () => ( {
	getWooPaymentsDeposit: jest.fn(),
	getWooPaymentsDeposits: jest.fn(),
	getWooPaymentsDepositsSummary: jest.fn(),
	getWooPaymentsDepositsExportUrl: jest.fn(),
	requestWooPaymentsDepositsExport: jest.fn(),
} ) );

jest.mock( '../money-movement/data', () => ( {
	getWooPaymentsTransactions: jest.fn(),
	getWooPaymentsTransactionsSummary: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '../../promotions/spotlight', () => ( {
	SpotlightPromotion: () => <div>Spotlight promotion</div>,
} ) );

const mockDataViews = jest.fn(
	( {
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
		view?: { search?: string };
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
						page: 2,
						perPage: 10,
						search: 'Ada',
						sort: {
							field: 'amount',
							direction: 'asc',
						},
						fields: [ 'date', 'amount' ],
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
	)
);

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( props: Parameters< typeof mockDataViews >[ 0 ] ) =>
		mockDataViews( props ),
} ) );

const mockGetDeposit = getWooPaymentsDeposit as jest.MockedFunction<
	typeof getWooPaymentsDeposit
>;
const mockGetDeposits = getWooPaymentsDeposits as jest.MockedFunction<
	typeof getWooPaymentsDeposits
>;
const mockGetDepositsSummary =
	getWooPaymentsDepositsSummary as jest.MockedFunction<
		typeof getWooPaymentsDepositsSummary
	>;
const mockRequestDepositsExport =
	requestWooPaymentsDepositsExport as jest.MockedFunction<
		typeof requestWooPaymentsDepositsExport
	>;
const mockGetDepositsExportUrl =
	getWooPaymentsDepositsExportUrl as jest.MockedFunction<
		typeof getWooPaymentsDepositsExportUrl
	>;
const mockGetTransactions = getWooPaymentsTransactions as jest.MockedFunction<
	typeof getWooPaymentsTransactions
>;
const mockGetTransactionsSummary =
	getWooPaymentsTransactionsSummary as jest.MockedFunction<
		typeof getWooPaymentsTransactionsSummary
	>;
const mockSpeak = speak as jest.MockedFunction< typeof speak >;

describe( 'WooPayments payout details admin surface', () => {
	let anchorClickSpy: jest.SpyInstance;
	let originalClipboard: Clipboard | undefined;

	beforeEach( () => {
		anchorClickSpy = jest
			.spyOn( HTMLAnchorElement.prototype, 'click' )
			.mockImplementation();
		originalClipboard = navigator.clipboard;
		window.localStorage.clear();
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockGetDeposit.mockReset();
		mockGetDeposits.mockReset();
		mockGetDepositsSummary.mockReset();
		mockRequestDepositsExport.mockReset();
		mockGetDepositsExportUrl.mockReset();
		mockGetTransactions.mockReset();
		mockGetTransactionsSummary.mockReset();
		mockDataViews.mockClear();
		mockSpeak.mockReset();
	} );

	afterEach( () => {
		anchorClickSpy.mockRestore();
		Object.defineProperty( navigator, 'clipboard', {
			configurable: true,
			value: originalClipboard,
		} );
	} );

	it( 'links payout history rows to native payout details', async () => {
		mockGetDeposits.mockResolvedValue( {
			total_count: 1,
			data: [
				{
					id: 'po_test',
					date: '2026-06-18',
					type: 'deposit',
					amount: 12500,
					status: 'paid',
					currency: 'usd',
				},
			],
		} );
		mockGetDepositsSummary.mockResolvedValue( {
			count: 1,
			total: 12500,
			currency: 'usd',
		} );

		render(
			<MemoryRouter initialEntries={ [ '/woopayments/payouts' ] }>
				<WooPaymentsPayouts />
			</MemoryRouter>
		);

		expect( screen.getByText( 'Spotlight promotion' ) ).toBeInTheDocument();

		const detailsLink = await screen.findByRole( 'link', {
			name: 'Jun 18, 2026 - view payout details for po_test',
		} );
		expect( detailsLink ).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts%2Fdetails&id=po_test'
		);
	} );

	it( 'uses URL query state for payout list and summary requests', async () => {
		mockGetDeposits.mockResolvedValue( {
			total_count: 1,
			data: [
				{
					id: 'po_test',
					date: '2026-06-18',
					type: 'deposit',
					amount: 12500,
					status: 'paid',
					currency: 'usd',
				},
			],
		} );
		mockGetDepositsSummary.mockResolvedValue( {
			count: 1,
			total: 12500,
			currency: 'usd',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/payouts?page=4&pagesize=50&sort=amount&direction=asc&search=po_test&status_is=paid&store_currency_is=usd',
				] }
			>
				<WooPaymentsPayouts />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'link', {
				name: 'Jun 18, 2026 - view payout details for po_test',
			} )
		).toBeInTheDocument();
		expect( mockGetDeposits ).toHaveBeenCalledWith(
			expect.objectContaining( {
				page: 4,
				pagesize: 50,
				sort: 'amount',
				direction: 'asc',
				match: 'po_test',
				status_is: 'paid',
				store_currency_is: 'usd',
			} )
		);
		expect( mockGetDepositsSummary ).toHaveBeenCalledWith(
			expect.objectContaining( {
				status_is: 'paid',
				store_currency_is: 'usd',
			} )
		);
	} );

	it( 'offers payout exports with the active query', async () => {
		mockGetDeposits.mockResolvedValue( {
			total_count: 0,
			data: [],
		} );
		mockGetDepositsSummary.mockResolvedValue( {
			count: 0,
			total: 0,
			currency: 'usd',
		} );
		mockRequestDepositsExport.mockResolvedValue( {
			export_id: 'export_test',
		} );
		mockGetDepositsExportUrl.mockResolvedValue( {
			download_url: 'https://example.com/payouts.csv',
		} );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/payouts?status_is=paid&store_currency_is=usd',
				] }
			>
				<WooPaymentsPayouts />
			</MemoryRouter>
		);

		await screen.findByRole( 'status' );
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Download payouts' } )
			);
		} );
		expect(
			await screen.findByText(
				'Your payouts export has started downloading.'
			)
		).toHaveAttribute( 'role', 'status' );

		expect( mockRequestDepositsExport ).toHaveBeenCalledWith(
			expect.objectContaining( {
				status_is: 'paid',
				store_currency_is: 'usd',
			} )
		);
		expect( mockGetDepositsExportUrl ).toHaveBeenCalledWith(
			'export_test'
		);
	} );

	it( 'loads and renders payout detail data', async () => {
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'deposit',
			amount: 12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
			automatic: true,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 3,
			total: 14000,
			fees: 1500,
			net: 12500,
			currency: 'usd',
		} );
		mockGetTransactions.mockResolvedValue( {
			total_count: 1,
			data: [
				{
					transaction_id: 'txn_payout',
					payment_intent_id: 'pi_payout',
					type: 'charge',
					amount: 12500,
					currency: 'usd',
					date: '2026-06-18',
				},
			],
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading payout details…'
		);
		expect(
			screen.getByRole( 'heading', { name: 'Payout details' } )
		).toBeInTheDocument();
		expect(
			await screen.findByText( 'STRIPE TEST BANK **** 6789' )
		).toBeInTheDocument();
		expect( mockGetDeposit ).toHaveBeenCalledWith( 'po_test' );
		expect( mockGetTransactionsSummary ).toHaveBeenCalledWith( {
			deposit_id: 'po_test',
		} );
		expect( mockGetTransactions ).toHaveBeenCalledWith(
			expect.objectContaining( {
				deposit_id: 'po_test',
			} )
		);
		expect(
			screen.getByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_payout',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'REF123' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '$125.00' ) ).toHaveLength( 3 );
		expect( screen.getByText( '3' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Payout details loaded.'
		);
	} );

	it( 'renders normal payout transaction history through DataViews with a deposit-scoped query', async () => {
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'deposit',
			amount: 12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
			automatic: true,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 3,
			total: 14000,
			fees: 1500,
			net: 12500,
			currency: 'usd',
		} );
		mockGetTransactions.mockResolvedValue( {
			total_count: 3,
			data: [
				{
					transaction_id: 'txn_payout',
					payment_intent_id: 'pi_payout',
					type: 'charge',
					amount: 12500,
					currency: 'usd',
					date: '2026-06-18',
				},
			],
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByTestId( 'money-movement-dataviews' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'searchbox', {
				name: 'Search payout transactions',
			} )
		).toBeInTheDocument();
		expect( mockDataViews ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				searchLabel: 'Search payout transactions',
				paginationInfo: {
					totalItems: 3,
					totalPages: 1,
				},
				fields: expect.arrayContaining( [
					expect.objectContaining( { id: 'date' } ),
					expect.objectContaining( { id: 'type' } ),
					expect.objectContaining( { id: 'amount' } ),
				] ),
			} )
		);
		expect( mockGetTransactions ).toHaveBeenLastCalledWith( {
			deposit_id: 'po_test',
			page: 1,
			pagesize: 25,
			sort: 'date',
			direction: 'desc',
		} );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Mock change DataViews columns',
			} )
		);

		await waitFor( () =>
			expect( mockGetTransactions ).toHaveBeenLastCalledWith( {
				deposit_id: 'po_test',
				page: 2,
				pagesize: 10,
				search: 'Ada',
				sort: 'amount',
				direction: 'asc',
			} )
		);
		expect( mockGetTransactionsSummary ).toHaveBeenLastCalledWith( {
			deposit_id: 'po_test',
			search: 'Ada',
		} );
	} );

	it( 'copies the bank reference ID to the clipboard and announces the result', async () => {
		const writeText = jest.fn().mockResolvedValue( undefined );

		Object.defineProperty( navigator, 'clipboard', {
			configurable: true,
			value: {
				writeText,
			},
		} );
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'deposit',
			amount: 12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 0,
			total: 0,
			fees: 0,
			net: 0,
			currency: 'usd',
		} );
		mockGetTransactions.mockResolvedValue( {
			total_count: 0,
			data: [],
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByText( 'REF123' ) ).toBeInTheDocument();

		const copyButton = screen.getByRole( 'button', {
			name: 'Copy bank reference ID to clipboard',
		} );
		await act( async () => {
			await userEvent.click( copyButton );
			await userEvent.click( copyButton );
		} );

		expect( writeText ).toHaveBeenCalledTimes( 2 );
		expect( writeText ).toHaveBeenCalledWith( 'REF123' );
		expect( mockSpeak ).toHaveBeenCalledTimes( 2 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Bank reference ID copied.',
			'polite'
		);
		await waitFor( () =>
			expect( screen.getByRole( 'status' ) ).toHaveTextContent(
				'Bank reference ID copied.'
			)
		);
	} );

	it( 'links to all transactions for a normal payout', async () => {
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'deposit',
			amount: 12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 1,
			total: 12500,
			fees: 0,
			net: 12500,
			currency: 'usd',
		} );
		mockGetTransactions.mockResolvedValue( {
			total_count: 0,
			data: [],
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		const allTransactionsLink = await screen.findByRole( 'link', {
			name: 'View all transactions in this payout',
		} );

		expect( allTransactionsLink ).toHaveAttribute(
			'href',
			expect.stringContaining(
				'admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions'
			)
		);
		expect( allTransactionsLink ).toHaveAttribute(
			'href',
			expect.stringContaining( 'deposit_id=po_test' )
		);
	} );

	it( 'does not render embedded transaction history for instant payouts', async () => {
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'deposit',
			amount: 12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
			automatic: false,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 1,
			total: 12500,
			fees: 0,
			net: 12500,
			currency: 'usd',
		} );
		mockGetTransactions.mockResolvedValue( {
			total_count: 1,
			data: [
				{
					transaction_id: 'txn_payout',
					type: 'charge',
					amount: 12500,
					currency: 'usd',
					date: '2026-06-18',
				},
			],
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByText(
				( _, element ) =>
					element?.textContent ===
					"We're unable to show transaction history on instant payouts. Learn more"
			)
		).toBeInTheDocument();
		expect( mockGetTransactions ).not.toHaveBeenCalled();
		expect(
			screen.queryByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_payout',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'link', {
				name: 'View all transactions in this payout',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'uses withdrawal wording for withdrawal details', async () => {
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'withdrawal',
			amount: -12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 1,
			total: -12500,
			fees: 0,
			net: -12500,
			currency: 'usd',
		} );
		mockGetTransactions.mockResolvedValue( {
			total_count: 0,
			data: [],
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect(
			await screen.findByRole( 'heading', { name: 'Withdrawal details' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Withdrawal ID' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'View all transactions in this withdrawal',
			} )
		).toBeInTheDocument();
	} );

	it( 'announces payout detail errors', async () => {
		mockGetDeposit.mockRejectedValue( new Error( 'Payout unavailable.' ) );
		mockGetTransactionsSummary.mockResolvedValue( {} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Payout unavailable.'
		);
	} );

	it( 'requires a payout ID before loading details', async () => {
		render(
			<MemoryRouter initialEntries={ [ '/woopayments/payouts/details' ] }>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'A payout ID is required.'
		);
		expect( mockGetDeposit ).not.toHaveBeenCalled();
		expect( mockGetTransactionsSummary ).not.toHaveBeenCalled();
	} );
} );
