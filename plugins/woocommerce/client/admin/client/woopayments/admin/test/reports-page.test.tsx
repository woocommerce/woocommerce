/**
 * External dependencies
 */
import {
	fireEvent,
	render,
	screen,
	waitFor,
	within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, useLocation } from 'react-router-dom';
import { speak } from '@wordpress/a11y';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WooPaymentsReportsPage } from '../reports/page';
import {
	getWooPaymentsReportsBalanceSummary,
	getWooPaymentsReportsFees,
	getWooPaymentsReportsFeesExportUrl,
	getWooPaymentsReportsFeesSummary,
	requestWooPaymentsReportsFeesExport,
} from '../reports/data';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '@wordpress/date', () => ( {
	dateI18n: jest.fn( ( format, date ) => `${ format }|${ date }` ),
} ) );

jest.mock( '../reports/data', () => ( {
	getWooPaymentsReportsBalanceSummary: jest.fn(),
	getWooPaymentsReportsFees: jest.fn(),
	getWooPaymentsReportsFeesSummary: jest.fn(),
	requestWooPaymentsReportsFeesExport: jest.fn(),
	getWooPaymentsReportsFeesExportUrl: jest.fn(),
} ) );

const mockDataViews = jest.fn(
	( {
		data = [],
		fields = [],
		header,
		isLoading,
		onChangeView,
		search,
		searchLabel,
		view = {},
	}: {
		data?: Array< Record< string, unknown > >;
		fields?: Array< {
			id: string;
			label: string;
			header?: string;
			filterBy?: unknown;
			render?: ( props: {
				item: Record< string, unknown >;
			} ) => ReactNode;
		} >;
		header?: ReactNode;
		isLoading?: boolean;
		onChangeView?: ( view: Record< string, unknown > ) => void;
		search?: boolean;
		searchLabel?: string;
		view?: Record< string, unknown >;
	} ) => {
		const viewFields = Array.isArray( view.fields )
			? ( view.fields as string[] )
			: fields.map( ( field ) => field.id );
		const visibleFields = fields.filter( ( field ) =>
			viewFields.includes( field.id )
		);

		return (
			<div data-testid="reports-dataviews" aria-busy={ isLoading }>
				{ header }
				{ fields.some(
					( field ) => field.id === 'date' && field.filterBy
				) &&
					onChangeView && (
						<button
							type="button"
							onClick={ () =>
								onChangeView( {
									...view,
									filters: [
										{
											field: 'date',
											operator: 'between',
											value: [
												'2026-04-01',
												'2026-04-30',
											],
										},
									],
								} )
							}
						>
							Apply April date filter
						</button>
					) }
				{ search && searchLabel && (
					<input
						type="search"
						aria-label={ searchLabel }
						value={ String( view.search || '' ) }
						onChange={ ( event ) =>
							onChangeView?.( {
								...view,
								search: event.currentTarget.value,
							} )
						}
					/>
				) }
				{ visibleFields.map( ( field ) => (
					<div key={ field.id }>{ field.header || field.label }</div>
				) ) }
				{ data.map( ( item, index ) => (
					<div
						key={ String(
							item.id || item.transaction_id || index
						) }
					>
						{ visibleFields.map( ( field ) => (
							<div key={ field.id }>
								{ field.render
									? field.render( { item } )
									: String( item[ field.id ] || '' ) }
							</div>
						) ) }
					</div>
				) ) }
			</div>
		);
	}
);

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( props: Record< string, unknown > ) =>
		mockDataViews( props as Parameters< typeof mockDataViews >[ 0 ] ),
} ) );

const mockGetBalanceSummary =
	getWooPaymentsReportsBalanceSummary as jest.MockedFunction<
		typeof getWooPaymentsReportsBalanceSummary
	>;
const mockGetFees = getWooPaymentsReportsFees as jest.MockedFunction<
	typeof getWooPaymentsReportsFees
>;
const mockGetFeesSummary =
	getWooPaymentsReportsFeesSummary as jest.MockedFunction<
		typeof getWooPaymentsReportsFeesSummary
	>;
const mockRequestFeesExport =
	requestWooPaymentsReportsFeesExport as jest.MockedFunction<
		typeof requestWooPaymentsReportsFeesExport
	>;
const mockGetFeesExportUrl =
	getWooPaymentsReportsFeesExportUrl as jest.MockedFunction<
		typeof getWooPaymentsReportsFeesExportUrl
	>;

const LocationProbe = () => {
	const location = useLocation();
	return (
		<div data-testid="location">
			{ location.pathname }
			{ location.search }
		</div>
	);
};

const renderReportsPage = ( initialEntries = [ '/woopayments/reports' ] ) =>
	render(
		<MemoryRouter initialEntries={ initialEntries }>
			<WooPaymentsReportsPage
				now={ new Date( '2026-06-19T12:00:00Z' ) }
			/>
			<LocationProbe />
		</MemoryRouter>
	);

const balanceSummary = {
	currency: 'usd',
	period: {
		start: '2026-06-01T00:00:00Z',
		end: '2026-06-19T23:59:59Z',
	},
	starting_balance: {
		amount: 1000,
	},
	total_charges_captured: {
		amount: 162672,
		count: 8,
	},
	fees: {
		amount: -6064,
	},
	charge_fees: {
		amount: -5958,
	},
	payout_fees: {
		amount: -100,
	},
	reader_fees: {
		amount: -150,
	},
	dispute_fees: {
		amount: -1500,
	},
	fee_refunds: {
		amount: 1644,
	},
	refunds: {
		amount: -21500,
		count: 3,
	},
	refund_failure: {
		amount: -2000,
		count: 1,
	},
	disputes: {
		amount: -4000,
		count: 1,
	},
	financing_payout: {
		amount: 5000,
		count: 1,
	},
	financing_paydown: {
		amount: -500,
		count: 1,
	},
	network_costs: {
		amount: -250,
		count: 1,
	},
	other_adjustments: {
		amount: 750,
		count: 1,
	},
	net_balance_change_in_the_period: {
		amount: 132008,
	},
	payouts: {
		amount: 1102608,
		count: 2,
	},
	ending_balance: {
		amount: 877,
	},
};

const feeRow = {
	transaction_id: 'txn_123',
	date: '2026-06-18 10:11:12',
	payment_method: {
		type: 'card',
	},
	type: 'charge',
	transaction_currency: 'usd',
	amount: 2500,
	deposit_currency: 'usd',
	fees: -120,
	order_id: 99,
	deposit_date: '2026-06-19',
	deposit_id: 'po_123',
};

const waitForNextTick = () =>
	new Promise< void >( ( resolve ) => {
		window.setTimeout( resolve, 0 );
	} );

describe( 'WooPaymentsReportsPage', () => {
	let printSpy: jest.SpyInstance;

	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
			dateFormat: 'F j, Y',
			locale: {
				userLocale: 'en_US',
			},
		};
		(
			window as typeof window & {
				wcpaySettings?: Record< string, unknown >;
			}
		 ).wcpaySettings = {
			accountDefaultCurrency: 'USD',
			currentUserEmail: 'merchant@example.com',
			dateFormat: 'F j, Y',
			timeFormat: 'g:i a',
		};
		printSpy = jest.spyOn( window, 'print' ).mockImplementation();
		mockDataViews.mockClear();
		jest.mocked( recordEvent ).mockClear();
		jest.mocked( speak ).mockClear();
		mockGetBalanceSummary.mockReset();
		mockGetFees.mockReset();
		mockGetFeesSummary.mockReset();
		mockRequestFeesExport.mockReset();
		mockGetFeesExportUrl.mockReset();
		mockGetBalanceSummary.mockImplementation( async () => {
			await waitForNextTick();
			return balanceSummary;
		} );
		mockGetFees.mockImplementation( async () => {
			await waitForNextTick();
			return [ feeRow ];
		} );
		mockGetFeesSummary.mockImplementation( async () => {
			await waitForNextTick();
			return {
				count: 1,
				sources: [ 'card' ],
				types: [ 'charge' ],
			};
		} );
		mockRequestFeesExport.mockResolvedValue( {
			export_id: 'export_123',
		} );
		mockGetFeesExportUrl.mockResolvedValue( {
			status: 'success',
			download_url: 'https://example.com/fees.csv',
		} );
	} );

	afterEach( () => {
		printSpy.mockRestore();
	} );

	it( 'renders the Reports shell, Balance tab, DataViews, and page view tracking', async () => {
		renderReportsPage();

		expect(
			screen.getByRole( 'heading', {
				name: 'Reports',
				hidden: true,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'View your reconciliation reports.' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'tab', { name: 'Balance' } )
		).toHaveAttribute( 'aria-selected', 'true' );
		expect( screen.getByRole( 'tab', { name: 'Fees' } ) ).toHaveAttribute(
			'aria-selected',
			'false'
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading balance report…'
		);

		expect(
			await screen.findByRole( 'heading', { name: 'Balance summary' } )
		).toBeInTheDocument();
		const dataViews = screen.getByTestId( 'reports-dataviews' );
		expect( dataViews ).toBeInTheDocument();
		for ( const label of [
			'Starting balance',
			'Total charges captured',
			'Fees',
			'Charge fees',
			'Payout fees',
			'Reader costs',
			'Dispute fees',
			'Fee refunds',
			'Refunds',
			'Refund failures',
			'Disputes',
			'Financing payout',
			'Financing paydown',
			'Network costs',
			'Other adjustments',
			'Net balance change in the period',
			'Payouts',
			'Ending balance',
		] ) {
			expect(
				within( dataViews ).getByText( label )
			).toBeInTheDocument();
		}
		expect(
			within( dataViews ).queryByText( 'Charges' )
		).not.toBeInTheDocument();
		expect( screen.getByLabelText( 'Date range' ) ).toBeInTheDocument();
		expect( mockGetBalanceSummary ).toHaveBeenCalledWith( {
			date_start: '2026-05-01T00:00:00.000Z',
			date_end: '2026-05-31T23:59:59.999Z',
			currency: 'USD',
		} );
		expect(
			screen.getByRole( 'button', { name: 'Print' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Export' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'status', { name: 'Balance export status' } )
		).toBeEmptyDOMElement();
		expect( recordEvent ).toHaveBeenCalledWith( 'page_view', {
			path: 'payments_reports',
			tab: 'balance',
		} );
		expect( speak ).toHaveBeenCalledWith(
			'18 balance report rows loaded.',
			'polite'
		);
	} );

	it( 'keeps the reference Balance anchor rows visible when activity rows are zero', async () => {
		mockGetBalanceSummary.mockImplementationOnce( async () => {
			await waitForNextTick();
			return {
				currency: 'usd',
				starting_balance: {
					amount: 0,
				},
				total_charges_captured: {
					amount: 0,
					count: 0,
				},
				fees: {
					amount: 0,
				},
				net_balance_change_in_the_period: {
					amount: 0,
				},
				payouts: {
					amount: 0,
					count: 0,
				},
				ending_balance: {
					amount: 0,
				},
			};
		} );

		renderReportsPage();

		expect(
			await screen.findByRole( 'heading', {
				name: 'No balance activity',
			} )
		).toBeInTheDocument();
		const dataViews = screen.getByTestId( 'reports-dataviews' );

		for ( const label of [
			'Starting balance',
			'Total charges captured',
			'Fees',
			'Net balance change in the period',
			'Payouts',
			'Ending balance',
		] ) {
			expect(
				within( dataViews ).getByText( label )
			).toBeInTheDocument();
		}

		for ( const label of [
			'Reader costs',
			'Charge fees',
			'Payout fees',
			'Refund failures',
			'Network costs',
		] ) {
			expect(
				within( dataViews ).queryByText( label )
			).not.toBeInTheDocument();
		}
		expect( speak ).not.toHaveBeenCalled();
	} );

	it( 'lets merchants change the Balance period through the Date range selector', async () => {
		renderReportsPage();

		await screen.findByRole( 'heading', { name: 'Balance summary' } );
		const dateRange = screen.getByLabelText( 'Date range' );

		dateRange.focus();
		fireEvent.change( dateRange, {
			target: {
				value: 'year_to_date',
			},
		} );

		expect( screen.getByLabelText( 'Date range' ) ).toHaveFocus();

		await waitFor( () =>
			expect( mockGetBalanceSummary ).toHaveBeenLastCalledWith( {
				date_start: '2026-01-01T00:00:00.000Z',
				date_end: '2026-06-18T23:59:59.999Z',
				currency: 'USD',
			} )
		);
		expect( screen.getByTestId( 'location' ) ).toHaveTextContent(
			'date_between%5B%5D=2026-01-01'
		);
	} );

	it( 'lets merchants change the Balance period through the DataViews date filter', async () => {
		renderReportsPage();

		await screen.findByRole( 'heading', { name: 'Balance summary' } );
		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Apply April date filter',
			} )
		);

		await waitFor( () =>
			expect( mockGetBalanceSummary ).toHaveBeenLastCalledWith( {
				date_start: '2026-04-01T00:00:00.000Z',
				date_end: '2026-04-30T23:59:59.999Z',
				currency: 'USD',
			} )
		);
		expect( screen.getByTestId( 'location' ) ).toHaveTextContent(
			'date_between%5B%5D=2026-04-01'
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_reports_balance_date_filter_change',
			{
				preset: 'custom',
				range_days: 30,
				is_initial_apply: false,
			}
		);
	} );

	it( 'syncs the active tab to the URL and tracks tab changes', async () => {
		renderReportsPage();

		await screen.findByRole( 'heading', { name: 'Balance summary' } );
		await userEvent.click( screen.getByRole( 'tab', { name: 'Fees' } ) );

		expect( screen.getByTestId( 'location' ) ).toHaveTextContent(
			'/woopayments/reports?report_tab=fees'
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_reports_tab_change',
			{
				from_tab: 'balance',
				to_tab: 'fees',
			}
		);
		expect(
			await screen.findByRole( 'searchbox', { name: 'Search fees' } )
		).toBeInTheDocument();
		expect( mockGetFees ).toHaveBeenCalledWith(
			expect.objectContaining( {
				page: 1,
				per_page: 25,
				sort: 'date',
				direction: 'desc',
				user_timezone: expect.stringMatching( /^[+-]\d{2}:\d{2}$/ ),
			} )
		);
	} );

	it( 'supports keyboard navigation between Reports tabs', async () => {
		renderReportsPage();

		const balanceTab = screen.getByRole( 'tab', { name: 'Balance' } );
		await screen.findByRole( 'heading', { name: 'Balance summary' } );
		balanceTab.focus();
		fireEvent.keyDown( balanceTab, { key: 'ArrowRight' } );

		expect( screen.getByTestId( 'location' ) ).toHaveTextContent(
			'/woopayments/reports?report_tab=fees'
		);
		expect( screen.getByRole( 'tab', { name: 'Fees' } ) ).toHaveFocus();
		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_reports_tab_change',
			{
				from_tab: 'balance',
				to_tab: 'fees',
			}
		);
	} );

	it( 'renders Balance error and empty states with accessible reload controls', async () => {
		mockGetBalanceSummary.mockImplementationOnce( async () => {
			await waitForNextTick();
			throw new Error( 'Balance failed' );
		} );
		renderReportsPage();

		expect(
			await screen.findByRole( 'heading', {
				name: 'Balance report unavailable',
			} )
		).toBeInTheDocument();
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			"We couldn't load your balance report."
		);
		expect(
			screen.getByRole( 'button', { name: 'Reload report' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Apply April date filter' } )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Date range' ) ).toBeInTheDocument();
		expect( speak ).toHaveBeenCalledWith(
			'Balance report could not be loaded.',
			'assertive'
		);

		mockGetBalanceSummary.mockImplementationOnce( async () => {
			await waitForNextTick();
			return {
				currency: 'usd',
				starting_balance: {
					amount: 0,
				},
				ending_balance: {
					amount: 0,
				},
			};
		} );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Reload report' } )
		);

		expect(
			await screen.findByRole( 'heading', {
				name: 'No balance activity',
			} )
		).toBeInTheDocument();
		expect(
			screen
				.getByRole( 'heading', { name: 'No balance activity' } )
				.closest( '[role="status"]' )
		).toHaveTextContent(
			'No balance activity was found for the selected period. Summary rows are shown with zero amounts.'
		);
		expect(
			screen.getByRole( 'button', { name: 'Apply April date filter' } )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Date range' ) ).toBeInTheDocument();
	} );

	it( 'renders Fees states, search, DataViews fields, and export flow', async () => {
		const clickSpy = jest
			.spyOn( HTMLAnchorElement.prototype, 'click' )
			.mockImplementation();

		renderReportsPage( [ '/woopayments/reports?tab=fees' ] );

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading fees report…'
		);
		expect(
			await screen.findByRole( 'searchbox', { name: 'Search fees' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Date & time' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Method' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Gross amount' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Fees total' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'F j, Y / g:i a|2026-06-18T10:11:12Z' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'txn_123' } )
		).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions%2Fdetails&id=txn_123&transaction_type=charge'
		);
		expect( screen.getByRole( 'link', { name: '99' } ) ).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-orders&action=edit&id=99'
		);
		expect( screen.getByText( '$25.00 USD' ) ).toBeInTheDocument();
		expect( screen.getByText( '-$1.20 USD' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'status', { name: 'Fees export status' } )
		).toBeEmptyDOMElement();
		expect( speak ).toHaveBeenCalledWith( '1 fees loaded.', 'polite' );

		fireEvent.change(
			screen.getByRole( 'searchbox', { name: 'Search fees' } ),
			{
				target: {
					value: 'txn_456',
				},
			}
		);

		await waitFor( () =>
			expect( mockGetFees ).toHaveBeenLastCalledWith(
				expect.objectContaining( {
					search: [ 'txn_456' ],
					user_timezone: expect.stringMatching( /^[+-]\d{2}:\d{2}$/ ),
				} )
			)
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_reports_fees_search',
			{
				search_length: 7,
			}
		);

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Export' } )
		);

		expect( mockRequestFeesExport ).toHaveBeenCalledWith(
			expect.objectContaining( {
				search: [ 'txn_456' ],
				user_timezone: expect.stringMatching( /^[+-]\d{2}:\d{2}$/ ),
				user_email: 'merchant@example.com',
				locale: 'en_US',
			} )
		);
		expect( mockGetFeesExportUrl ).toHaveBeenCalledWith( 'export_123' );
		await waitFor( () => expect( clickSpy ).toHaveBeenCalledTimes( 1 ) );
		expect( recordEvent ).toHaveBeenCalledWith( 'wcpay_csv_export_click', {
			row_type: 'fees',
			source: 'payments_reports',
			exported_row_count: 1,
		} );
		expect(
			await screen.findByRole( 'status', {
				name: 'Fees export status',
			} )
		).toHaveTextContent( 'Fees export is ready.' );
		clickSpy.mockRestore();
	} );

	it( 'exposes the Fees date filter through DataViews', async () => {
		renderReportsPage( [ '/woopayments/reports?tab=fees' ] );

		await screen.findByRole( 'searchbox', { name: 'Search fees' } );
		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Apply April date filter',
			} )
		);

		await waitFor( () =>
			expect( mockGetFees ).toHaveBeenLastCalledWith(
				expect.objectContaining( {
					date_between: [ '2026-04-01', '2026-04-30' ],
					user_timezone: expect.stringMatching( /^[+-]\d{2}:\d{2}$/ ),
				} )
			)
		);
		expect( mockGetFeesSummary ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				date_between: [ '2026-04-01', '2026-04-30' ],
				user_timezone: expect.stringMatching( /^[+-]\d{2}:\d{2}$/ ),
			} )
		);
	} );

	it( 'renders Fees error and empty states', async () => {
		mockGetFees.mockImplementationOnce( async () => {
			await waitForNextTick();
			throw new Error( 'Fees failed' );
		} );
		renderReportsPage( [ '/woopayments/reports?tab=fees' ] );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Fees report unavailable',
			} )
		).toBeInTheDocument();
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			"We couldn't load your fees data."
		);
		expect( speak ).toHaveBeenCalledWith(
			'Fees report could not be loaded.',
			'assertive'
		);

		mockGetFees.mockImplementationOnce( async () => {
			await waitForNextTick();
			return [];
		} );
		mockGetFeesSummary.mockImplementationOnce( async () => {
			await waitForNextTick();
			return {
				count: 0,
				sources: [],
				types: [],
			};
		} );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Reload report' } )
		);

		expect(
			await screen.findByRole( 'heading', { name: 'No fees yet' } )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Fees will appear here once you start receiving payments.'
		);
	} );
} );
