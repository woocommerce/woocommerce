/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useLocation, useNavigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsDisputes,
	getWooPaymentsDisputesExportUrl,
	getWooPaymentsDisputesSummary,
	requestWooPaymentsDisputesExport,
} from './data';
import type {
	WooPaymentsDispute,
	WooPaymentsMoneyMovementDataView,
} from './types';
import { isDisputeActionable } from './dispute-evidence-fields';
import {
	buildMoneyMovementRoutePath,
	dataViewsViewToMoneyMovementQuery,
	moneyMovementQueryToDataViewsView,
	parseMoneyMovementQuery,
} from './query';
import { WooPaymentsMoneyMovementDataViews } from './dataviews';
import { runWooPaymentsExport } from './export';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getDisputeId,
	getErrorMessage,
	getTransactionDetailsRoute,
} from './utils';
import { LiveStatusMessage, StatusMessage } from './table';
import {
	getMoneyMovementViewPreferences,
	mergeMoneyMovementViewPreferences,
	setMoneyMovementViewPreferences,
} from './view-preferences';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';
import { SpotlightPromotion } from '../../promotions/spotlight';
import '../style.scss';

type DisputesSummary = Record< string, unknown >;
type ExportMessage = {
	text: string;
	isError?: boolean;
};

const getSummaryCount = ( summary: DisputesSummary ) => {
	const count = summary.total_count || summary.count;

	return typeof count === 'number' ? count : undefined;
};

const getSummaryTotal = ( summary: DisputesSummary ) => {
	const total = summary.total || summary.gross;

	return typeof total === 'number' ? total : undefined;
};

const getSummaryCurrency = ( summary: DisputesSummary ) =>
	typeof summary.currency === 'string' ? summary.currency : undefined;

export const WooPaymentsDisputesPage = () => {
	const [ disputes, setDisputes ] = useState< WooPaymentsDispute[] >( [] );
	const [ totalCount, setTotalCount ] = useState( 0 );
	const [ summary, setSummary ] = useState< DisputesSummary >( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ exportMessage, setExportMessage ] =
		useState< ExportMessage | null >( null );
	const [ isExporting, setIsExporting ] = useState( false );
	const [ viewPreferences, setViewPreferences ] = useState( () =>
		getMoneyMovementViewPreferences( 'disputes' )
	);
	const location = useLocation();
	const navigate = useNavigate();
	const query = useMemo(
		() =>
			parseMoneyMovementQuery( location.search, {
				page: 1,
				pagesize: 25,
				sort: 'created',
				direction: 'desc',
			} ),
		[ location.search ]
	);
	const queryView = useMemo(
		() =>
			moneyMovementQueryToDataViewsView( query, {
				fields: [ 'date', 'reason', 'status', 'amount', 'action' ],
				titleField: 'reason',
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
				label: __( 'Date', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsDispute } ) =>
					formatDate( item.date || item.created ),
			},
			{
				id: 'reason',
				label: __( 'Dispute', 'woocommerce' ),
				enableHiding: false,
				render: ( { item }: { item: WooPaymentsDispute } ) =>
					formatLabel( item.reason ),
			},
			{
				id: 'status',
				label: __( 'Status', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsDispute } ) =>
					formatLabel( item.status ),
			},
			{
				id: 'amount',
				label: __( 'Amount', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsDispute } ) =>
					formatAmount( item.amount, item.currency ),
			},
			{
				id: 'action',
				label: __( 'Action', 'woocommerce' ),
				enableHiding: false,
				enableSorting: false,
				render: ( { item }: { item: WooPaymentsDispute } ) => {
					const id = getDisputeId( item );
					const isActionable = isDisputeActionable( item );
					const rowHref = getSettingsPaymentsProviderRouteUrl(
						getTransactionDetailsRoute( item )
					);
					const ariaLabel = isActionable
						? sprintf(
								/* translators: 1: dispute reason, 2: dispute ID. */
								__(
									'Respond now to %1$s dispute %2$s from transaction details',
									'woocommerce'
								),
								formatLabel( item.reason ).toLowerCase(),
								id
						  )
						: sprintf(
								/* translators: 1: dispute reason, 2: dispute ID. */
								__(
									'View transaction details for %1$s dispute %2$s',
									'woocommerce'
								),
								formatLabel( item.reason ),
								id
						  );
					const action = isActionable
						? 'respond_from_transaction_details'
						: 'view_transaction';

					return (
						<a
							href={ rowHref }
							aria-label={ ariaLabel }
							onClick={ () =>
								recordEvent(
									'wcpay_disputes_row_action_click',
									{
										action,
										dispute_id: id,
									}
								)
							}
						>
							{ isActionable
								? __( 'Respond now', 'woocommerce' )
								: __( 'See details', 'woocommerce' ) }
						</a>
					);
				},
			},
		],
		[]
	);

	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_disputes',
		} );
	}, [] );

	useEffect( () => {
		let isMounted = true;

		const loadDisputes = async () => {
			setIsLoading( true );

			try {
				const [ response, nextSummary ] = await Promise.all( [
					getWooPaymentsDisputes( query ),
					getWooPaymentsDisputesSummary( query ),
				] );

				if ( isMounted ) {
					setDisputes( response.data || [] );
					setTotalCount( response.total_count || 0 );
					setSummary( nextSummary );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments disputes.',
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

		loadDisputes();

		return () => {
			isMounted = false;
		};
	}, [ query ] );

	const handleViewChange = ( nextView: WooPaymentsMoneyMovementDataView ) => {
		setViewPreferences(
			setMoneyMovementViewPreferences( 'disputes', nextView )
		);
		navigate(
			buildMoneyMovementRoutePath(
				'/woopayments/disputes',
				dataViewsViewToMoneyMovementQuery( nextView, query )
			)
		);
	};
	const handleExport = async () => {
		setIsExporting( true );
		setExportMessage( null );

		try {
			await runWooPaymentsExport( {
				requestExport: () => requestWooPaymentsDisputesExport( query ),
				getExportUrl: getWooPaymentsDisputesExportUrl,
			} );
			setExportMessage( {
				text: __(
					'Your disputes export has started downloading.',
					'woocommerce'
				),
			} );
		} catch ( error ) {
			setExportMessage( {
				text: getErrorMessage(
					error,
					__(
						'Unable to export WooPayments disputes.',
						'woocommerce'
					)
				),
				isError: true,
			} );
		} finally {
			setIsExporting( false );
		}
	};
	let liveStatusMessage = __( 'Disputes loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = __( 'Loading disputes…', 'woocommerce' );
	} else if ( disputes.length === 0 ) {
		liveStatusMessage = __( 'No disputes found.', 'woocommerce' );
	}

	const summaryCount = getSummaryCount( summary ) ?? totalCount;
	const summaryTotal = getSummaryTotal( summary );
	const summaryCurrency = getSummaryCurrency( summary );

	return (
		<div className="woocommerce-woopayments-money-movement">
			<SpotlightPromotion />
			<section aria-busy={ isLoading }>
				<h2>{ __( 'Disputes', 'woocommerce' ) }</h2>
				<LiveStatusMessage isError={ !! errorMessage }>
					{ liveStatusMessage }
				</LiveStatusMessage>
				{ isLoading && (
					<StatusMessage>
						{ __( 'Loading disputes…', 'woocommerce' ) }
					</StatusMessage>
				) }
				{ errorMessage && (
					<StatusMessage isError>{ errorMessage }</StatusMessage>
				) }
				<div className="woocommerce-woopayments-money-movement__summary">
					<span>
						{ sprintf(
							/* translators: %d: disputes count. */
							__( '%d disputes', 'woocommerce' ),
							summaryCount
						) }
					</span>
					{ typeof summaryTotal === 'number' && (
						<span>
							{ formatAmount( summaryTotal, summaryCurrency ) }
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
					rows={ disputes }
					view={ view }
					onChangeView={ handleViewChange }
					total={ totalCount || disputes.length }
					isLoading={ isLoading }
					searchLabel={ __( 'Search disputes', 'woocommerce' ) }
					empty={ __( 'No disputes found.', 'woocommerce' ) }
					getItemId={ getDisputeId }
					toolbarActions={
						<Button
							variant="secondary"
							onClick={ handleExport }
							isBusy={ isExporting }
							disabled={ isExporting }
						>
							{ __( 'Download disputes', 'woocommerce' ) }
						</Button>
					}
				/>
			</section>
		</div>
	);
};
