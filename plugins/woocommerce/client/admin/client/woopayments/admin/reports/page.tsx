/**
 * External dependencies
 */
import { Button, SelectControl } from '@wordpress/components';
import { speak } from '@wordpress/a11y';
import { dateI18n } from '@wordpress/date';
import { __, sprintf } from '@wordpress/i18n';
import {
	type KeyboardEvent,
	type ReactNode,
	useCallback,
	useEffect,
	useId,
	useMemo,
	useRef,
	useState,
} from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { recordEvent } from '@woocommerce/tracks';
import { getHistory, getNewPath } from '@woocommerce/navigation';

// @ts-expect-error - Use the WordPress-bundled DataViews entry in wp-admin builds.
import { DataViews, type Field, type View } from '@wordpress/dataviews/wp'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import {
	getWooPaymentsReportsBalanceSummary,
	getWooPaymentsReportsFees,
	getWooPaymentsReportsFeesExportUrl,
	getWooPaymentsReportsFeesSummary,
	requestWooPaymentsReportsFeesExport,
} from './data';
import { buildReportsFeesQueryFromView } from './query';
import { ReportState } from './report-state';
import { runWooPaymentsExport } from '../money-movement/export';
import type {
	ReportsBalanceQuery,
	ReportsBalanceSummary,
	ReportsBalanceSummaryRow,
	ReportsFee,
	ReportsFeesQuery,
	ReportsFeesSummary,
	ReportsTab,
} from './types';
import './style.scss';
import { getTransactionDetailsRoute } from '../money-movement/utils';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';

type WooPaymentsReportsPageProps = {
	now?: Date;
};

type AsyncState< T > = {
	data?: T;
	isLoading: boolean;
	error?: Error;
};

type BalanceRow = {
	id: string;
	label: string;
	amount: number;
	count?: number;
	alwaysVisible?: boolean;
};

type BalanceDateFilterValue =
	| {
			operator: 'between';
			value: [ string, string ];
	  }
	| {
			operator: 'on' | 'before' | 'after';
			value: string;
	  };

type BalanceDatePreset =
	| 'last_month'
	| 'month_to_date'
	| 'year_to_date'
	| 'custom';

type DataViewsFilter = {
	field: string;
	operator: string;
	value?: unknown;
};

type GlobalSettings = typeof globalThis & {
	wcpaySettings?: {
		accountDefaultCurrency?: string;
		currentUserEmail?: string;
		dateFormat?: string;
		timeFormat?: string;
	};
	wcSettings?: {
		adminUrl?: string;
		dateFormat?: string;
		locale?: {
			userLocale?: string;
		};
	};
};

const normalizeReportsTab = ( tab: string | null ): ReportsTab =>
	tab === 'fees' ? 'fees' : 'balance';

const getGlobalSettings = () => globalThis as GlobalSettings;

const isSettingsPaymentsSearch = ( search: string ) => {
	const params = new URLSearchParams( search );

	return (
		params.get( 'page' ) === 'wc-settings' &&
		params.get( 'tab' ) === 'checkout'
	);
};

const getReportsTabFromSearch = ( search: string ): ReportsTab => {
	const params = new URLSearchParams( search );
	const nativeTab = params.get( 'report_tab' );

	if ( nativeTab ) {
		return normalizeReportsTab( nativeTab );
	}

	return isSettingsPaymentsSearch( search )
		? 'balance'
		: normalizeReportsTab( params.get( 'tab' ) );
};

const buildReportsRoute = ( search = '' ) =>
	search ? `/woopayments/reports?${ search }` : '/woopayments/reports';

const buildSettingsReportsRoute = ( search = '' ) => {
	const adminPath = getNewPath(
		{ tab: 'checkout' },
		'/woopayments/reports',
		{},
		'wc-settings'
	);

	return search ? `${ adminPath }&${ search }` : adminPath;
};

const navigateReportsRoute = (
	currentSearch: string,
	navigate: ReturnType< typeof useNavigate >,
	search = ''
) => {
	if ( isSettingsPaymentsSearch( currentSearch ) ) {
		getHistory().push( buildSettingsReportsRoute( search ) );
		return;
	}

	navigate( buildReportsRoute( search ) );
};

const formatAmount = ( amount = 0, currency = 'usd' ) =>
	new Intl.NumberFormat( undefined, {
		style: 'currency',
		currency: currency.toUpperCase() || 'USD',
		currencyDisplay: 'narrowSymbol',
	} ).format( amount / 100 );

const formatExplicitAmount = ( amount = 0, currency = 'usd' ) =>
	`${ formatAmount( amount, currency ) } ${ currency.toUpperCase() }`;

const getDateFormat = () =>
	getGlobalSettings().wcpaySettings?.dateFormat ||
	getGlobalSettings().wcSettings?.dateFormat ||
	'F j, Y';

const getTimeFormat = () =>
	getGlobalSettings().wcpaySettings?.timeFormat || 'g:i a';

const getDateTimeIso = ( value?: string | null ) => {
	if ( ! value ) {
		return null;
	}

	if ( value.includes( 'T' ) ) {
		return value.endsWith( 'Z' ) || /[+-]\d{2}:?\d{2}$/.test( value )
			? value
			: `${ value }Z`;
	}

	return `${ value.replace( ' ', 'T' ) }Z`;
};

const formatDateTimeFromString = (
	value?: string | null,
	includeTime = false
) => {
	const iso = getDateTimeIso( value );

	if ( ! iso ) {
		return '-';
	}

	const format = includeTime
		? `${ getDateFormat() } / ${ getTimeFormat() }`
		: getDateFormat();

	return dateI18n( format, iso );
};

const getAdminUrl = () => {
	const adminUrl = getGlobalSettings().wcSettings?.adminUrl || '';
	return adminUrl.endsWith( '/' ) || adminUrl === ''
		? adminUrl
		: `${ adminUrl }/`;
};

const getOrderUrl = ( orderId: ReportsFee[ 'order_id' ] ) =>
	`${ getAdminUrl() }admin.php?page=wc-orders&action=edit&id=${ encodeURIComponent(
		String( orderId ?? '' )
	) }`;

const getTransactionUrl = ( item: ReportsFee ) =>
	getSettingsPaymentsProviderRouteUrl(
		getTransactionDetailsRoute( {
			id: item.payment_id || item.transaction_id,
			transaction_id: item.transaction_id,
			type: item.type,
		} )
	);

const isYmd = ( value: unknown ): value is string =>
	typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test( value );

const parseYmd = ( ymd: string ): [ number, number, number ] =>
	ymd.split( '-' ).map( Number ) as [ number, number, number ];

const toYmdUTC = ( date: Date ): string =>
	[
		date.getUTCFullYear(),
		String( date.getUTCMonth() + 1 ).padStart( 2, '0' ),
		String( date.getUTCDate() ).padStart( 2, '0' ),
	].join( '-' );

const toStartOfDayUTC = ( ymd: string ): string => {
	const [ year, month, day ] = parseYmd( ymd );
	return new Date(
		Date.UTC( year, month - 1, day, 0, 0, 0, 0 )
	).toISOString();
};

const toEndOfDayUTC = ( ymd: string ): string => {
	const [ year, month, day ] = parseYmd( ymd );
	return new Date(
		Date.UTC( year, month - 1, day, 23, 59, 59, 999 )
	).toISOString();
};

const getLatestCompleteDayYmd = ( now: Date ): string =>
	toYmdUTC(
		new Date(
			Date.UTC(
				now.getUTCFullYear(),
				now.getUTCMonth(),
				now.getUTCDate() - 1
			)
		)
	);

const minYmd = ( first: string, second: string ): string =>
	first <= second ? first : second;

const sortYmdRange = ( start: string, end: string ): [ string, string ] =>
	start <= end ? [ start, end ] : [ end, start ];

const capYmdAtLatestCompleteDay = ( ymd: string, now: Date ): string =>
	minYmd( ymd, getLatestCompleteDayYmd( now ) );

const getLastFullCalendarMonthDateFilter = (
	now: Date
): BalanceDateFilterValue => {
	const start = new Date(
		Date.UTC( now.getUTCFullYear(), now.getUTCMonth() - 1, 1 )
	);
	const end = new Date(
		Date.UTC( now.getUTCFullYear(), now.getUTCMonth(), 0 )
	);

	return {
		operator: 'between',
		value: [ toYmdUTC( start ), toYmdUTC( end ) ],
	};
};

const getMonthToDateFilter = ( now: Date ): BalanceDateFilterValue => ( {
	operator: 'between',
	value: [
		toYmdUTC(
			new Date( Date.UTC( now.getUTCFullYear(), now.getUTCMonth(), 1 ) )
		),
		getLatestCompleteDayYmd( now ),
	],
} );

const getYearToDateFilter = ( now: Date ): BalanceDateFilterValue => ( {
	operator: 'between',
	value: [
		toYmdUTC( new Date( Date.UTC( now.getUTCFullYear(), 0, 1 ) ) ),
		getLatestCompleteDayYmd( now ),
	],
} );

const isBalanceDatePreset = ( value: string ): value is BalanceDatePreset =>
	[ 'last_month', 'month_to_date', 'year_to_date' ].includes( value );

const getBalanceDateFilterForPreset = (
	preset: BalanceDatePreset,
	now: Date
): BalanceDateFilterValue => {
	if ( preset === 'month_to_date' ) {
		return getMonthToDateFilter( now );
	}

	if ( preset === 'year_to_date' ) {
		return getYearToDateFilter( now );
	}

	return getLastFullCalendarMonthDateFilter( now );
};

const normalizeBalanceDateFilter = (
	value: BalanceDateFilterValue,
	now: Date
): BalanceDateFilterValue => {
	if ( value.operator === 'between' ) {
		const [ start, end ] = sortYmdRange(
			capYmdAtLatestCompleteDay( value.value[ 0 ], now ),
			capYmdAtLatestCompleteDay( value.value[ 1 ], now )
		);

		return {
			operator: 'between',
			value: [ start, end ],
		};
	}

	return {
		operator: value.operator,
		value: capYmdAtLatestCompleteDay( value.value, now ),
	};
};

const getMonthStartYmd = ( ymd: string ): string => {
	const [ year, month ] = parseYmd( ymd );
	return toYmdUTC( new Date( Date.UTC( year, month - 1, 1 ) ) );
};

const getMonthEndYmd = ( ymd: string ): string => {
	const [ year, month ] = parseYmd( ymd );
	return toYmdUTC( new Date( Date.UTC( year, month, 0 ) ) );
};

const getBalancePeriodForDateFilter = (
	value: BalanceDateFilterValue,
	now: Date
): Required< Pick< ReportsBalanceQuery, 'date_start' | 'date_end' > > => {
	const normalized = normalizeBalanceDateFilter( value, now );

	if ( normalized.operator === 'between' ) {
		return {
			date_start: toStartOfDayUTC( normalized.value[ 0 ] ),
			date_end: toEndOfDayUTC( normalized.value[ 1 ] ),
		};
	}

	if ( normalized.operator === 'on' ) {
		return {
			date_start: toStartOfDayUTC( normalized.value ),
			date_end: toEndOfDayUTC( normalized.value ),
		};
	}

	if ( normalized.operator === 'before' ) {
		return {
			date_start: toStartOfDayUTC( getMonthStartYmd( normalized.value ) ),
			date_end: toEndOfDayUTC( normalized.value ),
		};
	}

	return {
		date_start: toStartOfDayUTC( normalized.value ),
		date_end: toEndOfDayUTC(
			minYmd(
				getMonthEndYmd( normalized.value ),
				getLatestCompleteDayYmd( now )
			)
		),
	};
};

const getRangeDays = ( start?: string, end?: string ): number | null => {
	if ( ! start || ! end ) {
		return null;
	}

	return Math.round(
		( new Date( end ).getTime() - new Date( start ).getTime() ) / 86400000
	);
};

const getBalanceQuery = (
	now: Date,
	currency: string,
	dateFilter: BalanceDateFilterValue
): ReportsBalanceQuery => ( {
	...getBalancePeriodForDateFilter( dateFilter, now ),
	currency,
} );

const getBalanceDateFilterFromSearch = (
	search: string,
	now: Date
): BalanceDateFilterValue => {
	const params = new URLSearchParams( search );
	const between = [
		...params.getAll( 'date_between[]' ),
		...params.getAll( 'date_between' ),
	].filter( isYmd );

	if ( between.length >= 2 ) {
		return normalizeBalanceDateFilter(
			{
				operator: 'between',
				value: [ between[ 0 ], between[ 1 ] ],
			},
			now
		);
	}

	const dateStart = params.get( 'date_start' )?.slice( 0, 10 );
	const dateEnd = params.get( 'date_end' )?.slice( 0, 10 );
	if ( isYmd( dateStart ) && isYmd( dateEnd ) ) {
		return normalizeBalanceDateFilter(
			{
				operator: 'between',
				value: [ dateStart, dateEnd ],
			},
			now
		);
	}

	const dateBefore = params.get( 'date_before' );
	if ( isYmd( dateBefore ) ) {
		return normalizeBalanceDateFilter(
			{
				operator: 'before',
				value: dateBefore,
			},
			now
		);
	}

	const dateAfter = params.get( 'date_after' );
	if ( isYmd( dateAfter ) ) {
		return normalizeBalanceDateFilter(
			{
				operator: 'after',
				value: dateAfter,
			},
			now
		);
	}

	return getLastFullCalendarMonthDateFilter( now );
};

const getBalanceDateFilterFromView = (
	view: View,
	fallback: BalanceDateFilterValue,
	now: Date
): BalanceDateFilterValue => {
	const dateFilter = ( view.filters as DataViewsFilter[] | undefined )?.find(
		( filter ) => filter.field === 'date'
	);

	if ( ! dateFilter ) {
		return getLastFullCalendarMonthDateFilter( now );
	}

	if (
		dateFilter.operator === 'between' &&
		Array.isArray( dateFilter.value ) &&
		isYmd( dateFilter.value[ 0 ] ) &&
		isYmd( dateFilter.value[ 1 ] )
	) {
		return normalizeBalanceDateFilter(
			{
				operator: 'between',
				value: [ dateFilter.value[ 0 ], dateFilter.value[ 1 ] ],
			},
			now
		);
	}

	if (
		( dateFilter.operator === 'on' ||
			dateFilter.operator === 'before' ||
			dateFilter.operator === 'after' ) &&
		isYmd( dateFilter.value )
	) {
		return normalizeBalanceDateFilter(
			{
				operator: dateFilter.operator,
				value: dateFilter.value,
			},
			now
		);
	}

	return fallback;
};

const serializeBalanceDateFilterToSearch = (
	value: BalanceDateFilterValue
): string => {
	const params = new URLSearchParams();

	if ( value.operator === 'between' ) {
		params.append( 'date_between[]', value.value[ 0 ] );
		params.append( 'date_between[]', value.value[ 1 ] );
		return params.toString();
	}

	if ( value.operator === 'on' ) {
		params.append( 'date_between[]', value.value );
		params.append( 'date_between[]', value.value );
		return params.toString();
	}

	params.set(
		value.operator === 'before' ? 'date_before' : 'date_after',
		value.value
	);
	return params.toString();
};

const matchBalanceDatePreset = (
	value: BalanceDateFilterValue,
	now: Date
): BalanceDatePreset => {
	if ( value.operator !== 'between' ) {
		return 'custom';
	}

	const lastMonth = getLastFullCalendarMonthDateFilter( now );

	if (
		value.value[ 0 ] === lastMonth.value[ 0 ] &&
		value.value[ 1 ] === lastMonth.value[ 1 ]
	) {
		return 'last_month';
	}

	const monthToDate = getMonthToDateFilter( now );

	if (
		value.value[ 0 ] === monthToDate.value[ 0 ] &&
		value.value[ 1 ] === monthToDate.value[ 1 ]
	) {
		return 'month_to_date';
	}

	const yearToDate = getYearToDateFilter( now );

	if (
		value.value[ 0 ] === yearToDate.value[ 0 ] &&
		value.value[ 1 ] === yearToDate.value[ 1 ]
	) {
		return 'year_to_date';
	}

	return 'custom';
};

const getBalanceAmount = (
	row: ReportsBalanceSummaryRow | undefined
): number => row?.amount ?? 0;

const getBalanceRows = ( summary: ReportsBalanceSummary ): BalanceRow[] =>
	[
		{
			id: 'starting_balance',
			label: __( 'Starting balance', 'woocommerce' ),
			amount: getBalanceAmount( summary.starting_balance ),
			count: summary.starting_balance?.count,
			alwaysVisible: true,
		},
		{
			id: 'total_charges_captured',
			label: __( 'Total charges captured', 'woocommerce' ),
			amount: getBalanceAmount( summary.total_charges_captured ),
			count: summary.total_charges_captured?.count,
			alwaysVisible: true,
		},
		{
			id: 'fees',
			label: __( 'Fees', 'woocommerce' ),
			amount: getBalanceAmount( summary.fees ),
			count: summary.fees?.count,
			alwaysVisible: true,
		},
		{
			id: 'charge_fees',
			label: __( 'Charge fees', 'woocommerce' ),
			amount: getBalanceAmount( summary.charge_fees ),
			count: summary.charge_fees?.count,
		},
		{
			id: 'dispute_fees',
			label: __( 'Dispute fees', 'woocommerce' ),
			amount: getBalanceAmount( summary.dispute_fees ),
			count: summary.dispute_fees?.count,
		},
		{
			id: 'fee_refunds',
			label: __( 'Fee refunds', 'woocommerce' ),
			amount: getBalanceAmount( summary.fee_refunds ),
			count: summary.fee_refunds?.count,
		},
		{
			id: 'refunds',
			label: __( 'Refunds', 'woocommerce' ),
			amount: getBalanceAmount( summary.refunds ),
			count: summary.refunds?.count,
		},
		{
			id: 'refund_failure',
			label: __( 'Refund failures', 'woocommerce' ),
			amount: getBalanceAmount( summary.refund_failure ),
			count: summary.refund_failure?.count,
		},
		{
			id: 'disputes',
			label: __( 'Disputes', 'woocommerce' ),
			amount: getBalanceAmount( summary.disputes ),
			count: summary.disputes?.count,
		},
		{
			id: 'financing_payout',
			label: __( 'Financing payout', 'woocommerce' ),
			amount: getBalanceAmount( summary.financing_payout ),
			count: summary.financing_payout?.count,
		},
		{
			id: 'financing_paydown',
			label: __( 'Financing paydown', 'woocommerce' ),
			amount: getBalanceAmount( summary.financing_paydown ),
			count: summary.financing_paydown?.count,
		},
		{
			id: 'payout_fees',
			label: __( 'Payout fees', 'woocommerce' ),
			amount: getBalanceAmount( summary.payout_fees ),
			count: summary.payout_fees?.count,
		},
		{
			id: 'reader_fees',
			label: __( 'Reader costs', 'woocommerce' ),
			amount: getBalanceAmount( summary.reader_fees ),
			count: summary.reader_fees?.count,
		},
		{
			id: 'network_costs',
			label: __( 'Network costs', 'woocommerce' ),
			amount: getBalanceAmount( summary.network_costs ),
			count: summary.network_costs?.count,
		},
		{
			id: 'other_adjustments',
			label: __( 'Other adjustments', 'woocommerce' ),
			amount: getBalanceAmount( summary.other_adjustments ),
			count: summary.other_adjustments?.count,
		},
		{
			id: 'net_balance_change_in_the_period',
			label: __( 'Net balance change in the period', 'woocommerce' ),
			amount: getBalanceAmount(
				summary.net_balance_change_in_the_period
			),
			count: summary.net_balance_change_in_the_period?.count,
			alwaysVisible: true,
		},
		{
			id: 'payouts',
			label: __( 'Payouts', 'woocommerce' ),
			amount: getBalanceAmount( summary.payouts ),
			count: summary.payouts?.count,
			alwaysVisible: true,
		},
		{
			id: 'ending_balance',
			label: __( 'Ending balance', 'woocommerce' ),
			amount: getBalanceAmount( summary.ending_balance ),
			count: summary.ending_balance?.count,
			alwaysVisible: true,
		},
	].filter(
		( row ) =>
			row.alwaysVisible ||
			row.amount !== 0 ||
			Number( row.count ?? 0 ) > 0
	);

const hasBalanceActivity = ( rows: BalanceRow[] ) =>
	rows.some(
		( row ) =>
			row.id !== 'starting_balance' &&
			row.id !== 'ending_balance' &&
			( row.amount !== 0 || Number( row.count ?? 0 ) > 0 )
	);

const getMethodLabel = ( value?: string ) => {
	if ( ! value ) {
		return '-';
	}

	if ( value === 'card' ) {
		return __( 'Card', 'woocommerce' );
	}

	return value
		.replace( /_/g, ' ' )
		.replace( /\b\w/g, ( letter ) => letter.toUpperCase() );
};

const getTypeLabel = ( value?: string ) => {
	if ( ! value ) {
		return '-';
	}

	const labels: Record< string, string > = {
		charge: __( 'Charge', 'woocommerce' ),
		payment: __( 'Payment', 'woocommerce' ),
		refund: __( 'Refund', 'woocommerce' ),
		dispute: __( 'Dispute', 'woocommerce' ),
		fee_refund: __( 'Fee refund', 'woocommerce' ),
		network_costs: __( 'Network costs', 'woocommerce' ),
	};

	return labels[ value ] || getMethodLabel( value );
};

const BalanceReport = ( { now }: { now: Date } ) => {
	const location = useLocation();
	const navigate = useNavigate();
	const currency =
		getGlobalSettings().wcpaySettings?.accountDefaultCurrency || 'USD';
	const dateFilter = useMemo(
		() => getBalanceDateFilterFromSearch( location.search, now ),
		[ location.search, now ]
	);
	const query = useMemo(
		() => getBalanceQuery( now, currency, dateFilter ),
		[ currency, dateFilter, now ]
	);
	const [ state, setState ] = useState< AsyncState< ReportsBalanceSummary > >(
		{
			isLoading: true,
		}
	);
	const errorHeadingRef = useRef< HTMLHeadingElement >( null );
	const [ exportStatus, setExportStatus ] = useState( '' );
	const mountedRef = useRef( true );

	useEffect(
		() => () => {
			mountedRef.current = false;
		},
		[]
	);

	const load = useCallback( async () => {
		setState( ( previous ) => ( {
			...previous,
			isLoading: true,
		} ) );

		try {
			const summary = await getWooPaymentsReportsBalanceSummary( query );
			const rows = getBalanceRows( summary );
			const rowsHaveActivity = hasBalanceActivity( rows );

			if ( ! mountedRef.current ) {
				return;
			}

			setState( {
				data: summary,
				error: undefined,
				isLoading: false,
			} );
			recordEvent( 'wcpay_reports_balance_load_success', {
				currency: ( summary.currency || currency ).toLowerCase(),
				has_activity: rowsHaveActivity,
				visible_row_count: rows.length,
			} );
			if ( rowsHaveActivity ) {
				speak(
					sprintf(
						/* translators: %d: number of balance rows loaded into the report. */
						__( '%d balance report rows loaded.', 'woocommerce' ),
						rows.length
					),
					'polite'
				);
			}
		} catch ( error ) {
			if ( ! mountedRef.current ) {
				return;
			}

			setState( {
				error: error as Error,
				isLoading: false,
			} );
			recordEvent( 'wcpay_reports_balance_load_error', {
				error_type: 'request',
			} );
			speak(
				__( 'Balance report could not be loaded.', 'woocommerce' ),
				'assertive'
			);
		}
	}, [ currency, query ] );

	useEffect( () => {
		load();
	}, [ load ] );

	useEffect( () => {
		if ( state.error ) {
			errorHeadingRef.current?.focus();
		}
	}, [ state.error ] );

	const summary = state.data || {};
	const rows = getBalanceRows( summary );
	const hasActivity = hasBalanceActivity( rows );
	const tableRows = state.error ? [] : rows;
	const reportCurrency = summary.currency || currency;
	const isInitialLoading = state.isLoading && ! state.data && ! state.error;
	const fields = useMemo< Field< BalanceRow >[] >(
		() => [
			{
				id: 'date',
				label: __( 'Date', 'woocommerce' ),
				type: 'date',
				enableHiding: false,
				enableSorting: false,
				filterBy: {
					operators: [ 'before', 'after', 'between', 'on' ],
				},
				getValue: () => query.date_start || '',
			},
			{
				id: 'label',
				label: __( 'Description', 'woocommerce' ),
				getValue: ( { item }: { item: BalanceRow } ) => item.label,
				render: ( { item }: { item: BalanceRow } ) => (
					<span>{ item.label }</span>
				),
			},
			{
				id: 'amount',
				label: __( 'Amount', 'woocommerce' ),
				type: 'integer',
				getValue: ( { item }: { item: BalanceRow } ) => item.amount,
				render: ( { item }: { item: BalanceRow } ) => (
					<span>{ formatAmount( item.amount, reportCurrency ) }</span>
				),
			},
		],
		[ query.date_start, reportCurrency ]
	);
	const view = useMemo< View >(
		() => ( {
			type: 'table',
			page: 1,
			perPage: tableRows.length || 1,
			fields: [ 'label', 'amount' ],
			filters: [
				{
					field: 'date',
					operator: dateFilter.operator,
					value: dateFilter.value,
				},
			],
			layout: {
				enableMoving: false,
			},
		} ),
		[ dateFilter.operator, dateFilter.value, tableRows.length ]
	);
	const activeDatePreset = matchBalanceDatePreset( dateFilter, now );

	const changeDateFilter = ( nextDateFilter: BalanceDateFilterValue ) => {
		if (
			JSON.stringify( nextDateFilter ) === JSON.stringify( dateFilter )
		) {
			return;
		}

		const nextPeriod = getBalancePeriodForDateFilter( nextDateFilter, now );
		recordEvent( 'wcpay_reports_balance_date_filter_change', {
			preset: matchBalanceDatePreset( nextDateFilter, now ),
			range_days: getRangeDays(
				nextPeriod.date_start,
				nextPeriod.date_end
			),
			is_initial_apply: false,
		} );

		const search = serializeBalanceDateFilterToSearch( nextDateFilter );
		navigateReportsRoute( location.search, navigate, search );
	};

	const handleChangeView = ( nextView: View ) => {
		changeDateFilter(
			getBalanceDateFilterFromView( nextView, dateFilter, now )
		);
	};

	const handlePresetChange = ( nextPreset: string | null ) => {
		if ( ! nextPreset || ! isBalanceDatePreset( nextPreset ) ) {
			return;
		}

		changeDateFilter( getBalanceDateFilterForPreset( nextPreset, now ) );
	};

	if ( isInitialLoading ) {
		return (
			<div role="status" aria-live="polite" aria-busy="true">
				{ __( 'Loading balance report…', 'woocommerce' ) }
			</div>
		);
	}

	let balanceState: ReactNode = null;

	if ( state.error ) {
		balanceState = (
			<ReportState
				title={ __( 'Balance report unavailable', 'woocommerce' ) }
				description={ __(
					"We couldn't load your balance report. Try again in a few minutes.",
					'woocommerce'
				) }
				role="alert"
				headingRef={ errorHeadingRef }
				headingTabIndex={ -1 }
				action={
					<Button
						variant="secondary"
						onClick={ () => {
							recordEvent(
								'wcpay_reports_balance_reload_click',
								{}
							);
							load();
						} }
					>
						{ __( 'Reload report', 'woocommerce' ) }
					</Button>
				}
			/>
		);
	} else if ( ! hasActivity ) {
		balanceState = (
			<ReportState
				title={ __( 'No balance activity', 'woocommerce' ) }
				description={ __(
					'No balance activity was found for the selected period. Summary rows are shown with zero amounts.',
					'woocommerce'
				) }
			/>
		);
	}

	return (
		<section className="woocommerce-woopayments-reports-balance">
			<div className="woocommerce-woopayments-reports__toolbar">
				<h2>{ __( 'Balance summary', 'woocommerce' ) }</h2>
				<SelectControl
					className="woocommerce-woopayments-reports__date-range"
					label={ __( 'Date range', 'woocommerce' ) }
					value={ activeDatePreset }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					options={ [
						{
							label: __( 'Last month', 'woocommerce' ),
							value: 'last_month',
						},
						{
							label: __( 'Month to date', 'woocommerce' ),
							value: 'month_to_date',
						},
						{
							label: __( 'Year to date', 'woocommerce' ),
							value: 'year_to_date',
						},
						{
							label: __( 'Custom', 'woocommerce' ),
							value: 'custom',
						},
					] }
					onChange={ handlePresetChange }
				/>
				<div className="woocommerce-woopayments-reports__actions">
					<Button
						variant="secondary"
						onClick={ () => {
							recordEvent( 'wcpay_reports_balance_print_click', {
								visible_row_count: rows.length,
							} );
							window.print();
						} }
					>
						{ __( 'Print', 'woocommerce' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ () => {
							recordEvent( 'wcpay_reports_balance_export_click', {
								visible_row_count: rows.length,
							} );
							setExportStatus(
								__(
									'Balance report export started.',
									'woocommerce'
								)
							);
						} }
					>
						{ __( 'Export', 'woocommerce' ) }
					</Button>
				</div>
			</div>
			<div
				role="status"
				aria-live="polite"
				aria-atomic="true"
				aria-label={ __( 'Balance export status', 'woocommerce' ) }
			>
				{ exportStatus }
			</div>
			<DataViews
				data={ tableRows }
				fields={ fields }
				view={ view }
				onChangeView={ handleChangeView }
				isLoading={ state.isLoading }
				defaultLayouts={ { table: {} } }
				paginationInfo={ {
					totalItems: tableRows.length,
					totalPages: 1,
				} }
				getItemId={ ( item: BalanceRow ) => item.id }
			/>
			{ balanceState }
		</section>
	);
};

const FeesReport = () => {
	const [ view, setView ] = useState< View >( {
		type: 'table',
		page: 1,
		perPage: 25,
		fields: [
			'date',
			'transaction_id',
			'payment_method',
			'type',
			'order_id',
			'transaction_currency',
			'amount',
			'fees',
		],
		sort: {
			field: 'date',
			direction: 'desc',
		},
		filters: [],
	} );
	const [ rowsState, setRowsState ] = useState< AsyncState< ReportsFee[] > >(
		{
			isLoading: true,
		}
	);
	const [ summaryState, setSummaryState ] = useState<
		AsyncState< ReportsFeesSummary >
	>( {
		isLoading: true,
	} );
	const [ isExporting, setIsExporting ] = useState( false );
	const [ exportStatus, setExportStatus ] = useState( '' );
	const errorHeadingRef = useRef< HTMLHeadingElement >( null );
	const mountedRef = useRef( true );
	const query = useMemo(
		() => buildReportsFeesQueryFromView( view ),
		[ view ]
	);
	const serializedQuery = useMemo( () => JSON.stringify( query ), [ query ] );
	const rows = rowsState.data ?? [];
	const totalItems = summaryState.data?.count ?? rows.length;
	const hasError = Boolean( rowsState.error || summaryState.error );
	const isLoading = rowsState.isLoading || summaryState.isLoading;
	const hasFilters = Boolean( view.search || ( view.filters ?? [] ).length );
	const totalPages = Math.max(
		1,
		Math.ceil( totalItems / Number( view.perPage || 25 ) )
	);
	const isInitialLoading =
		isLoading && ! rowsState.data && ! summaryState.data;

	useEffect(
		() => () => {
			mountedRef.current = false;
		},
		[]
	);

	const load = useCallback( async () => {
		const requestQuery = JSON.parse( serializedQuery ) as ReportsFeesQuery;

		setRowsState( ( previous ) => ( {
			...previous,
			error: undefined,
			isLoading: true,
		} ) );
		setSummaryState( ( previous ) => ( {
			...previous,
			error: undefined,
			isLoading: true,
		} ) );

		try {
			const [ fees, summary ] = await Promise.all( [
				getWooPaymentsReportsFees( requestQuery ),
				getWooPaymentsReportsFeesSummary( requestQuery ),
			] );

			if ( ! mountedRef.current ) {
				return;
			}

			setRowsState( {
				data: fees,
				isLoading: false,
			} );
			setSummaryState( {
				data: summary,
				isLoading: false,
			} );
			recordEvent( 'wcpay_reports_fees_load_success', {
				total_items: summary.count ?? fees.length,
				has_filters: Boolean(
					requestQuery.search ||
						requestQuery.date_after ||
						requestQuery.date_before ||
						requestQuery.date_between ||
						requestQuery.payment_method_type ||
						requestQuery.type
				),
			} );
			speak(
				sprintf(
					/* translators: %d: number of fees loaded into the report table. */
					__( '%d fees loaded.', 'woocommerce' ),
					summary.count ?? fees.length
				),
				'polite'
			);
		} catch ( error ) {
			if ( ! mountedRef.current ) {
				return;
			}

			setRowsState( {
				error: error as Error,
				isLoading: false,
			} );
			setSummaryState( {
				error: error as Error,
				isLoading: false,
			} );
			recordEvent( 'wcpay_reports_fees_load_error', {
				has_filters: hasFilters,
			} );
			speak(
				__( 'Fees report could not be loaded.', 'woocommerce' ),
				'assertive'
			);
		}
	}, [ hasFilters, serializedQuery ] );

	useEffect( () => {
		load();
	}, [ load ] );

	useEffect( () => {
		if ( hasError ) {
			errorHeadingRef.current?.focus();
		}
	}, [ hasError ] );

	const fields = useMemo< Field< ReportsFee >[] >(
		() => [
			{
				id: 'date',
				label: __( 'Date', 'woocommerce' ),
				header: __( 'Date & time', 'woocommerce' ),
				type: 'date',
				enableSorting: true,
				filterBy: {
					operators: [ 'before', 'after', 'between', 'on' ],
				},
				getValue: ( { item }: { item: ReportsFee } ) => item.date || '',
				render: ( { item }: { item: ReportsFee } ) => (
					<span>{ formatDateTimeFromString( item.date, true ) }</span>
				),
			},
			{
				id: 'payment_method',
				label: __( 'Method', 'woocommerce' ),
				elements: ( summaryState.data?.sources || [] ).map(
					( source ) => ( {
						value: source,
						label: getMethodLabel( source ),
					} )
				),
				filterBy: { operators: [ 'is' ] },
				getValue: ( { item }: { item: ReportsFee } ) =>
					item.payment_method?.type || '',
				render: ( { item }: { item: ReportsFee } ) => (
					<span>{ getMethodLabel( item.payment_method?.type ) }</span>
				),
			},
			{
				id: 'type',
				label: __( 'Type', 'woocommerce' ),
				elements: (
					summaryState.data?.types || [ 'charge', 'refund' ]
				).map( ( type ) => ( {
					value: type,
					label: getTypeLabel( type ),
				} ) ),
				filterBy: { operators: [ 'is', 'isAny' ] },
				getValue: ( { item }: { item: ReportsFee } ) => item.type || '',
				render: ( { item }: { item: ReportsFee } ) => (
					<span>{ getTypeLabel( item.type ) }</span>
				),
			},
			{
				id: 'order_id',
				label: __( 'Order ID', 'woocommerce' ),
				getValue: ( { item }: { item: ReportsFee } ) =>
					item.order_id ?? '',
				render: ( { item }: { item: ReportsFee } ) =>
					item.order_id ? (
						<a href={ getOrderUrl( item.order_id ) }>
							{ String( item.order_id ) }
						</a>
					) : (
						<span>-</span>
					),
			},
			{
				id: 'transaction_id',
				label: __( 'Transaction ID', 'woocommerce' ),
				enableHiding: false,
				getValue: ( { item }: { item: ReportsFee } ) =>
					item.transaction_id,
				render: ( { item }: { item: ReportsFee } ) => (
					<a href={ getTransactionUrl( item ) }>
						{ item.transaction_id }
					</a>
				),
			},
			{
				id: 'transaction_currency',
				label: __( 'Currency', 'woocommerce' ),
				getValue: ( { item }: { item: ReportsFee } ) =>
					( item.transaction_currency || '' ).toUpperCase(),
				render: ( { item }: { item: ReportsFee } ) => (
					<span>
						{ ( item.transaction_currency || '' ).toUpperCase() ||
							'-' }
					</span>
				),
			},
			{
				id: 'amount',
				label: __( 'Gross amount', 'woocommerce' ),
				type: 'integer',
				getValue: ( { item }: { item: ReportsFee } ) =>
					item.amount ?? 0,
				render: ( { item }: { item: ReportsFee } ) => (
					<span>
						{ formatExplicitAmount(
							item.amount ?? 0,
							item.deposit_currency || item.transaction_currency
						) }
					</span>
				),
			},
			{
				id: 'fees',
				label: __( 'Fees total', 'woocommerce' ),
				type: 'integer',
				getValue: ( { item }: { item: ReportsFee } ) => item.fees ?? 0,
				render: ( { item }: { item: ReportsFee } ) => (
					<span>
						{ formatExplicitAmount(
							item.fees ?? 0,
							item.deposit_currency || item.transaction_currency
						) }
					</span>
				),
			},
			{
				id: 'deposit_date',
				label: __( 'Settlement date', 'woocommerce' ),
				getValue: ( { item }: { item: ReportsFee } ) =>
					item.deposit_date || '',
				render: ( { item }: { item: ReportsFee } ) => (
					<span>
						{ formatDateTimeFromString( item.deposit_date ) }
					</span>
				),
			},
			{
				id: 'deposit_id',
				label: __( 'Payout ID', 'woocommerce' ),
				getValue: ( { item }: { item: ReportsFee } ) =>
					item.deposit_id || '',
			},
		],
		[ summaryState.data?.sources, summaryState.data?.types ]
	);

	const handleChangeView = ( nextView: View ) => {
		if ( nextView.search !== view.search ) {
			recordEvent( 'wcpay_reports_fees_search', {
				search_length: String( nextView.search || '' ).length,
			} );
		}

		setView( nextView );
	};

	const handleExport = async () => {
		const settings = getGlobalSettings();
		const exportQuery = {
			...query,
			user_email: settings.wcpaySettings?.currentUserEmail,
			locale: settings.wcSettings?.locale?.userLocale,
		};

		setIsExporting( true );
		setExportStatus( __( 'Preparing fees export…', 'woocommerce' ) );
		recordEvent( 'wcpay_csv_export_click', {
			row_type: 'fees',
			source: 'payments_reports',
			exported_row_count: totalItems,
		} );

		try {
			await runWooPaymentsExport( {
				requestExport: () =>
					requestWooPaymentsReportsFeesExport( exportQuery ),
				getExportUrl: getWooPaymentsReportsFeesExportUrl,
			} );
			setExportStatus( __( 'Fees export is ready.', 'woocommerce' ) );
			recordEvent( 'wcpay_reports_fees_export_success', {
				exported_row_count: totalItems,
			} );
		} catch ( error ) {
			setExportStatus(
				__( 'Fees export could not be prepared.', 'woocommerce' )
			);
			recordEvent( 'wcpay_reports_fees_export_error', {
				error_type: error instanceof Error ? 'request' : 'unknown',
			} );
			speak(
				__( 'Fees export could not be prepared.', 'woocommerce' ),
				'assertive'
			);
		} finally {
			setIsExporting( false );
		}
	};

	if ( isInitialLoading ) {
		return (
			<div role="status" aria-live="polite" aria-busy="true">
				{ __( 'Loading fees report…', 'woocommerce' ) }
			</div>
		);
	}

	if ( hasError ) {
		return (
			<ReportState
				title={ __( 'Fees report unavailable', 'woocommerce' ) }
				description={ __(
					"We couldn't load your fees data. Try again in a few minutes.",
					'woocommerce'
				) }
				role="alert"
				headingRef={ errorHeadingRef }
				headingTabIndex={ -1 }
				action={
					<Button
						variant="secondary"
						onClick={ () => {
							recordEvent(
								'wcpay_reports_fees_reload_click',
								{}
							);
							load();
						} }
					>
						{ __( 'Reload report', 'woocommerce' ) }
					</Button>
				}
			/>
		);
	}

	if ( rows.length === 0 && ! hasFilters ) {
		return (
			<ReportState
				title={ __( 'No fees yet', 'woocommerce' ) }
				description={ __(
					'Fees will appear here once you start receiving payments.',
					'woocommerce'
				) }
			/>
		);
	}

	return (
		<section className="woocommerce-woopayments-reports-fees">
			<DataViews
				data={ rows }
				fields={ fields }
				view={ view }
				onChangeView={ handleChangeView }
				isLoading={ isLoading }
				search
				searchLabel={ __( 'Search fees', 'woocommerce' ) }
				header={
					<div className="woocommerce-woopayments-reports__toolbar">
						<h2>{ __( 'Fees', 'woocommerce' ) }</h2>
						<Button
							variant="primary"
							onClick={ handleExport }
							disabled={ isExporting }
							accessibleWhenDisabled
							aria-busy={ isExporting }
							isBusy={ isExporting }
						>
							{ __( 'Export', 'woocommerce' ) }
						</Button>
					</div>
				}
				paginationInfo={ {
					totalItems,
					totalPages,
				} }
				defaultLayouts={ { table: {} } }
				getItemId={ ( item: ReportsFee ) => item.transaction_id }
			/>
			{ rows.length === 0 && hasFilters && (
				<ReportState
					title={ __( 'No fees to display', 'woocommerce' ) }
					description={ __(
						'Fees will appear here.',
						'woocommerce'
					) }
				/>
			) }
			<div
				role="status"
				aria-live="polite"
				aria-atomic="true"
				aria-label={ __( 'Fees export status', 'woocommerce' ) }
			>
				{ exportStatus }
			</div>
		</section>
	);
};

export const WooPaymentsReportsPage = ( {
	now = new Date(),
}: WooPaymentsReportsPageProps ) => {
	const stableNow = useRef( now ).current;
	const location = useLocation();
	const navigate = useNavigate();
	const activeTab = getReportsTabFromSearch( location.search );
	const balanceTabId = useId();
	const feesTabId = useId();
	const balancePanelId = useId();
	const feesPanelId = useId();
	const balanceTabRef = useRef< HTMLButtonElement >( null );
	const feesTabRef = useRef< HTMLButtonElement >( null );

	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_reports',
			tab: activeTab,
		} );
		// Mount-only parity with the reference surface.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const selectTab = ( nextTab: ReportsTab ) => {
		if ( nextTab === activeTab ) {
			return;
		}

		recordEvent( 'wcpay_reports_tab_change', {
			from_tab: activeTab,
			to_tab: nextTab,
		} );
		navigateReportsRoute(
			location.search,
			navigate,
			nextTab === 'fees' ? 'report_tab=fees' : ''
		);
	};

	const focusTab = ( nextTab: ReportsTab ) => {
		selectTab( nextTab );

		if ( nextTab === 'balance' ) {
			balanceTabRef.current?.focus();
			return;
		}

		feesTabRef.current?.focus();
	};

	const handleTabKeyDown = ( event: KeyboardEvent< HTMLButtonElement > ) => {
		if ( event.nativeEvent.isComposing ) {
			return;
		}

		if ( [ 'ArrowLeft', 'ArrowUp', 'Home' ].includes( event.key ) ) {
			event.preventDefault();
			focusTab( 'balance' );
		}

		if ( [ 'ArrowRight', 'ArrowDown', 'End' ].includes( event.key ) ) {
			event.preventDefault();
			focusTab( 'fees' );
		}
	};

	return (
		<div className="woocommerce-woopayments-reports">
			<h1 className="screen-reader-text">
				{ __( 'Reports', 'woocommerce' ) }
			</h1>
			<p className="woocommerce-woopayments-reports__intro">
				{ __( 'View your reconciliation reports.', 'woocommerce' ) }
			</p>
			<div
				className="woocommerce-woopayments-reports__tabs"
				role="tablist"
				aria-label={ __( 'Reports', 'woocommerce' ) }
			>
				<button
					type="button"
					ref={ balanceTabRef }
					id={ balanceTabId }
					role="tab"
					aria-selected={ activeTab === 'balance' }
					aria-controls={ balancePanelId }
					tabIndex={ activeTab === 'balance' ? 0 : -1 }
					onClick={ () => selectTab( 'balance' ) }
					onKeyDown={ handleTabKeyDown }
				>
					{ __( 'Balance', 'woocommerce' ) }
				</button>
				<button
					type="button"
					ref={ feesTabRef }
					id={ feesTabId }
					role="tab"
					aria-selected={ activeTab === 'fees' }
					aria-controls={ feesPanelId }
					tabIndex={ activeTab === 'fees' ? 0 : -1 }
					onClick={ () => selectTab( 'fees' ) }
					onKeyDown={ handleTabKeyDown }
				>
					{ __( 'Fees', 'woocommerce' ) }
				</button>
			</div>
			<div
				id={ balancePanelId }
				role="tabpanel"
				aria-labelledby={ balanceTabId }
				hidden={ activeTab !== 'balance' }
			>
				{ activeTab === 'balance' && (
					<BalanceReport now={ stableNow } />
				) }
			</div>
			<div
				id={ feesPanelId }
				role="tabpanel"
				aria-labelledby={ feesTabId }
				hidden={ activeTab !== 'fees' }
			>
				{ activeTab === 'fees' && <FeesReport /> }
			</div>
		</div>
	);
};
