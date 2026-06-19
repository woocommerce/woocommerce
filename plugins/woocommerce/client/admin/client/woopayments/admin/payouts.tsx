/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useLocation, useNavigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsDeposits,
	getWooPaymentsDepositsExportUrl,
	getWooPaymentsDepositsSummary,
	requestWooPaymentsDepositsExport,
} from './overview/data';
import { WooPaymentsMoneyMovementDataViews } from './money-movement/dataviews';
import { runWooPaymentsExport } from './money-movement/export';
import {
	buildMoneyMovementRoutePath,
	dataViewsViewToMoneyMovementQuery,
	moneyMovementQueryToDataViewsView,
	parseMoneyMovementQuery,
} from './money-movement/query';
import type { WooPaymentsMoneyMovementDataView } from './money-movement/types';
import { LiveStatusMessage, StatusMessage } from './money-movement/table';
import { getErrorMessage } from './money-movement/utils';
import {
	getMoneyMovementViewPreferences,
	mergeMoneyMovementViewPreferences,
	setMoneyMovementViewPreferences,
} from './money-movement/view-preferences';
import type {
	WooPaymentsDeposit,
	WooPaymentsDepositsQuery,
	WooPaymentsDepositsSummary,
} from './overview/types';
import {
	formatPayoutDate,
	formatPayoutStatus,
	formatWooPaymentsAmount,
} from './overview/utils';
import { getSettingsPaymentsProviderRouteUrl } from './utils';
import { SpotlightPromotion } from '../promotions/spotlight';
import './style.scss';

type PayoutsSummary = WooPaymentsDepositsSummary;
type ExportMessage = {
	text: string;
	isError?: boolean;
};

const getSummaryCount = ( summary: PayoutsSummary ) => {
	const count = summary.count;

	return typeof count === 'number' ? count : undefined;
};

const getSummaryTotal = ( summary: PayoutsSummary ) =>
	typeof summary.total === 'number' ? summary.total : undefined;

const getSummaryCurrency = ( summary: PayoutsSummary ) =>
	typeof summary.currency === 'string' ? summary.currency : undefined;

const getFirstString = ( value: unknown ) => {
	if ( Array.isArray( value ) ) {
		return value.find( ( item ) => typeof item === 'string' && item );
	}

	return typeof value === 'string' && value ? value : undefined;
};

const getPayoutsRequestQuery = (
	query: ReturnType< typeof parseMoneyMovementQuery >
): WooPaymentsDepositsQuery => ( {
	page: query.page,
	pagesize: query.pagesize,
	sort: query.sort,
	direction: query.direction,
	match: getFirstString( query.search ),
	store_currency_is: getFirstString( query.store_currency_is ),
	status_is: getFirstString( query.status_is ),
	status_is_not: getFirstString( query.status_is_not ),
	date_after: getFirstString( query.date_after ),
	date_before: getFirstString( query.date_before ),
	date_between: getFirstString( query.date_between ),
} );

export const WooPaymentsPayouts = () => {
	const [ payouts, setPayouts ] = useState< WooPaymentsDeposit[] >( [] );
	const [ totalCount, setTotalCount ] = useState( 0 );
	const [ summary, setSummary ] = useState< PayoutsSummary >( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ exportMessage, setExportMessage ] =
		useState< ExportMessage | null >( null );
	const [ isExporting, setIsExporting ] = useState( false );
	const [ viewPreferences, setViewPreferences ] = useState( () =>
		getMoneyMovementViewPreferences( 'payouts' )
	);
	const location = useLocation();
	const navigate = useNavigate();
	const query = useMemo(
		() =>
			parseMoneyMovementQuery( location.search, {
				page: 1,
				pagesize: 25,
				sort: 'date',
				direction: 'desc',
			} ),
		[ location.search ]
	);
	const queryView = useMemo(
		() =>
			moneyMovementQueryToDataViewsView( query, {
				fields: [ 'date', 'status', 'amount' ],
				titleField: 'date',
				showTitle: false,
			} ),
		[ query ]
	);
	const view = useMemo(
		() => mergeMoneyMovementViewPreferences( queryView, viewPreferences ),
		[ queryView, viewPreferences ]
	);
	const fields = useMemo(
		() => [
			{
				id: 'date',
				label: __( 'Dispatch date', 'woocommerce' ),
				enableHiding: false,
				render: ( { item }: { item: WooPaymentsDeposit } ) => (
					<a
						href={ getSettingsPaymentsProviderRouteUrl(
							`/woopayments/payouts/details?id=${ encodeURIComponent(
								item.id
							) }`
						) }
					>
						{ formatPayoutDate( item ) }
						<span className="screen-reader-text">
							{ sprintf(
								/* translators: %s: payout ID. */
								__(
									' - view payout details for %s',
									'woocommerce'
								),
								item.id
							) }
						</span>
					</a>
				),
			},
			{
				id: 'status',
				label: __( 'Status', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsDeposit } ) =>
					formatPayoutStatus( item.status ),
			},
			{
				id: 'amount',
				label: __( 'Amount', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsDeposit } ) =>
					formatWooPaymentsAmount( item.amount, item.currency ),
			},
		],
		[]
	);

	useEffect( () => {
		let isMounted = true;

		const loadPayouts = async () => {
			setIsLoading( true );

			try {
				const requestQuery = getPayoutsRequestQuery( query );
				const [ response, nextSummary ] = await Promise.all( [
					getWooPaymentsDeposits( requestQuery ),
					getWooPaymentsDepositsSummary( requestQuery ),
				] );

				if ( ! isMounted ) {
					return;
				}

				setPayouts( response.data || [] );
				setTotalCount( response.total_count || 0 );
				setSummary( nextSummary );
				setErrorMessage( null );
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments payout history.',
								'woocommerce'
							)
						)
					);
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		};

		loadPayouts();

		return () => {
			isMounted = false;
		};
	}, [ query ] );

	const handleViewChange = ( nextView: WooPaymentsMoneyMovementDataView ) => {
		setViewPreferences(
			setMoneyMovementViewPreferences( 'payouts', nextView )
		);
		navigate(
			buildMoneyMovementRoutePath(
				'/woopayments/payouts',
				dataViewsViewToMoneyMovementQuery( nextView, query )
			)
		);
	};
	const handleExport = async () => {
		setIsExporting( true );
		setExportMessage( null );

		try {
			const requestQuery = getPayoutsRequestQuery( query );

			await runWooPaymentsExport( {
				requestExport: () =>
					requestWooPaymentsDepositsExport( requestQuery ),
				getExportUrl: getWooPaymentsDepositsExportUrl,
			} );
			setExportMessage( {
				text: __(
					'Your payouts export has started downloading.',
					'woocommerce'
				),
			} );
		} catch ( error ) {
			setExportMessage( {
				text: getErrorMessage(
					error,
					__( 'Unable to export WooPayments payouts.', 'woocommerce' )
				),
				isError: true,
			} );
		} finally {
			setIsExporting( false );
		}
	};
	let liveStatusMessage = __( 'Payout history loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = __( 'Loading payouts…', 'woocommerce' );
	} else if ( payouts.length === 0 ) {
		liveStatusMessage = __( 'No payouts found.', 'woocommerce' );
	}

	const summaryCount = getSummaryCount( summary ) ?? totalCount;
	const summaryTotal = getSummaryTotal( summary );
	const summaryCurrency = getSummaryCurrency( summary );

	return (
		<div className="woocommerce-woopayments-payouts">
			<SpotlightPromotion />
			<section
				className="woocommerce-woopayments-overview-card"
				aria-busy={ isLoading }
			>
				<h2>{ __( 'Payout history', 'woocommerce' ) }</h2>
				<LiveStatusMessage isError={ !! errorMessage }>
					{ liveStatusMessage }
				</LiveStatusMessage>
				{ isLoading && (
					<StatusMessage>
						{ __( 'Loading payouts…', 'woocommerce' ) }
					</StatusMessage>
				) }
				{ errorMessage && (
					<StatusMessage isError>{ errorMessage }</StatusMessage>
				) }
				<div className="woocommerce-woopayments-money-movement__summary">
					<span>
						{ sprintf(
							/* translators: %d: payouts count. */
							__( '%d payouts', 'woocommerce' ),
							summaryCount
						) }
					</span>
					{ typeof summaryTotal === 'number' && (
						<span>
							{ formatWooPaymentsAmount(
								summaryTotal,
								summaryCurrency
							) }
						</span>
					) }
				</div>
				{ exportMessage && (
					<StatusMessage isLive isError={ !! exportMessage.isError }>
						{ exportMessage.text }
					</StatusMessage>
				) }
				<WooPaymentsMoneyMovementDataViews
					fields={ fields }
					rows={ payouts }
					view={ view }
					onChangeView={ handleViewChange }
					total={ totalCount || payouts.length }
					isLoading={ isLoading }
					searchLabel={ __( 'Search payouts', 'woocommerce' ) }
					empty={ __( 'No payouts found.', 'woocommerce' ) }
					getItemId={ ( payout ) => payout.id }
					toolbarActions={
						<Button
							variant="secondary"
							onClick={ handleExport }
							isBusy={ isExporting }
							disabled={ isExporting }
						>
							{ __( 'Download payouts', 'woocommerce' ) }
						</Button>
					}
				/>
			</section>
		</div>
	);
};

export default WooPaymentsPayouts;
