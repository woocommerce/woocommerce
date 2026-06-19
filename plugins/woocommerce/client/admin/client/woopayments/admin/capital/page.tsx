/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsCapitalActiveLoanSummary,
	getWooPaymentsCapitalLoans,
} from './data';
import type {
	WooPaymentsCapitalLoan,
	WooPaymentsCapitalSummary,
} from './types';
import { formatWooPaymentsAmount } from '../overview/utils';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';
import { getErrorMessage } from '../money-movement/utils';
import { WooPaymentsTestModeNotice } from '../test-mode-notice';

const getDateValue = ( value: string | number ): string | number => {
	if ( typeof value === 'number' ) {
		return value < 10000000000 ? value * 1000 : value;
	}

	const match = value
		.trim()
		.match( /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/ );

	if ( ! match ) {
		return value;
	}

	const [ , year, month, day, hour, minute, second ] = match;

	return Date.UTC(
		Number( year ),
		Number( month ) - 1,
		Number( day ),
		Number( hour ),
		Number( minute ),
		Number( second )
	);
};

const formatDate = ( value?: string | number | null ) => {
	if ( ! value ) {
		return '-';
	}

	const date = new Date( getDateValue( value ) );

	if ( Number.isNaN( date.getTime() ) ) {
		return '-';
	}

	return date.toLocaleDateString( undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	} );
};

const formatPercent = ( value: number ) =>
	`${ Number( ( value * 100 ).toFixed( 2 ) ) }%`;

const getLoanStatus = ( loan: WooPaymentsCapitalLoan ) =>
	loan.fully_paid_at
		? sprintf(
				/* translators: %s: loan paid-off date. */
				__( 'Paid off: %s', 'woocommerce' ),
				formatDate( loan.fully_paid_at )
		  )
		: __( 'Active', 'woocommerce' );

const getLoanStatusClassName = ( loan: WooPaymentsCapitalLoan ) =>
	loan.fully_paid_at
		? 'woocommerce-woopayments-capital__status-chip is-paid-off'
		: 'woocommerce-woopayments-capital__status-chip is-active';

const ActiveLoanSummary = ( {
	summary,
	loans,
}: {
	summary: WooPaymentsCapitalSummary;
	loans: WooPaymentsCapitalLoan[];
} ) => {
	const details = summary.details;

	if ( ! details ) {
		return null;
	}

	const totalDue = details.advance_amount + details.fee_amount;
	const periodDue =
		details.current_repayment_interval.paid_amount +
		details.current_repayment_interval.remaining_amount;
	const activeLoanId =
		loans.find( ( loan ) => ! loan.fully_paid_at )?.stripe_loan_id || '';
	const activeLoanUrl = activeLoanId
		? getSettingsPaymentsProviderRouteUrl(
				`/woopayments/transactions?loan_id_is=${ encodeURIComponent(
					activeLoanId
				) }`
		  )
		: '';

	return (
		<section className="woocommerce-woopayments-capital__section">
			<div className="woocommerce-woopayments-capital__section-header">
				<h3>{ __( 'Active loan overview', 'woocommerce' ) }</h3>
				{ activeLoanUrl && (
					<a
						className="woocommerce-woopayments-capital__view-transactions"
						href={ activeLoanUrl }
					>
						{ __( 'View transactions', 'woocommerce' ) }
					</a>
				) }
			</div>
			<dl className="woocommerce-woopayments-capital__summary">
				<div>
					<dt>{ __( 'Total repaid', 'woocommerce' ) }</dt>
					<dd>
						{ sprintf(
							/* translators: 1: paid amount, 2: total amount. */
							__( '%1$s of %2$s', 'woocommerce' ),
							formatWooPaymentsAmount(
								details.paid_amount,
								details.currency
							),
							formatWooPaymentsAmount(
								totalDue,
								details.currency
							)
						) }
					</dd>
				</div>
				<div>
					<dt>
						{ sprintf(
							/* translators: %s: repayment period due date. */
							__(
								'Repaid this period (until %s)',
								'woocommerce'
							),
							formatDate(
								details.current_repayment_interval.due_at
							)
						) }
					</dt>
					<dd>
						{ sprintf(
							/* translators: 1: paid amount, 2: total period amount. */
							__( '%1$s of %2$s minimum', 'woocommerce' ),
							formatWooPaymentsAmount(
								details.current_repayment_interval.paid_amount,
								details.currency
							),
							formatWooPaymentsAmount(
								periodDue,
								details.currency
							)
						) }
					</dd>
				</div>
				<div>
					<dt>{ __( 'Loan disbursed', 'woocommerce' ) }</dt>
					<dd>{ formatDate( details.advance_paid_out_at ) }</dd>
				</div>
				<div>
					<dt>{ __( 'Loan amount', 'woocommerce' ) }</dt>
					<dd>
						{ formatWooPaymentsAmount(
							details.advance_amount,
							details.currency
						) }
					</dd>
				</div>
				<div>
					<dt>{ __( 'Fixed fee', 'woocommerce' ) }</dt>
					<dd>
						{ formatWooPaymentsAmount(
							details.fee_amount,
							details.currency
						) }
					</dd>
				</div>
				<div>
					<dt>{ __( 'Withhold rate', 'woocommerce' ) }</dt>
					<dd>{ formatPercent( details.withhold_rate ) }</dd>
				</div>
				<div>
					<dt>{ __( 'First paydown', 'woocommerce' ) }</dt>
					<dd>{ formatDate( details.repayments_begin_at ) }</dd>
				</div>
			</dl>
		</section>
	);
};

const LoanStatusChip = ( { loan }: { loan: WooPaymentsCapitalLoan } ) => (
	<span className={ getLoanStatusClassName( loan ) }>
		{ getLoanStatus( loan ) }
	</span>
);

const getLoanTransactionsUrl = ( loan: WooPaymentsCapitalLoan ) =>
	getSettingsPaymentsProviderRouteUrl(
		`/woopayments/transactions?loan_id_is=${ encodeURIComponent(
			loan.stripe_loan_id
		) }`
	);

const LoanActionLink = ( { loan }: { loan: WooPaymentsCapitalLoan } ) => (
	<a
		className="woocommerce-woopayments-capital__loan-action"
		href={ getLoanTransactionsUrl( loan ) }
	>
		{ __( 'View transactions', 'woocommerce' ) }
		<span className="screen-reader-text">
			{ sprintf(
				/* translators: %s: loan ID. */
				__( ' for loan %s', 'woocommerce' ),
				loan.stripe_loan_id
			) }
		</span>
	</a>
);

const LoanListSummary = ( { loans }: { loans: WooPaymentsCapitalLoan[] } ) => {
	if ( loans.length === 0 ) {
		return null;
	}

	const currencies = Array.from(
		new Set( loans.map( ( loan ) => loan.currency ) )
	);
	const loanCount = sprintf(
		/* translators: %d: number of Capital loans. */
		_n( '%d loan', '%d loans', loans.length, 'woocommerce' ),
		loans.length
	);

	if ( currencies.length !== 1 ) {
		return (
			<div className="woocommerce-woopayments-capital__list-summary">
				<span>{ loanCount }</span>
			</div>
		);
	}

	const currency = currencies[ 0 ];
	const totalAmount = loans.reduce(
		( total, loan ) => total + loan.amount,
		0
	);
	const totalFees = loans.reduce(
		( total, loan ) => total + loan.fee_amount,
		0
	);

	return (
		<div className="woocommerce-woopayments-capital__list-summary">
			<span>{ loanCount }</span>
			<span>
				{ sprintf(
					/* translators: %s: formatted Capital loan total amount. */
					__( '%s total', 'woocommerce' ),
					formatWooPaymentsAmount( totalAmount, currency )
				) }
			</span>
			<span>
				{ sprintf(
					/* translators: %s: formatted Capital loan fixed-fee total. */
					__( '%s fixed fees', 'woocommerce' ),
					formatWooPaymentsAmount( totalFees, currency )
				) }
			</span>
		</div>
	);
};

export const WooPaymentsCapitalPage = () => {
	const [ summary, setSummary ] = useState< WooPaymentsCapitalSummary >( {} );
	const [ loans, setLoans ] = useState< WooPaymentsCapitalLoan[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );

	useEffect( () => {
		let isMounted = true;

		const loadCapitalData = async () => {
			setIsLoading( true );

			try {
				const [ nextSummary, nextLoans ] = await Promise.all( [
					getWooPaymentsCapitalActiveLoanSummary(),
					getWooPaymentsCapitalLoans(),
				] );

				if ( ! isMounted ) {
					return;
				}

				setSummary( nextSummary );
				setLoans( nextLoans );
				setErrorMessage( null );
			} catch ( error ) {
				if ( isMounted ) {
					setLoans( [] );
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments Capital Loans.',
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

		loadCapitalData();

		return () => {
			isMounted = false;
		};
	}, [] );

	const hasLoans = ! isLoading && ! errorMessage && loans.length > 0;
	const statusMessage =
		( isLoading && __( 'Loading Capital Loans…', 'woocommerce' ) ) ||
		errorMessage ||
		( ! isLoading &&
			! errorMessage &&
			loans.length === 0 &&
			__( 'No Capital loans found.', 'woocommerce' ) ) ||
		( hasLoans && __( 'Capital Loans loaded.', 'woocommerce' ) ) ||
		'';

	return (
		<div className="woocommerce-woopayments-capital">
			<WooPaymentsTestModeNotice currentPage="loans" />
			<section
				className="woocommerce-woopayments-capital__section"
				aria-busy={ isLoading }
			>
				<h2>{ __( 'Capital Loans', 'woocommerce' ) }</h2>
				<p
					className={
						hasLoans
							? 'screen-reader-text'
							: 'woocommerce-woopayments-capital__status'
					}
					role={ errorMessage ? 'alert' : 'status' }
					aria-live={ errorMessage ? 'assertive' : 'polite' }
				>
					{ statusMessage }
				</p>
				{ ! isLoading && ! errorMessage && loans.length === 0 && (
					<p className="woocommerce-woopayments-capital__empty">
						{ __( 'No Capital loans found.', 'woocommerce' ) }
					</p>
				) }
			</section>
			{ summary.details && ! errorMessage && (
				<ActiveLoanSummary summary={ summary } loans={ loans } />
			) }
			{ hasLoans && (
				<section className="woocommerce-woopayments-capital__section">
					<h3>{ __( 'All loans', 'woocommerce' ) }</h3>
					<LoanListSummary loans={ loans } />
					<table className="woocommerce-woopayments-capital__table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Disbursed', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Status', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Amount', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Fixed fee', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Withhold rate', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'First paydown', 'woocommerce' ) }
								</th>
								<th scope="col">
									{ __( 'Actions', 'woocommerce' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ loans.map( ( loan ) => (
								<tr key={ loan.stripe_loan_id }>
									<td>{ formatDate( loan.paid_out_at ) }</td>
									<td>
										<LoanStatusChip loan={ loan } />
									</td>
									<td>
										{ formatWooPaymentsAmount(
											loan.amount,
											loan.currency
										) }
									</td>
									<td>
										{ formatWooPaymentsAmount(
											loan.fee_amount,
											loan.currency
										) }
									</td>
									<td>
										{ formatPercent( loan.withhold_rate ) }
									</td>
									<td>
										{ formatDate( loan.first_paydown_at ) }
									</td>
									<td>
										<LoanActionLink loan={ loan } />
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</section>
			) }
		</div>
	);
};

export default WooPaymentsCapitalPage;
