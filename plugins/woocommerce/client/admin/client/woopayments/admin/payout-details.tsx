/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
	getSettingsPaymentsProviderRouteUrl,
} from './overview/utils';
import { getWooPaymentsTransactionsSummary } from './money-movement/data';
import { getErrorMessage } from './money-movement/utils';
import './style.scss';

type PayoutTransactionSummary = Record< string, unknown >;

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
	value: string | number;
} ) => (
	<div>
		<dt>{ label }</dt>
		<dd>{ value }</dd>
	</div>
);

export const WooPaymentsPayoutDetailsPage = () => {
	const [ payout, setPayout ] = useState< WooPaymentsDeposit | null >( null );
	const [ summary, setSummary ] = useState< PayoutTransactionSummary >( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const location = useLocation();
	const payoutId = new URLSearchParams( location.search ).get( 'id' ) || '';

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

			try {
				const [ nextPayout, nextSummary ] = await Promise.all( [
					getWooPaymentsDeposit( payoutId ),
					getWooPaymentsTransactionsSummary( {
						deposit_id: payoutId,
					} ),
				] );

				if ( isMounted ) {
					setPayout( nextPayout );
					setSummary( nextSummary );
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
	}, [ payoutId ] );

	const loadingMessage = __( 'Loading payout details…', 'woocommerce' );
	let liveStatusMessage = __( 'Payout details loaded.', 'woocommerce' );

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
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
			<h2>{ __( 'Payout details', 'woocommerce' ) }</h2>
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
							label={ __( 'Payout ID', 'woocommerce' ) }
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
								payout.bank_reference_key ||
								__( 'Not available', 'woocommerce' )
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
						<h3>{ __( 'Payout transactions', 'woocommerce' ) }</h3>
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
					</section>
				</>
			) }
		</section>
	);
};

export default WooPaymentsPayoutDetailsPage;
