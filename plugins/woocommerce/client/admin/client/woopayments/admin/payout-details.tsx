/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import type { ReactNode } from 'react';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getWooPaymentsDeposit } from './overview/data';
import type { WooPaymentsDeposit } from './overview/types';
import {
	formatPayoutDate,
	formatPayoutStatus,
	formatWooPaymentsAmount,
} from './overview/utils';
import {
	getWooPaymentsTransactions,
	getWooPaymentsTransactionsSummary,
} from './money-movement/data';
import type {
	WooPaymentsMoneyMovementDataView,
	WooPaymentsMoneyMovementQuery,
	WooPaymentsTransaction,
} from './money-movement/types';
import { WooPaymentsMoneyMovementDataViews } from './money-movement/dataviews';
import {
	dataViewsViewToMoneyMovementQuery,
	moneyMovementQueryToDataViewsView,
} from './money-movement/query';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getErrorMessage,
	getResourceId,
	getTransactionDetailsRoute,
} from './money-movement/utils';
import { getSettingsPaymentsProviderRouteUrl } from './utils';
import './style.scss';

type PayoutTransactionSummary = Record< string, unknown >;

const INSTANT_PAYOUTS_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/instant-payouts/#transactions';

const DEFAULT_PAYOUT_TRANSACTIONS_QUERY = {
	page: 1,
	pagesize: 25,
	sort: 'date',
	direction: 'desc',
} as const satisfies WooPaymentsMoneyMovementQuery;

const getDefaultPayoutTransactionsView = () =>
	moneyMovementQueryToDataViewsView( DEFAULT_PAYOUT_TRANSACTIONS_QUERY, {
		fields: [ 'date', 'type', 'amount' ],
		titleField: 'type',
		showTitle: false,
	} );

const getPayoutTransactionsQuery = (
	payoutId: string,
	view: WooPaymentsMoneyMovementDataView
): WooPaymentsMoneyMovementQuery => ( {
	...dataViewsViewToMoneyMovementQuery(
		view,
		DEFAULT_PAYOUT_TRANSACTIONS_QUERY
	),
	deposit_id: payoutId,
} );

const getPayoutTransactionsSummaryQuery = (
	query: WooPaymentsMoneyMovementQuery
): WooPaymentsMoneyMovementQuery => ( {
	deposit_id: query.deposit_id,
	...( query.search ? { search: query.search } : {} ),
} );

const getSummaryNumber = (
	summary: PayoutTransactionSummary,
	keys: string[]
) => {
	for ( const key of keys ) {
		const value = summary[ key ];

		if ( typeof value === 'number' ) {
			return value;
		}
	}

	return undefined;
};

const getSummaryCurrency = (
	summary: PayoutTransactionSummary,
	payout: WooPaymentsDeposit
) => {
	const currency = summary.currency;

	return typeof currency === 'string' ? currency : payout.currency;
};

const formatSummaryAmount = (
	value: number | undefined,
	currency?: string | null
) =>
	typeof value === 'number'
		? formatWooPaymentsAmount( value, currency )
		: '-';

const SummaryRow = ( {
	label,
	value,
}: {
	label: string;
	value: ReactNode;
} ) => (
	<div>
		<dt>{ label }</dt>
		<dd>{ value }</dd>
	</div>
);

export const WooPaymentsPayoutDetailsPage = () => {
	const [ payout, setPayout ] = useState< WooPaymentsDeposit | null >( null );
	const [ summary, setSummary ] = useState< PayoutTransactionSummary >( {} );
	const [ transactions, setTransactions ] = useState<
		WooPaymentsTransaction[]
	>( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ copyStatusMessage, setCopyStatusMessage ] = useState<
		string | null
	>( null );
	const [ transactionsView, setTransactionsView ] =
		useState< WooPaymentsMoneyMovementDataView >(
			getDefaultPayoutTransactionsView
		);
	const location = useLocation();
	const payoutId = new URLSearchParams( location.search ).get( 'id' ) || '';
	const transactionsQuery = useMemo(
		() => getPayoutTransactionsQuery( payoutId, transactionsView ),
		[ payoutId, transactionsView ]
	);
	const payoutTransactionFields = useMemo(
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
					const transactionId = getResourceId( item );

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
								transactionId
							) }
						>
							{ formatLabel( item.type ) }
						</a>
					);
				},
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
		let isMounted = true;

		const loadPayoutDetails = async () => {
			if ( ! payoutId ) {
				setErrorMessage(
					__( 'A payout ID is required.', 'woocommerce' )
				);
				setIsLoading( false );
				return;
			}

			setIsLoading( true );
			setCopyStatusMessage( null );

			try {
				const nextPayout = await getWooPaymentsDeposit( payoutId );
				const isInstantPayout = nextPayout.automatic === false;
				const [ nextSummary, nextTransactions ] = await Promise.all( [
					getWooPaymentsTransactionsSummary(
						getPayoutTransactionsSummaryQuery( transactionsQuery )
					),
					isInstantPayout
						? Promise.resolve( { data: [] } )
						: getWooPaymentsTransactions( transactionsQuery ),
				] );

				if ( isMounted ) {
					setPayout( nextPayout );
					setSummary( nextSummary );
					setTransactions( nextTransactions.data || [] );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments payout details.',
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

		loadPayoutDetails();

		return () => {
			isMounted = false;
		};
	}, [ payoutId, transactionsQuery ] );

	const copyBankReferenceId = async () => {
		if ( ! payout?.bank_reference_key ) {
			return;
		}

		try {
			if ( ! navigator.clipboard?.writeText ) {
				throw new Error( 'Clipboard API is unavailable.' );
			}

			await navigator.clipboard.writeText( payout.bank_reference_key );
			const successMessage = __(
				'Bank reference ID copied.',
				'woocommerce'
			);
			setCopyStatusMessage( successMessage );
			speak( successMessage, 'polite' );
		} catch ( error ) {
			const copyErrorMessage = __(
				'Unable to copy bank reference ID to clipboard.',
				'woocommerce'
			);
			setCopyStatusMessage( copyErrorMessage );
			speak( copyErrorMessage, 'polite' );
		}
	};

	const loadingMessage = __( 'Loading payout details…', 'woocommerce' );
	let liveStatusMessage = __( 'Payout details loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
	} else if ( copyStatusMessage ) {
		liveStatusMessage = copyStatusMessage;
	}

	const summaryCurrency = payout
		? getSummaryCurrency( summary, payout )
		: null;
	const transactionCount = getSummaryNumber( summary, [
		'count',
		'total_count',
	] );
	const grossAmount = getSummaryNumber( summary, [ 'total', 'gross' ] );
	const fees = getSummaryNumber( summary, [ 'fees', 'fee' ] );
	const netAmount = getSummaryNumber( summary, [ 'net', 'amount' ] );
	const isWithdrawal = payout?.type === 'withdrawal';
	const isInstantPayout = payout?.automatic === false;
	const payoutLabel = isWithdrawal
		? __( 'withdrawal', 'woocommerce' )
		: __( 'payout', 'woocommerce' );
	const payoutTitle = isWithdrawal
		? __( 'Withdrawal details', 'woocommerce' )
		: __( 'Payout details', 'woocommerce' );
	const payoutIdLabel = isWithdrawal
		? __( 'Withdrawal ID', 'woocommerce' )
		: __( 'Payout ID', 'woocommerce' );
	const payoutTransactionsTitle = isWithdrawal
		? __( 'Withdrawal transactions', 'woocommerce' )
		: __( 'Payout transactions', 'woocommerce' );
	const allTransactionsUrl =
		payout &&
		getSettingsPaymentsProviderRouteUrl(
			`/woopayments/transactions?deposit_id=${ encodeURIComponent(
				payout.id
			) }`
		);
	const bankReferenceId = payout?.bank_reference_key;

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<a
				href={ getSettingsPaymentsProviderRouteUrl(
					'/woopayments/payouts'
				) }
			>
				{ __( 'Back to payout history', 'woocommerce' ) }
			</a>
			<h2>{ payoutTitle }</h2>
			<p
				className="screen-reader-text"
				role={ errorMessage ? 'alert' : 'status' }
				aria-live={ errorMessage ? 'assertive' : 'polite' }
			>
				{ liveStatusMessage }
			</p>
			{ isLoading && (
				<p className="woocommerce-woopayments-money-movement__status">
					{ loadingMessage }
				</p>
			) }
			{ errorMessage && (
				<p className="woocommerce-woopayments-money-movement__status">
					{ errorMessage }
				</p>
			) }
			{ payout && ! errorMessage && (
				<>
					<dl className="woocommerce-woopayments-money-movement__details">
						<SummaryRow
							label={ payoutIdLabel }
							value={ payout.id }
						/>
						<SummaryRow
							label={ __( 'Dispatch date', 'woocommerce' ) }
							value={ formatPayoutDate( payout ) }
						/>
						<SummaryRow
							label={ __( 'Status', 'woocommerce' ) }
							value={ formatPayoutStatus( payout.status ) }
						/>
						<SummaryRow
							label={ __( 'Amount', 'woocommerce' ) }
							value={ formatWooPaymentsAmount(
								payout.amount,
								payout.currency
							) }
						/>
						<SummaryRow
							label={ __( 'Bank account', 'woocommerce' ) }
							value={
								payout.bankAccount ||
								__( 'Not available', 'woocommerce' )
							}
						/>
						<SummaryRow
							label={ __( 'Bank reference ID', 'woocommerce' ) }
							value={
								bankReferenceId ? (
									<span className="woocommerce-woopayments-money-movement__copyable-value">
										<span>{ bankReferenceId }</span>
										<Button
											variant="secondary"
											onClick={ copyBankReferenceId }
											aria-label={ __(
												'Copy bank reference ID to clipboard',
												'woocommerce'
											) }
										>
											{ __( 'Copy', 'woocommerce' ) }
										</Button>
									</span>
								) : (
									__( 'Not available', 'woocommerce' )
								)
							}
						/>
					</dl>
					{ ( payout.failure_message || payout.failure_code ) && (
						<p className="woocommerce-woopayments-money-movement__notice">
							<strong>
								{ __( 'Failure reason:', 'woocommerce' ) }
							</strong>{ ' ' }
							{ payout.failure_message || payout.failure_code }
						</p>
					) }
					<section className="woocommerce-woopayments-overview-card">
						<h3>{ payoutTransactionsTitle }</h3>
						<dl className="woocommerce-woopayments-money-movement__details">
							<SummaryRow
								label={ __( 'Transactions', 'woocommerce' ) }
								value={ transactionCount ?? '-' }
							/>
							<SummaryRow
								label={ __( 'Gross amount', 'woocommerce' ) }
								value={ formatSummaryAmount(
									grossAmount,
									summaryCurrency
								) }
							/>
							<SummaryRow
								label={ __( 'Fees', 'woocommerce' ) }
								value={ formatSummaryAmount(
									fees,
									summaryCurrency
								) }
							/>
							<SummaryRow
								label={ __( 'Net amount', 'woocommerce' ) }
								value={ formatSummaryAmount(
									netAmount,
									summaryCurrency
								) }
							/>
						</dl>
						{ isInstantPayout ? (
							<p className="woocommerce-woopayments-money-movement__notice">
								{ __(
									"We're unable to show transaction history on instant payouts.",
									'woocommerce'
								) }{ ' ' }
								<a href={ INSTANT_PAYOUTS_DOCS_URL }>
									{ __( 'Learn more', 'woocommerce' ) }
								</a>
							</p>
						) : (
							<>
								<WooPaymentsMoneyMovementDataViews
									fields={ payoutTransactionFields }
									rows={ transactions }
									view={ transactionsView }
									onChangeView={ setTransactionsView }
									total={
										transactionCount ?? transactions.length
									}
									isLoading={ isLoading }
									searchLabel={ __(
										'Search payout transactions',
										'woocommerce'
									) }
									empty={
										<p>
											{ __(
												'No transactions found for this payout.',
												'woocommerce'
											) }
										</p>
									}
									getItemId={ getResourceId }
								/>
								{ allTransactionsUrl && (
									<p className="woocommerce-woopayments-money-movement__footer-actions">
										<a href={ allTransactionsUrl }>
											{ sprintf(
												/* translators: %s: payout or withdrawal. */
												__(
													'View all transactions in this %s',
													'woocommerce'
												),
												payoutLabel
											) }
										</a>
									</p>
								) }
							</>
						) }
					</section>
				</>
			) }
		</section>
	);
};

export default WooPaymentsPayoutDetailsPage;
