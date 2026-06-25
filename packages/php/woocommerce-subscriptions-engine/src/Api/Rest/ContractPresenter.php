<?php
/**
 * ContractPresenter - shapes a {@see Contract} into the presentation view-model the
 * customer portal renders and refetches.
 *
 * This is the engine REST response shape: the list-row and detail arrays
 * {@see ContractsController} returns. It is the server-side mirror of the consumer
 * portal's view-model so a page-load render and a client refetch see identical fields -
 * status labels, formatted money, formatted dates, the per-status detail date-row, and
 * the action-visibility flags. Consumers tolerate unknown fields, so this shape is
 * ADDITIVE: new fields may be added; existing ones are never removed or repurposed.
 *
 * It shapes entirely from the (enriched) contract plus two facade reads: the billing
 * cadence comes off the contract's own frozen plan snapshot ({@see Contract::get_plan_snapshot()},
 * hydrated by the facade) - NOT a live {@see \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository}
 * join - and the related orders come from {@see Subscriptions::get_related_orders()}. The
 * contract's last-updated stamp is the one remaining repository read. Live WC objects never
 * leave this class: related orders are reduced to plain arrays before they are returned. A
 * contract with no hydrated plan snapshot (or one whose snapshot carries no cadence) degrades
 * to an empty cadence rather than fataling.
 *
 * List-row shape:
 *   id, status, status_label, next_payment, payment_method_title, total
 *
 * Detail shape:
 *   id, status, status_label, recurring_summary, start_date, last_order_date,
 *   date_row_label, date_row_value, payment_method_title, payment_method_expires,
 *   cancel_visible, hold_visible, reactivate_visible, needs_payment_notice,
 *   at_period_end, cancel_modal_copy, related_orders[]
 *
 * Related-order row shape:
 *   number, date, status, status_label, total, view_url
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Maps a contract to the portal presentation view-model.
 */
final class ContractPresenter {

	/**
	 * Statuses a customer may cancel from: active and on-hold. Terminal and
	 * pending-cancellation states are not customer-cancelable from the portal.
	 *
	 * @var array<int, string>
	 */
	private const CANCELABLE_STATUSES = array( ContractStatus::ACTIVE, ContractStatus::ON_HOLD );

	/**
	 * Contract repository, for the last-updated read.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Build the presenter.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
	}

	/**
	 * Build the list-row presentation array for one contract.
	 *
	 * @param Contract $contract The contract.
	 * @return array<string, mixed>
	 */
	public function build_row( Contract $contract ): array {
		$status         = $contract->get_status();
		$cadence        = $this->billing_cadence( $contract );
		$next_payment   = $this->to_string( $contract->get_next_payment_gmt() );
		$payment_method = $this->payment_method( $contract );

		return array(
			'id'                   => (int) $contract->get_id(),
			'status'               => $status,
			'status_label'         => $this->status_label( $status ),
			'next_payment'         => $this->should_dash_next_payment( $status, $next_payment )
				? ''
				: $this->format_date( $next_payment ),
			'payment_method_title' => $payment_method['title'],
			'total'                => $this->recurring_summary( $contract, $cadence ),
		);
	}

	/**
	 * Build the detail-page presentation array for one contract, with its related orders.
	 *
	 * @param Contract $contract The contract.
	 * @return array<string, mixed>
	 */
	public function build_detail( Contract $contract ): array {
		$id               = (int) $contract->get_id();
		$status           = $contract->get_status();
		$cadence          = $this->billing_cadence( $contract );
		$next_payment_gmt = $this->to_string( $contract->get_next_payment_gmt() );
		$end_gmt          = $this->to_string( $contract->get_end_gmt() );
		$updated_gmt      = $this->to_string( $this->contracts->find_last_updated_gmt( $id ) );
		$payment_method   = $this->payment_method( $contract );
		$has_next_payment = '' !== $next_payment_gmt;

		// "Needs payment": on-hold WITH a scheduled next payment is the failed-payment
		// retry path (the customer must update payment before reactivate is safe);
		// on-hold with no next payment is the admin-action path, where reactivate is safe.
		$needs_payment = ContractStatus::ON_HOLD === $status && $has_next_payment;

		return array(
			'id'                     => $id,
			'status'                 => $status,
			'status_label'           => $this->status_label( $status ),
			'recurring_summary'      => $this->recurring_summary( $contract, $cadence ),
			'start_date'             => $this->format_date( $this->to_string( $contract->get_start_gmt() ) ),
			'last_order_date'        => $this->format_date( $this->resolve_last_order_gmt( $contract ) ),
			'date_row_label'         => $this->date_row_label( $status, $next_payment_gmt ),
			'date_row_value'         => $this->date_row_value( $status, $next_payment_gmt, $end_gmt, $updated_gmt ),
			'payment_method_title'   => $payment_method['title'],
			'payment_method_expires' => $payment_method['expires'],
			'cancel_visible'         => in_array( $status, self::CANCELABLE_STATUSES, true ),
			'hold_visible'           => ContractStatus::ACTIVE === $status,
			'reactivate_visible'     => ContractStatus::ON_HOLD === $status && ! $needs_payment,
			'needs_payment_notice'   => $needs_payment,
			// Cancel mode the action forwards: active cancels at period end
			// (graceful -> pending-cancellation); on-hold cancels immediately.
			'at_period_end'          => ContractStatus::ACTIVE === $status,
			'cancel_modal_copy'      => $this->cancel_modal_copy( $status, $next_payment_gmt, $end_gmt ),
			'related_orders'         => $this->related_orders( $id ),
		);
	}

	/**
	 * Customer-facing status label. `pending-cancellation` is the deliberate
	 * customer-facing divergence ("Cancels soon"); unknown statuses humanize.
	 *
	 * @param string $status Status slug.
	 */
	public function status_label( string $status ): string {
		switch ( $status ) {
			case ContractStatus::ACTIVE:
				return __( 'Active', 'woocommerce-subscriptions-engine' );
			case ContractStatus::ON_HOLD:
				return __( 'On hold', 'woocommerce-subscriptions-engine' );
			case ContractStatus::PENDING_CANCELLATION:
				return __( 'Cancels soon', 'woocommerce-subscriptions-engine' );
			case ContractStatus::CANCELLED:
				return __( 'Cancelled', 'woocommerce-subscriptions-engine' );
			case ContractStatus::EXPIRED:
				return __( 'Expired', 'woocommerce-subscriptions-engine' );
			default:
				return ucfirst( str_replace( '-', ' ', $status ) );
		}
	}

	/**
	 * Related orders for a contract, as presentation rows, newest first.
	 *
	 * Sources the live orders through the {@see Subscriptions::get_related_orders()} facade
	 * (so the presenter stays facade-only, never reaching into the order-linkage internals)
	 * and reduces each to a plain presentation row before returning - no `WC_Order` escapes.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<int, array<string, mixed>>
	 */
	private function related_orders( int $contract_id ): array {
		$rows = array();
		foreach ( Subscriptions::get_related_orders( $contract_id ) as $order ) {
			$rows[] = $this->order_to_row( $order );
		}

		return $rows;
	}

	/**
	 * Reduce a live order to the presentation related-order row.
	 *
	 * @param WC_Order $order The order to reduce.
	 * @return array<string, mixed>
	 */
	private function order_to_row( WC_Order $order ): array {
		$date   = $order->get_date_created();
		$status = $order->get_status();

		return array(
			'number'       => $order->get_order_number(),
			'date'         => null === $date ? '' : $this->format_date( gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) ),
			'status'       => $status,
			'status_label' => wc_get_order_status_name( $status ),
			'total'        => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'view_url'     => $order->get_view_order_url(),
		);
	}

	/**
	 * Whether the next-payment cell should render a dash rather than a date.
	 *
	 * @param string $status           Status slug.
	 * @param string $next_payment_gmt GMT next-payment timestamp (empty if unset).
	 */
	private function should_dash_next_payment( string $status, string $next_payment_gmt ): bool {
		if ( in_array( $status, array( ContractStatus::PENDING_CANCELLATION, ContractStatus::CANCELLED, ContractStatus::EXPIRED ), true ) ) {
			return true;
		}
		if ( ContractStatus::ON_HOLD === $status && '' === $next_payment_gmt ) {
			return true;
		}

		return false;
	}

	/**
	 * Per-status label for the dynamic detail date-row.
	 *
	 * @param string $status           Status slug.
	 * @param string $next_payment_gmt GMT next-payment timestamp (empty if unset).
	 */
	private function date_row_label( string $status, string $next_payment_gmt ): string {
		switch ( $status ) {
			case ContractStatus::ACTIVE:
				return __( 'Next payment date', 'woocommerce-subscriptions-engine' );
			case ContractStatus::PENDING_CANCELLATION:
				return __( 'Cancels on', 'woocommerce-subscriptions-engine' );
			case ContractStatus::CANCELLED:
			case ContractStatus::EXPIRED:
				return __( 'End date', 'woocommerce-subscriptions-engine' );
			case ContractStatus::ON_HOLD:
				if ( '' !== $next_payment_gmt ) {
					return __( 'Next payment date', 'woocommerce-subscriptions-engine' );
				}
				return __( 'On-hold since', 'woocommerce-subscriptions-engine' );
			default:
				return __( 'Date', 'woocommerce-subscriptions-engine' );
		}
	}

	/**
	 * Per-status value for the dynamic detail date-row.
	 *
	 * @param string $status           Status slug.
	 * @param string $next_payment_gmt GMT next-payment timestamp (empty if unset).
	 * @param string $end_gmt          GMT end timestamp (empty if unset).
	 * @param string $updated_gmt      GMT last-updated timestamp (empty if unset).
	 */
	private function date_row_value( string $status, string $next_payment_gmt, string $end_gmt, string $updated_gmt ): string {
		switch ( $status ) {
			case ContractStatus::ACTIVE:
				return $this->format_date( $next_payment_gmt );
			case ContractStatus::PENDING_CANCELLATION:
				return $this->format_date( '' !== $end_gmt ? $end_gmt : $next_payment_gmt );
			case ContractStatus::CANCELLED:
			case ContractStatus::EXPIRED:
				return $this->format_date( $end_gmt );
			case ContractStatus::ON_HOLD:
				if ( '' !== $next_payment_gmt ) {
					return $this->format_date( $next_payment_gmt );
				}
				// Admin-action path: the last-updated stamp is the closest proxy for
				// "when the status flipped".
				return $this->format_date( $updated_gmt );
			default:
				return '';
		}
	}

	/**
	 * State-aware cancel-modal body copy: active cancels at the end of the current cycle
	 * (with the date when available); on-hold cancels immediately.
	 *
	 * @param string $status           Status slug.
	 * @param string $next_payment_gmt GMT next-payment timestamp (empty if unset).
	 * @param string $end_gmt          GMT end timestamp (empty if unset).
	 */
	private function cancel_modal_copy( string $status, string $next_payment_gmt, string $end_gmt ): string {
		if ( ContractStatus::ACTIVE === $status ) {
			$end_date = $this->format_date( '' !== $end_gmt ? $end_gmt : $next_payment_gmt );
			if ( '' !== $end_date ) {
				return sprintf(
					/* translators: %s: end-of-current-billing-cycle date */
					__( 'Your subscription will be cancelled at the end of your current billing cycle (%s). You will continue to receive your orders until then.', 'woocommerce-subscriptions-engine' ),
					$end_date
				);
			}
			return __(
				'Your subscription will be cancelled at the end of your current billing cycle. You will continue to receive your orders until then.',
				'woocommerce-subscriptions-engine'
			);
		}

		if ( ContractStatus::ON_HOLD === $status ) {
			return __( 'Your subscription will be cancelled immediately.', 'woocommerce-subscriptions-engine' );
		}

		return '';
	}

	/**
	 * Resolve the "last order date" GMT for the detail block, falling back to the start
	 * date when last-payment is unset (a freshly active contract).
	 *
	 * @param Contract $contract The contract.
	 */
	private function resolve_last_order_gmt( Contract $contract ): string {
		$last = $this->to_string( $contract->get_last_payment_gmt() );
		if ( '' !== $last ) {
			return $last;
		}

		return $this->to_string( $contract->get_start_gmt() );
	}

	/**
	 * Build the recurring summary: `{price} / {period}` for interval 1, or
	 * `{price} every {N} {period}s` for interval > 1.
	 *
	 * @param Contract                             $contract The contract.
	 * @param array{period: string, interval: int} $cadence The billing cadence.
	 */
	private function recurring_summary( Contract $contract, array $cadence ): string {
		$billing_total = $this->to_string( $contract->get_billing_total() );
		if ( '' === $billing_total ) {
			return '';
		}

		$currency = $contract->get_currency();
		$price    = wp_strip_all_tags( wc_price( (float) $billing_total, array( 'currency' => '' !== $currency ? $currency : null ) ) );

		$period   = $cadence['period'];
		$interval = $cadence['interval'];
		if ( '' === $period || $interval < 1 ) {
			return $price;
		}

		$suffix = $this->format_cadence( $period, $interval );

		return '' === $suffix ? $price : $price . ' ' . $suffix;
	}

	/**
	 * Build the cadence suffix for the recurring summary.
	 *
	 * @param string $period   Period slug: day/week/month/year.
	 * @param int    $interval Interval count.
	 */
	private function format_cadence( string $period, int $interval ): string {
		if ( 1 === $interval ) {
			switch ( $period ) {
				case 'day':
					return __( '/ day', 'woocommerce-subscriptions-engine' );
				case 'week':
					return __( '/ week', 'woocommerce-subscriptions-engine' );
				case 'month':
					return __( '/ month', 'woocommerce-subscriptions-engine' );
				case 'year':
					return __( '/ year', 'woocommerce-subscriptions-engine' );
				default:
					return '';
			}
		}

		switch ( $period ) {
			case 'day':
				/* translators: %d: interval count */
				return sprintf( _n( 'every %d day', 'every %d days', $interval, 'woocommerce-subscriptions-engine' ), $interval );
			case 'week':
				/* translators: %d: interval count */
				return sprintf( _n( 'every %d week', 'every %d weeks', $interval, 'woocommerce-subscriptions-engine' ), $interval );
			case 'month':
				/* translators: %d: interval count */
				return sprintf( _n( 'every %d month', 'every %d months', $interval, 'woocommerce-subscriptions-engine' ), $interval );
			case 'year':
				/* translators: %d: interval count */
				return sprintf( _n( 'every %d year', 'every %d years', $interval, 'woocommerce-subscriptions-engine' ), $interval );
			default:
				return '';
		}
	}

	/**
	 * The contract's payment-method presentation fields (`title`, `expires`).
	 *
	 * `title` comes off the contract's stored instrument. The card-expiry string is not
	 * modelled on the contract row, so `expires` is left blank here; it is a documented
	 * seam a payment-method-detail read can fill when that surface lands.
	 *
	 * @param Contract $contract The contract.
	 * @return array{title: string, expires: string}
	 */
	private function payment_method( Contract $contract ): array {
		$title = $contract->get_payment_instrument()->get_title();

		return array(
			'title'   => null === $title ? '' : $title,
			'expires' => '',
		);
	}

	/**
	 * The contract's billing cadence (`period`, `interval`) from its frozen plan snapshot.
	 *
	 * Read off the contract's hydrated plan snapshot ({@see Contract::get_plan_snapshot()}),
	 * so the cadence is the one the contract is billed under even after the live plan it came
	 * from is edited or deleted - no live plan-repository join. A contract with no hydrated
	 * snapshot (or a snapshot carrying no cadence) degrades to an empty period and a zero
	 * interval, which the presentation layer renders as a price with no cadence suffix rather
	 * than fataling.
	 *
	 * @param Contract $contract The contract.
	 * @return array{period: string, interval: int}
	 */
	private function billing_cadence( Contract $contract ): array {
		$snapshot = $contract->get_plan_snapshot();
		if ( null !== $snapshot ) {
			$policy = $snapshot->get_billing_policy();
			if ( $policy instanceof BillingPolicy ) {
				return array(
					'period'   => $policy->get_period(),
					'interval' => $policy->get_interval(),
				);
			}
		}

		return array(
			'period'   => '',
			'interval' => 0,
		);
	}

	/**
	 * Format a GMT timestamp string into the site date format. Empty string for an empty
	 * or unparseable input.
	 *
	 * @param string $gmt GMT timestamp ('Y-m-d H:i:s') or empty.
	 */
	private function format_date( string $gmt ): string {
		if ( '' === $gmt ) {
			return '';
		}
		$timestamp = strtotime( $gmt . ' UTC' );
		if ( false === $timestamp ) {
			return '';
		}

		return date_i18n( ScalarCoercion::coerce_string( get_option( 'date_format' ), 'F j, Y' ), $timestamp );
	}

	/**
	 * Coerce a nullable scalar to a trimmed string.
	 *
	 * @param mixed $value The value to coerce.
	 */
	private function to_string( $value ): string {
		return trim( ScalarCoercion::coerce_string( $value ) );
	}
}
