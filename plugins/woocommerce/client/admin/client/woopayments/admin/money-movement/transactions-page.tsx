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
	getWooPaymentsTransactionsExportUrl,
	getWooPaymentsTransactions,
	getWooPaymentsTransactionsSummary,
	requestWooPaymentsTransactionsExport,
} from './data';
import type {
	WooPaymentsMoneyMovementDataView,
	WooPaymentsTransaction,
} from './types';
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
	getResourceId,
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

type TransactionsSummary = Record< string, unknown >;
type ExportMessage = {
	text: string;
	isError?: boolean;
};

const getSummaryCount = ( summary: TransactionsSummary ) => {
	const count = summary.total_count || summary.count;

	return typeof count === 'number' ? count : undefined;
};

const getSummaryTotal = ( summary: TransactionsSummary ) => {
	const total = summary.total || summary.gross;

	return typeof total === 'number' ? total : undefined;
};

const getSummaryCurrency = ( summary: TransactionsSummary ) =>
	typeof summary.currency === 'string' ? summary.currency : undefined;

export const WooPaymentsTransactionsPage = () => {
	const [ transactions, setTransactions ] = useState<
		WooPaymentsTransaction[]
	>( [] );
	const [ totalCount, setTotalCount ] = useState( 0 );
	const [ summary, setSummary ] = useState< TransactionsSummary >( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ exportMessage, setExportMessage ] =
		useState< ExportMessage | null >( null );
	const [ isExporting, setIsExporting ] = useState( false );
	const [ viewPreferences, setViewPreferences ] = useState( () =>
		getMoneyMovementViewPreferences( 'transactions' )
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
				fields: [ 'date', 'type', 'customer', 'amount' ],
				titleField: 'type',
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
				render: ( { item }: { item: WooPaymentsTransaction } ) =>
					formatDate( item.date || item.created ),
			},
			{
				id: 'type',
				label: __( 'Type', 'woocommerce' ),
				enableHiding: false,
				render: ( { item }: { item: WooPaymentsTransaction } ) => {
					const id = getResourceId( item );

					return (
						<a
							href={ getSettingsPaymentsProviderRouteUrl(
								getTransactionDetailsRoute( item )
							) }
							aria-label={ sprintf(
								/* translators: 1: transaction type, 2: transaction ID. */
								__(
									'View transaction details for %1$s transaction %2$s',
									'woocommerce'
								),
								formatLabel( item.type ),
								id
							) }
						>
							{ formatLabel( item.type ) }
						</a>
					);
				},
			},
			{
				id: 'customer',
				label: __( 'Customer', 'woocommerce' ),
				enableHiding: true,
				enableSorting: false,
				render: ( { item }: { item: WooPaymentsTransaction } ) =>
					item.customer_name || item.customer_email || '-',
			},
			{
				id: 'amount',
				label: __( 'Amount', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsTransaction } ) =>
					formatAmount( item.amount, item.currency ),
			},
		],
		[]
	);

	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_transactions',
		} );
	}, [] );

	useEffect( () => {
		let isMounted = true;

		const loadTransactions = async () => {
			setIsLoading( true );

			try {
				const [ response, nextSummary ] = await Promise.all( [
					getWooPaymentsTransactions( query ),
					getWooPaymentsTransactionsSummary( query ),
				] );

				if ( isMounted ) {
					setTransactions( response.data || [] );
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
								'Unable to load WooPayments transactions.',
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

		loadTransactions();

		return () => {
			isMounted = false;
		};
	}, [ query ] );

	const handleViewChange = ( nextView: WooPaymentsMoneyMovementDataView ) => {
		setViewPreferences(
			setMoneyMovementViewPreferences( 'transactions', nextView )
		);
		navigate(
			buildMoneyMovementRoutePath(
				'/woopayments/transactions',
				dataViewsViewToMoneyMovementQuery( nextView, query )
			)
		);
	};
	const handleExport = async () => {
		setIsExporting( true );
		setExportMessage( null );

		try {
			await runWooPaymentsExport( {
				requestExport: () =>
					requestWooPaymentsTransactionsExport( query ),
				getExportUrl: getWooPaymentsTransactionsExportUrl,
			} );
			setExportMessage( {
				text: __(
					'Your transactions export has started downloading.',
					'woocommerce'
				),
			} );
		} catch ( error ) {
			setExportMessage( {
				text: getErrorMessage(
					error,
					__(
						'Unable to export WooPayments transactions.',
						'woocommerce'
					)
				),
				isError: true,
			} );
		} finally {
			setIsExporting( false );
		}
	};
	let liveStatusMessage = __( 'Transactions loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = __( 'Loading transactions…', 'woocommerce' );
	} else if ( transactions.length === 0 ) {
		liveStatusMessage = __( 'No transactions found.', 'woocommerce' );
	}

	const summaryCount = getSummaryCount( summary ) ?? totalCount;
	const summaryTotal = getSummaryTotal( summary );
	const summaryCurrency = getSummaryCurrency( summary );

	return (
		<div className="woocommerce-woopayments-money-movement">
			<SpotlightPromotion />
			<section aria-busy={ isLoading }>
				<h2>{ __( 'Transactions', 'woocommerce' ) }</h2>
				<LiveStatusMessage isError={ !! errorMessage }>
					{ liveStatusMessage }
				</LiveStatusMessage>
				{ isLoading && (
					<StatusMessage>
						{ __( 'Loading transactions…', 'woocommerce' ) }
					</StatusMessage>
				) }
				{ errorMessage && (
					<StatusMessage isError>{ errorMessage }</StatusMessage>
				) }
				<div className="woocommerce-woopayments-money-movement__summary">
					<span>
						{ sprintf(
							/* translators: %d: transactions count. */
							__( '%d transactions', 'woocommerce' ),
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
					rows={ transactions }
					view={ view }
					onChangeView={ handleViewChange }
					total={ totalCount || transactions.length }
					isLoading={ isLoading }
					searchLabel={ __( 'Search transactions', 'woocommerce' ) }
					empty={ __( 'No transactions found.', 'woocommerce' ) }
					getItemId={ getResourceId }
					toolbarActions={
						<Button
							variant="secondary"
							onClick={ handleExport }
							isBusy={ isExporting }
							disabled={ isExporting }
						>
							{ __( 'Download transactions', 'woocommerce' ) }
						</Button>
					}
				/>
			</section>
		</div>
	);
};
