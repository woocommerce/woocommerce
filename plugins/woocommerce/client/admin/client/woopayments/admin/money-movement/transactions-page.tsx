/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useLocation, useNavigate } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	cancelWooPaymentsAuthorization,
	captureWooPaymentsAuthorization,
	getWooPaymentsAuthorizations,
	getWooPaymentsAuthorizationsSummary,
	getWooPaymentsTransactionsExportUrl,
	getWooPaymentsTransactions,
	getWooPaymentsTransactionsSummary,
	requestWooPaymentsTransactionsExport,
} from './data';
import type {
	WooPaymentsAuthorization,
	WooPaymentsAuthorizationsSummary,
	WooPaymentsMoneyMovementDataView,
	WooPaymentsMoneyMovementQuery,
	WooPaymentsTransaction,
} from './types';
import {
	buildMoneyMovementRoutePath,
	dataViewsViewToMoneyMovementQuery,
	moneyMovementQueryToDataViewsView,
	parseMoneyMovementQuery,
	sanitizeWooPaymentsAuthorizationsQuery,
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
type MoneyMovementSummary =
	| TransactionsSummary
	| WooPaymentsAuthorizationsSummary;
type ExportMessage = {
	text: string;
	isError?: boolean;
};
type MoneyMovementResource = 'transactions' | 'authorizations';
type AuthorizationAction = 'capture' | 'cancel';
type PendingAuthorizationAction = {
	action: AuthorizationAction;
	paymentIntentId: string;
} | null;
type NoticeDispatch = {
	createSuccessNotice: ( message: string ) => void;
	createErrorNotice: ( message: string ) => void;
};

const getSummaryCount = ( summary: MoneyMovementSummary ) => {
	const totalCount =
		'total_count' in summary ? summary.total_count : undefined;

	if ( typeof totalCount === 'number' ) {
		return totalCount;
	}

	const count = 'count' in summary ? summary.count : undefined;

	return typeof count === 'number' ? count : undefined;
};

const getSummaryTotal = ( summary: MoneyMovementSummary ) => {
	const total = 'total' in summary ? summary.total : undefined;

	if ( typeof total === 'number' ) {
		return total;
	}

	const gross = 'gross' in summary ? summary.gross : undefined;

	return typeof gross === 'number' ? gross : undefined;
};

const getSummaryCurrency = ( summary: MoneyMovementSummary ) =>
	'currency' in summary && typeof summary.currency === 'string'
		? summary.currency
		: undefined;

const shouldShowSummaryTotal = (
	summary: MoneyMovementSummary,
	resource: MoneyMovementResource
) => {
	if ( resource === 'transactions' ) {
		return true;
	}

	return ! (
		'all_currencies' in summary &&
		Array.isArray( summary.all_currencies ) &&
		summary.all_currencies.length > 1
	);
};

const getAuthorizationPaymentIntentId = ( item: WooPaymentsAuthorization ) =>
	item.payment_intent_id || item.id || '';

const getAuthorizationOrderId = ( item: WooPaymentsAuthorization ) => {
	const orderId = item.order_id;

	return typeof orderId === 'number' || typeof orderId === 'string'
		? String( orderId )
		: '';
};

const getAuthorizationCaptureBy = ( value?: string | number ) => {
	if ( ! value ) {
		return '-';
	}

	const timestamp =
		typeof value === 'number' && value < 10000000000 ? value * 1000 : value;
	const date = new Date( timestamp );

	if ( Number.isNaN( date.getTime() ) ) {
		return '-';
	}

	date.setUTCDate( date.getUTCDate() + 7 );

	return formatDate( date.toISOString() );
};

const getNotices = () =>
	dispatch( 'core/notices' ) as unknown as NoticeDispatch;

const buildTransactionsRoute = (
	view: WooPaymentsMoneyMovementDataView,
	currentQuery: WooPaymentsMoneyMovementQuery,
	isUncaptured: boolean
) => {
	const nextQuery = dataViewsViewToMoneyMovementQuery( view, currentQuery );
	const route = buildMoneyMovementRoutePath(
		'/woopayments/transactions',
		isUncaptured
			? sanitizeWooPaymentsAuthorizationsQuery( nextQuery )
			: nextQuery
	);

	return isUncaptured
		? `${ route }${ route.includes( '?' ) ? '&' : '?' }view=uncaptured`
		: route;
};

export const WooPaymentsTransactionsPage = () => {
	const location = useLocation();
	const navigate = useNavigate();
	const isUncaptured = useMemo( () => {
		const params = new URLSearchParams( location.search );

		return (
			params.get( 'tab' ) === 'uncaptured' ||
			params.get( 'view' ) === 'uncaptured'
		);
	}, [ location.search ] );
	const resource: MoneyMovementResource = isUncaptured
		? 'authorizations'
		: 'transactions';
	const [ transactions, setTransactions ] = useState<
		WooPaymentsTransaction[]
	>( [] );
	const [ authorizations, setAuthorizations ] = useState<
		WooPaymentsAuthorization[]
	>( [] );
	const [ totalCount, setTotalCount ] = useState( 0 );
	const [ summary, setSummary ] = useState< MoneyMovementSummary >( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ exportMessage, setExportMessage ] =
		useState< ExportMessage | null >( null );
	const [ isExporting, setIsExporting ] = useState( false );
	const [ pendingAuthorizationAction, setPendingAuthorizationAction ] =
		useState< PendingAuthorizationAction >( null );
	const [ viewPreferences, setViewPreferences ] = useState( () =>
		getMoneyMovementViewPreferences( resource )
	);
	const query = useMemo(
		() =>
			parseMoneyMovementQuery( location.search, {
				page: 1,
				pagesize: 25,
				sort: isUncaptured ? 'created' : 'date',
				direction: 'desc',
			} ),
		[ isUncaptured, location.search ]
	);
	const resourceQuery = useMemo(
		() =>
			isUncaptured
				? sanitizeWooPaymentsAuthorizationsQuery( query )
				: query,
		[ isUncaptured, query ]
	);
	const queryView = useMemo(
		() =>
			moneyMovementQueryToDataViewsView( resourceQuery, {
				fields: isUncaptured
					? [
							'authorized_date',
							'capture_by',
							'order',
							'risk',
							'amount',
							'customer',
							'actions',
					  ]
					: [ 'date', 'type', 'customer', 'amount' ],
				titleField: isUncaptured ? 'order' : 'type',
				showTitle: false,
			} ),
		[ isUncaptured, resourceQuery ]
	);
	const view = useMemo(
		() => mergeMoneyMovementViewPreferences( queryView, viewPreferences ),
		[ queryView, viewPreferences ]
	);
	const transactionFields = useMemo(
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
		setViewPreferences( getMoneyMovementViewPreferences( resource ) );
		setExportMessage( null );
	}, [ resource ] );

	useEffect( () => {
		recordEvent( 'page_view', {
			path: isUncaptured
				? 'payments_transactions_uncaptured'
				: 'payments_transactions',
		} );
	}, [ isUncaptured ] );

	const loadMoneyMovement = useCallback(
		async ( {
			setLoading = true,
			isCurrent = () => true,
		}: {
			setLoading?: boolean;
			isCurrent?: () => boolean;
		} = {} ) => {
			const canUpdate = () => isCurrent();

			if ( setLoading ) {
				setIsLoading( true );
			}

			try {
				if ( isUncaptured ) {
					const [ response, nextSummary ] = await Promise.all( [
						getWooPaymentsAuthorizations( resourceQuery ),
						getWooPaymentsAuthorizationsSummary( resourceQuery ),
					] );

					if ( canUpdate() ) {
						setAuthorizations( response.data || [] );
						setTotalCount(
							response.total_count ??
								getSummaryCount( nextSummary ) ??
								0
						);
						setSummary( nextSummary );
						setErrorMessage( null );
					}

					return;
				}

				const [ response, nextSummary ] = await Promise.all( [
					getWooPaymentsTransactions( resourceQuery ),
					getWooPaymentsTransactionsSummary( resourceQuery ),
				] );

				if ( canUpdate() ) {
					setTransactions( response.data || [] );
					setTotalCount( response.total_count || 0 );
					setSummary( nextSummary );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( canUpdate() ) {
					setErrorMessage(
						getErrorMessage(
							error,
							isUncaptured
								? __(
										'Unable to load WooPayments uncaptured transactions.',
										'woocommerce'
								  )
								: __(
										'Unable to load WooPayments transactions.',
										'woocommerce'
								  )
						)
					);
				}
			} finally {
				if ( setLoading && canUpdate() ) {
					setIsLoading( false );
				}
			}
		},
		[ isUncaptured, resourceQuery ]
	);

	useEffect( () => {
		let isMounted = true;

		loadMoneyMovement( {
			isCurrent: () => isMounted,
		} );

		return () => {
			isMounted = false;
		};
	}, [ loadMoneyMovement ] );

	const handleViewChange = ( nextView: WooPaymentsMoneyMovementDataView ) => {
		setViewPreferences(
			setMoneyMovementViewPreferences( resource, nextView )
		);
		navigate(
			buildTransactionsRoute( nextView, resourceQuery, isUncaptured )
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

	const handleAuthorizationAction = async (
		authorization: WooPaymentsAuthorization,
		action: AuthorizationAction
	) => {
		const paymentIntentId =
			getAuthorizationPaymentIntentId( authorization );
		const orderId = Number( getAuthorizationOrderId( authorization ) );

		if (
			! paymentIntentId ||
			! Number.isFinite( orderId ) ||
			orderId <= 0
		) {
			getNotices().createErrorNotice(
				__(
					'Unable to process this authorization because the order details are incomplete.',
					'woocommerce'
				)
			);
			return;
		}

		setPendingAuthorizationAction( {
			action,
			paymentIntentId,
		} );

		try {
			if ( action === 'capture' ) {
				await captureWooPaymentsAuthorization(
					orderId,
					paymentIntentId
				);
				await loadMoneyMovement( { setLoading: false } );
				setPendingAuthorizationAction( null );
				getNotices().createSuccessNotice(
					sprintf(
						/* translators: %s: order ID. */
						__(
							'Payment for order #%s captured successfully.',
							'woocommerce'
						),
						orderId
					)
				);
			} else {
				await cancelWooPaymentsAuthorization(
					orderId,
					paymentIntentId
				);
				await loadMoneyMovement( { setLoading: false } );
				setPendingAuthorizationAction( null );
				getNotices().createSuccessNotice(
					sprintf(
						/* translators: %s: order ID. */
						__(
							'Payment for order #%s canceled successfully.',
							'woocommerce'
						),
						orderId
					)
				);
			}
		} catch ( error ) {
			setPendingAuthorizationAction( null );
			getNotices().createErrorNotice(
				sprintf(
					/* translators: 1: action name, 2: order ID, 3: error message. */
					__(
						'Unable to %1$s authorization for order #%2$s. %3$s',
						'woocommerce'
					),
					action,
					orderId,
					getErrorMessage(
						error,
						__(
							'Please refresh the page and try again.',
							'woocommerce'
						)
					)
				)
			);
		}
	};

	const authorizationFields = [
		{
			id: 'authorized_date',
			label: __( 'Authorized date', 'woocommerce' ),
			enableHiding: true,
			render: ( { item }: { item: WooPaymentsAuthorization } ) =>
				formatDate( item.created ),
		},
		{
			id: 'capture_by',
			label: __( 'Capture by', 'woocommerce' ),
			enableHiding: true,
			render: ( { item }: { item: WooPaymentsAuthorization } ) =>
				getAuthorizationCaptureBy( item.created ),
		},
		{
			id: 'order',
			label: __( 'Order', 'woocommerce' ),
			enableHiding: false,
			render: ( { item }: { item: WooPaymentsAuthorization } ) => {
				const orderId = getAuthorizationOrderId( item );

				return orderId ? `#${ orderId }` : '-';
			},
		},
		{
			id: 'risk',
			label: __( 'Risk', 'woocommerce' ),
			enableHiding: true,
			render: ( { item }: { item: WooPaymentsAuthorization } ) =>
				formatLabel(
					item.risk_level === undefined
						? undefined
						: String( item.risk_level )
				),
		},
		{
			id: 'amount',
			label: __( 'Amount', 'woocommerce' ),
			enableHiding: true,
			render: ( { item }: { item: WooPaymentsAuthorization } ) =>
				formatAmount( item.amount, item.currency ),
		},
		{
			id: 'customer',
			label: __( 'Customer', 'woocommerce' ),
			enableHiding: true,
			enableSorting: false,
			render: ( { item }: { item: WooPaymentsAuthorization } ) =>
				item.customer_name || item.customer_email || '-',
		},
		{
			id: 'actions',
			label: __( 'Actions', 'woocommerce' ),
			enableHiding: false,
			enableSorting: false,
			render: ( { item }: { item: WooPaymentsAuthorization } ) => {
				const paymentIntentId = getAuthorizationPaymentIntentId( item );
				const orderId = getAuthorizationOrderId( item );
				const pending =
					pendingAuthorizationAction?.paymentIntentId ===
					paymentIntentId;
				const pendingAction = pending
					? pendingAuthorizationAction?.action
					: null;

				return (
					<div className="woocommerce-woopayments-money-movement__row-actions">
						<Button
							variant="primary"
							isBusy={ pendingAction === 'capture' }
							disabled={ pending }
							onClick={ () =>
								handleAuthorizationAction( item, 'capture' )
							}
							aria-label={
								pendingAction === 'capture'
									? sprintf(
											/* translators: %s: order ID. */
											__(
												'Capturing authorization for order #%s',
												'woocommerce'
											),
											orderId
									  )
									: sprintf(
											/* translators: %s: order ID. */
											__(
												'Capture authorization for order #%s',
												'woocommerce'
											),
											orderId
									  )
							}
						>
							{ __( 'Capture', 'woocommerce' ) }
						</Button>
						<Button
							variant="secondary"
							isDestructive
							isBusy={ pendingAction === 'cancel' }
							disabled={ pending }
							onClick={ () =>
								handleAuthorizationAction( item, 'cancel' )
							}
							aria-label={
								pendingAction === 'cancel'
									? sprintf(
											/* translators: %s: order ID. */
											__(
												'Canceling authorization for order #%s',
												'woocommerce'
											),
											orderId
									  )
									: sprintf(
											/* translators: %s: order ID. */
											__(
												'Cancel authorization for order #%s',
												'woocommerce'
											),
											orderId
									  )
							}
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
					</div>
				);
			},
		},
	];

	const rows = isUncaptured ? authorizations : transactions;
	let liveStatusMessage = isUncaptured
		? __( 'Uncaptured transactions loaded.', 'woocommerce' )
		: __( 'Transactions loaded.', 'woocommerce' );
	const loadingMessage = isUncaptured
		? __( 'Loading uncaptured transactions…', 'woocommerce' )
		: __( 'Loading transactions…', 'woocommerce' );
	const emptyMessage = isUncaptured
		? __( 'No uncaptured transactions found.', 'woocommerce' )
		: __( 'No transactions found.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
	} else if ( rows.length === 0 ) {
		liveStatusMessage = emptyMessage;
	}

	const summaryCount = getSummaryCount( summary ) ?? totalCount;
	const summaryTotal = getSummaryTotal( summary );
	const summaryCurrency = getSummaryCurrency( summary );
	const tabTransactionsUrl = getSettingsPaymentsProviderRouteUrl(
		'/woopayments/transactions'
	);
	const tabUncapturedUrl = getSettingsPaymentsProviderRouteUrl(
		'/woopayments/transactions?view=uncaptured'
	);
	const summaryCountLabel = isUncaptured
		? sprintf(
				/* translators: %d: uncaptured transactions count. */
				__( '%d uncaptured transactions', 'woocommerce' ),
				summaryCount
		  )
		: sprintf(
				/* translators: %d: transactions count. */
				__( '%d transactions', 'woocommerce' ),
				summaryCount
		  );

	return (
		<div className="woocommerce-woopayments-money-movement">
			<SpotlightPromotion />
			<section aria-busy={ isLoading }>
				<h2>
					{ isUncaptured
						? __( 'Uncaptured transactions', 'woocommerce' )
						: __( 'Transactions', 'woocommerce' ) }
				</h2>
				<nav
					className="woocommerce-woopayments-money-movement__tabs"
					aria-label={ __( 'Transaction views', 'woocommerce' ) }
				>
					<a
						href={ tabTransactionsUrl }
						aria-current={ isUncaptured ? undefined : 'page' }
					>
						{ __( 'Transactions', 'woocommerce' ) }
					</a>
					<a
						href={ tabUncapturedUrl }
						aria-current={ isUncaptured ? 'page' : undefined }
					>
						{ __( 'Uncaptured', 'woocommerce' ) }
					</a>
				</nav>
				<LiveStatusMessage isError={ !! errorMessage }>
					{ liveStatusMessage }
				</LiveStatusMessage>
				{ isLoading && (
					<StatusMessage>{ loadingMessage }</StatusMessage>
				) }
				{ errorMessage && (
					<StatusMessage isError>{ errorMessage }</StatusMessage>
				) }
				<div className="woocommerce-woopayments-money-movement__summary">
					<span>{ summaryCountLabel }</span>
					{ typeof summaryTotal === 'number' &&
						shouldShowSummaryTotal( summary, resource ) && (
							<span>
								{ formatAmount(
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
				{ isUncaptured ? (
					<WooPaymentsMoneyMovementDataViews
						fields={ isLoading ? [] : authorizationFields }
						rows={ authorizations }
						view={ view }
						onChangeView={ handleViewChange }
						total={ totalCount || authorizations.length }
						isLoading={ isLoading }
						searchLabel={ __(
							'Search uncaptured transactions',
							'woocommerce'
						) }
						empty={ emptyMessage }
						getItemId={ getAuthorizationPaymentIntentId }
					/>
				) : (
					<WooPaymentsMoneyMovementDataViews
						fields={ transactionFields }
						rows={ transactions }
						view={ view }
						onChangeView={ handleViewChange }
						total={ totalCount || transactions.length }
						isLoading={ isLoading }
						searchLabel={ __(
							'Search transactions',
							'woocommerce'
						) }
						empty={ emptyMessage }
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
				) }
			</section>
		</div>
	);
};
