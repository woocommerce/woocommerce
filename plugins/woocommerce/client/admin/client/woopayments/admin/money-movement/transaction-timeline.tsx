/**
 * External dependencies
 */
import type { ReactNode } from 'react';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { WooPaymentsTimelineEvent } from './types';
import { formatAmount, formatDate, formatLabel } from './utils';

type TimelineDisplayEvent = {
	message: ReactNode;
	body?: ReactNode[];
	date?: string | number;
};

const hasDisplayValue = ( value: unknown ) =>
	value !== undefined && value !== null && value !== '';

const getEventDate = ( event: WooPaymentsTimelineEvent ) =>
	event.datetime || event.created;

const getString = (
	record: Record< string, unknown >,
	key: string
): string | undefined => {
	const value = record[ key ];

	return typeof value === 'string' && value ? value : undefined;
};

const getNumber = (
	record: Record< string, unknown >,
	key: string
): number | undefined => {
	const value = record[ key ];

	return typeof value === 'number' && Number.isFinite( value )
		? value
		: undefined;
};

const getAmount = ( event: WooPaymentsTimelineEvent, ...keys: string[] ) => {
	for ( const key of keys ) {
		const value = getNumber( event, key );

		if ( value !== undefined ) {
			return value;
		}
	}

	return undefined;
};

const getCurrency = ( event: WooPaymentsTimelineEvent ) =>
	getString( event, 'currency' ) || 'usd';

const getTimelineUserName = ( event: WooPaymentsTimelineEvent ) =>
	typeof event.user?.username === 'string' ? event.user.username : '';

const getFallbackMessage = ( event: WooPaymentsTimelineEvent ) => {
	if ( event.message ) {
		return event.message;
	}

	const userName = getTimelineUserName( event );
	if ( event.type === 'fraud_outcome_manual_approve' ) {
		return userName
			? sprintf(
					/* translators: %s: user display name. */
					__( 'Payment was approved by %s', 'woocommerce' ),
					userName
			  )
			: __( 'Payment was approved.', 'woocommerce' );
	}

	if ( event.type === 'fraud_outcome_manual_block' ) {
		return userName
			? sprintf(
					/* translators: %s: user display name. */
					__( 'Payment was blocked by %s', 'woocommerce' ),
					userName
			  )
			: __( 'Payment was blocked.', 'woocommerce' );
	}

	return formatLabel( event.type );
};

const createBodyLine = ( label: string, value: string ) =>
	hasDisplayValue( value )
		? sprintf(
				/* translators: 1: detail label, 2: detail value. */
				__( '%1$s: %2$s', 'woocommerce' ),
				label,
				value
		  )
		: null;

const getRefundBody = ( event: WooPaymentsTimelineEvent ) => {
	const body: ReactNode[] = [];
	const reason = getString( event, 'reason' );
	const arn = getString( event, 'acquirer_reference_number' );

	if ( reason ) {
		body.push(
			createBodyLine(
				__( 'Reason', 'woocommerce' ),
				formatLabel( reason )
			)
		);
	}

	if ( arn ) {
		body.push( createBodyLine( __( 'ARN', 'woocommerce' ), arn ) );
	}

	return body.filter( Boolean );
};

const getCapturedBody = ( event: WooPaymentsTimelineEvent ) => {
	const body: ReactNode[] = [];
	const currency = getCurrency( event );
	const fee = getAmount( event, 'fee' );
	const tax = getAmount( event, 'tax' );
	const net = getAmount( event, 'net' );

	if ( fee !== undefined ) {
		body.push(
			createBodyLine(
				__( 'Fee', 'woocommerce' ),
				formatAmount( Math.abs( fee ), currency )
			)
		);
	}

	if ( tax !== undefined ) {
		body.push(
			createBodyLine(
				__( 'Tax', 'woocommerce' ),
				formatAmount( Math.abs( tax ), currency )
			)
		);
	}

	if ( net !== undefined ) {
		body.push(
			createBodyLine(
				__( 'Net', 'woocommerce' ),
				formatAmount( net, currency )
			)
		);
	}

	return body.filter( Boolean );
};

const createAmountMessage = (
	template: string,
	event: WooPaymentsTimelineEvent,
	...amountKeys: string[]
) => {
	const amount = getAmount( event, ...amountKeys );

	if ( amount === undefined ) {
		return undefined;
	}

	return sprintf( template, formatAmount( amount, getCurrency( event ) ) );
};

const mapTimelineEvent = (
	event: WooPaymentsTimelineEvent
): TimelineDisplayEvent[] => {
	const date = getEventDate( event );
	const type = event.type || '';

	switch ( type ) {
		case 'started':
			return [
				{
					message: __(
						'Payment status changed to Started.',
						'woocommerce'
					),
					date,
				},
			];
		case 'authorized': {
			const message = createAmountMessage(
				/* translators: %s: formatted amount. */
				__(
					'A payment of %s was successfully authorized.',
					'woocommerce'
				),
				event,
				'amount_authorized',
				'amount'
			);

			return [
				{
					message: __(
						'Payment status changed to Authorized.',
						'woocommerce'
					),
					date,
				},
				...( message ? [ { message, date } ] : [] ),
			];
		}
		case 'authorization_voided':
		case 'authorization_expired': {
			const isExpired = type === 'authorization_expired';
			const message = createAmountMessage(
				isExpired
					? /* translators: %s: formatted amount. */
					  __( 'Authorization for %s expired.', 'woocommerce' )
					: /* translators: %s: formatted amount. */
					  __( 'Authorization for %s was voided.', 'woocommerce' ),
				event,
				'amount_authorized',
				'amount'
			);

			return [
				{
					message: isExpired
						? __(
								'Payment status changed to Authorization expired.',
								'woocommerce'
						  )
						: __(
								'Payment status changed to Authorization voided.',
								'woocommerce'
						  ),
					date,
				},
				...( message ? [ { message, date } ] : [] ),
			];
		}
		case 'captured': {
			const message = createAmountMessage(
				/* translators: %s: formatted amount. */
				__(
					'A payment of %s was successfully charged.',
					'woocommerce'
				),
				event,
				'amount_captured',
				'amount'
			);

			return [
				{
					message: __(
						'Payment status changed to Paid.',
						'woocommerce'
					),
					date,
				},
				{
					message: message || getFallbackMessage( event ),
					body: getCapturedBody( event ),
					date,
				},
			];
		}
		case 'partial_refund':
		case 'full_refund': {
			const message = createAmountMessage(
				/* translators: %s: formatted amount. */
				__(
					'A payment of %s was successfully refunded.',
					'woocommerce'
				),
				event,
				'amount_refunded',
				'amount'
			);

			return [
				{
					message:
						message ||
						( type === 'full_refund'
							? __( 'Payment was refunded.', 'woocommerce' )
							: __(
									'Payment was partially refunded.',
									'woocommerce'
							  ) ),
					body: getRefundBody( event ),
					date,
				},
			];
		}
		case 'refund_failed': {
			const message = createAmountMessage(
				/* translators: %s: formatted amount. */
				__( 'A refund of %s failed.', 'woocommerce' ),
				event,
				'amount_refunded',
				'amount'
			);
			const failureReason = getString( event, 'failure_reason' );

			return [
				{
					message: message || __( 'Refund failed.', 'woocommerce' ),
					body: failureReason
						? [
								createBodyLine(
									__( 'Reason', 'woocommerce' ),
									failureReason
								),
						  ].filter( Boolean )
						: undefined,
					date,
				},
			];
		}
		case 'failed': {
			const message = createAmountMessage(
				/* translators: %s: formatted amount. */
				__( 'A payment of %s failed.', 'woocommerce' ),
				event,
				'amount'
			);
			const failureReason = getString( event, 'failure_reason' );

			return [
				{
					message: message || __( 'Payment failed.', 'woocommerce' ),
					body: failureReason
						? [
								createBodyLine(
									__( 'Reason', 'woocommerce' ),
									failureReason
								),
						  ].filter( Boolean )
						: undefined,
					date,
				},
			];
		}
		case 'dispute.created':
		case 'dispute_closed':
		case 'dispute.closed':
		case 'dispute.funds_withdrawn':
		case 'dispute.funds_reinstated': {
			const disputedAmount = createAmountMessage(
				type === 'dispute.created'
					? /* translators: %s: formatted amount. */
					  __( 'A dispute was opened for %s.', 'woocommerce' )
					: /* translators: %s: formatted amount. */
					  __( 'Dispute amount: %s', 'woocommerce' ),
				event,
				'amount'
			);

			return [
				{
					message: disputedAmount || getFallbackMessage( event ),
					date,
				},
			];
		}
		case 'financing_paydown': {
			const message = createAmountMessage(
				/* translators: %s: formatted amount. */
				__( 'A financing paydown of %s was applied.', 'woocommerce' ),
				event,
				'amount'
			);

			return [
				{
					message: message || getFallbackMessage( event ),
					date,
				},
			];
		}
		case 'fraud_outcome_manual_approve':
		case 'fraud_outcome_manual_block':
			return [ { message: getFallbackMessage( event ), date } ];
		case 'fraud_outcome_auto_review':
			return [
				{
					message: __(
						'Payment was screened by your fraud filters and placed in review.',
						'woocommerce'
					),
					date,
				},
			];
		case 'fraud_outcome_auto_block':
			return [
				{
					message: __(
						'Payment was screened by your fraud filters and blocked.',
						'woocommerce'
					),
					date,
				},
			];
		default:
			return [ { message: getFallbackMessage( event ), date } ];
	}
};

export const WooPaymentsTransactionTimeline = ( {
	events,
}: {
	events: WooPaymentsTimelineEvent[];
} ) => {
	const rows = events.flatMap( mapTimelineEvent );

	if ( ! rows.length ) {
		return null;
	}

	return (
		<section className="woocommerce-woopayments-overview-card">
			<h3>{ __( 'Timeline', 'woocommerce' ) }</h3>
			<ol className="woocommerce-woopayments-money-movement__timeline">
				{ rows.map( ( row, index ) => (
					<li key={ index }>
						<span>{ row.message }</span>
						{ !! row.body?.length && (
							<ul>
								{ row.body.map( ( line, bodyIndex ) => (
									<li key={ bodyIndex }>{ line }</li>
								) ) }
							</ul>
						) }
						{ row.date && <time>{ formatDate( row.date ) }</time> }
					</li>
				) ) }
			</ol>
		</section>
	);
};
