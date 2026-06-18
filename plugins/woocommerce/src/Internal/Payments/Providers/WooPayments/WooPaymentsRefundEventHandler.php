<?php
/**
 * WooPaymentsRefundEventHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use RuntimeException;
use WC_Order;
use WC_Order_Refund;

/**
 * Handles WooPayments refund webhook side effects for native WooPayments.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsRefundEventHandler {

	/**
	 * Stripe zero-decimal currencies.
	 *
	 * @var string[]
	 */
	private const ZERO_DECIMAL_CURRENCIES = array(
		'bif',
		'clp',
		'djf',
		'gnf',
		'jpy',
		'kmf',
		'krw',
		'mga',
		'pyg',
		'rwf',
		'vnd',
		'vuv',
		'xaf',
		'xof',
		'xpf',
	);

	/**
	 * Account countries where Future Refunds or Disputes balance is not supported.
	 *
	 * @var string[]
	 */
	private const FROD_UNSUPPORTED_COUNTRIES = array( 'HK', 'SG', 'AE' );

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Order payment store.
	 *
	 * @var OrderPaymentStore
	 */
	private OrderPaymentStore $order_payment_store;

	/**
	 * Initialize the handler.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime      WooPayments legacy runtime.
	 * @param OrderPaymentStore        $order_payment_store Order payment store.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime, OrderPaymentStore $order_payment_store ): void {
		$this->legacy_runtime      = $legacy_runtime;
		$this->order_payment_store = $order_payment_store;
	}

	/**
	 * Tell whether an event is a WooPayments refund event.
	 *
	 * @param string $event_type Event type.
	 * @return bool
	 */
	public function is_supported_event( string $event_type ): bool {
		return in_array(
			$event_type,
			array(
				'charge.refund.updated',
				'charge.refunded',
			),
			true
		);
	}

	/**
	 * Process a provider refund event.
	 *
	 * @param string              $event_type   Event type.
	 * @param array<string,mixed> $event_object Refund or charge object.
	 */
	public function process( string $event_type, array $event_object ): void {
		if ( 'charge.refunded' === $event_type ) {
			$this->process_charge_refunded( $event_object );
			return;
		}

		if ( 'charge.refund.updated' === $event_type ) {
			$this->process_refund_updated( $event_object );
		}
	}

	/**
	 * Process a charge.refunded event from outside WP Admin.
	 *
	 * @param array<string,mixed> $charge Charge object.
	 * @throws RuntimeException When the refund event cannot be processed safely.
	 */
	private function process_charge_refunded( array $charge ): void {
		if ( 'succeeded' !== $this->get_required_string( $charge, 'status' ) ) {
			return;
		}

		if ( ! (bool) ( $charge['captured'] ?? false ) ) {
			return;
		}

		$charge_id         = $this->get_required_string( $charge, 'id' );
		$charge_amount     = $this->get_required_int( $charge, 'amount' );
		$currency          = $this->get_required_string( $charge, 'currency' );
		$refund            = $this->get_latest_refund( $charge );
		$refund_id         = $this->get_required_string( $refund, 'id' );
		$refund_amount     = $this->get_required_int( $refund, 'amount' );
		$refund_reason     = $this->get_optional_string( $refund, 'reason' );
		$refund_status     = $this->get_optional_string( $refund, 'status' );
		$balance_txn_id    = $this->get_refund_balance_transaction_id( $refund['balance_transaction'] ?? null );
		$refunded_amount   = $this->interpret_stripe_amount( $refund_amount, $currency );
		$is_partial_refund = $refund_amount < $charge_amount;
		$is_pending_refund = 'pending' === $refund_status;
		$order             = $this->get_order_for_charge_id( $charge_id, $charge );
		$existing_refund   = $this->get_refund_by_provider_refund_id( $order, $refund_id );

		if ( $charge_amount < 0 || $refund_amount < 0 || $refunded_amount > (float) $order->get_total() ) {
			throw new RuntimeException( esc_html( sprintf( 'The refund amount is not valid for charge ID: %s', $charge_id ) ) );
		}

		$this->claim_refund_lock( $order, $refund_id );
		try {
			$wc_refund = $existing_refund instanceof WC_Order_Refund
				? $existing_refund
				: $this->create_local_refund(
					$order,
					$refunded_amount,
					$refund_reason,
					$is_partial_refund ? array() : $order->get_items()
				);

			$this->add_note_and_metadata_for_created_refund( $order, $wc_refund, $refund_id, $balance_txn_id, $is_pending_refund );
		} finally {
			$this->order_payment_store->unlock_order_payment( $order );
		}
	}

	/**
	 * Process a charge.refund.updated event.
	 *
	 * @param array<string,mixed> $refund Refund object.
	 * @throws RuntimeException When the refund update cannot be processed safely.
	 */
	private function process_refund_updated( array $refund ): void {
		$charge_id      = $this->get_required_string( $refund, 'charge' );
		$refund_id      = $this->get_required_string( $refund, 'id' );
		$amount         = $this->get_required_int( $refund, 'amount' );
		$currency       = $this->get_required_string( $refund, 'currency' );
		$status         = $this->get_required_string( $refund, 'status' );
		$balance_txn_id = $this->get_refund_balance_transaction_id( $refund['balance_transaction'] ?? null );
		$order          = $this->get_order_for_charge_id( $charge_id );
		$wc_refund      = $this->get_refund_by_provider_refund_id( $order, $refund_id );

		switch ( $status ) {
			case 'failed':
				$this->claim_refund_lock( $order, $refund_id );
				try {
					$this->handle_failed_refund( $order, $refund_id, $amount, $currency, $wc_refund, false, $this->get_optional_string( $refund, 'failure_reason' ) );
				} finally {
					$this->order_payment_store->unlock_order_payment( $order );
				}
				return;
			case 'canceled':
			case 'cancelled':
				$this->claim_refund_lock( $order, $refund_id );
				try {
					$this->handle_failed_refund( $order, $refund_id, $amount, $currency, $wc_refund, true );
				} finally {
					$this->order_payment_store->unlock_order_payment( $order );
				}
				return;
			case 'succeeded':
				if ( $wc_refund instanceof WC_Order_Refund ) {
					$this->claim_refund_lock( $order, $refund_id );
					try {
						$this->add_note_and_metadata_for_created_refund( $order, $wc_refund, $refund_id, $balance_txn_id, false );
					} finally {
						$this->order_payment_store->unlock_order_payment( $order );
					}
				}
				return;
		}

		throw new RuntimeException( esc_html( sprintf( 'Invalid refund update status: %s', $status ) ) );
	}

	/**
	 * Create a local WC refund.
	 *
	 * @param WC_Order $order      Order object.
	 * @param float    $amount     Refund amount.
	 * @param string   $reason     Refund reason.
	 * @param array    $line_items Line items to refund.
	 * @return WC_Order_Refund
	 * @throws RuntimeException When WooCommerce cannot create the local refund.
	 */
	private function create_local_refund( WC_Order $order, float $amount, string $reason, array $line_items ): WC_Order_Refund {
		$refund_args = array(
			'amount'   => wc_format_decimal( $amount, wc_get_price_decimals() ),
			'reason'   => $reason,
			'order_id' => $order->get_id(),
		);

		if ( ! empty( $line_items ) ) {
			$refund_args['line_items'] = $line_items;
		}

		$refund = wc_create_refund( $refund_args );
		if ( is_wp_error( $refund ) ) {
			$this->log_refund_failure( $order, 'Failed to create local refund: ' . $refund->get_error_message() );
			throw new RuntimeException( esc_html( sprintf( 'Could not create local refund for order %1$d: %2$s', $order->get_id(), $refund->get_error_message() ) ) );
		}

		if ( ! $refund instanceof WC_Order_Refund ) {
			$this->log_refund_failure( $order, 'wc_create_refund returned an unexpected value.' );
			throw new RuntimeException( esc_html( sprintf( 'Could not create local refund for order %d.', $order->get_id() ) ) );
		}

		return $refund;
	}

	/**
	 * Add the WooPayments refund note and metadata.
	 *
	 * @param WC_Order        $order          Order object.
	 * @param WC_Order_Refund $wc_refund      Refund object.
	 * @param string          $refund_id      Provider refund ID.
	 * @param string          $balance_txn_id Balance transaction ID.
	 * @param bool            $is_pending     Whether the provider refund is pending.
	 */
	private function add_note_and_metadata_for_created_refund( WC_Order $order, WC_Order_Refund $wc_refund, string $refund_id, string $balance_txn_id, bool $is_pending ): void {
		$note = $this->get_created_refund_note( $order, $wc_refund, $refund_id, $is_pending );
		if ( ! $this->order_note_exists( $order, $note ) ) {
			$order->add_order_note( $note );
		}

		$order->update_meta_data( '_wcpay_refund_status', $is_pending ? 'pending' : 'successful' );
		$wc_refund->update_meta_data( '_wcpay_refund_id', $refund_id );
		if ( '' !== $balance_txn_id ) {
			$wc_refund->update_meta_data( '_wcpay_refund_transaction_id', $balance_txn_id );
		}

		$wc_refund->save_meta_data();
		$order->save();
	}

	/**
	 * Handle a failed or canceled provider refund.
	 *
	 * @param WC_Order             $order          Order object.
	 * @param string               $refund_id      Provider refund ID.
	 * @param int                  $amount         Refund amount in provider minor units.
	 * @param string               $currency       Refund currency.
	 * @param WC_Order_Refund|null $wc_refund      Matched local refund.
	 * @param bool                 $is_cancelled   Whether the refund was canceled.
	 * @param string               $failure_reason Provider failure reason.
	 * @throws RuntimeException When WooCommerce cannot delete the local refund.
	 */
	private function handle_failed_refund( WC_Order $order, string $refund_id, int $amount, string $currency, ?WC_Order_Refund $wc_refund, bool $is_cancelled = false, string $failure_reason = '' ): void {
		if ( $wc_refund instanceof WC_Order_Refund ) {
			$wc_refund_id = $wc_refund->get_id();
			$deleted      = $wc_refund->delete( true );
			if ( false === $deleted || is_wp_error( $deleted ) ) {
				$this->log_refund_failure( $order, sprintf( 'Failed to delete local refund %d after provider refund failure.', $wc_refund->get_id() ) );
				throw new RuntimeException( esc_html( sprintf( 'Could not delete failed local refund %d.', $wc_refund->get_id() ) ) );
			}

			/**
			 * Fires after a refund is deleted while handling a WooPayments refund webhook.
			 *
			 * @since 11.0.0
			 *
			 * @param int $wc_refund_id Deleted refund ID.
			 * @param int $order_id     Parent order ID.
			 */
			do_action( 'woocommerce_refund_deleted', $wc_refund_id, $order->get_id() );
		}

		if ( ! $is_cancelled && 'insufficient_funds' === $failure_reason ) {
			$this->add_order_note_once( $order, $this->get_insufficient_balance_refund_note( $order, $amount ) );
		} else {
			$note = $this->get_failed_refund_note( $order, $refund_id, $amount, $currency, $is_cancelled, $failure_reason );
			$this->add_order_note_once( $order, $note );
		}

		if ( 'refunded' === $order->get_status() ) {
			$order->update_status( 'failed' );
		}

		$order->update_meta_data( '_wcpay_refund_status', 'failed' );
		$order->save();
	}

	/**
	 * Build a WooPayments-compatible created-refund note.
	 *
	 * @param WC_Order        $order      Order object.
	 * @param WC_Order_Refund $wc_refund  Refund object.
	 * @param string          $refund_id  Provider refund ID.
	 * @param bool            $is_pending Whether the provider refund is pending.
	 * @return string
	 */
	private function get_created_refund_note( WC_Order $order, WC_Order_Refund $wc_refund, string $refund_id, bool $is_pending ): string {
		$formatted_amount = $this->format_refund_amount( (float) $wc_refund->get_amount(), $wc_refund->get_currency(), $order );
		$status_text      = $is_pending
			? sprintf(
				'<a href="https://woocommerce.com/document/woopayments/managing-money/#pending-refunds" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_html__( 'is pending', 'woocommerce' )
			)
			: esc_html__( 'was successfully processed', 'woocommerce' );
		$refund_id_markup = '<code>' . esc_html( $refund_id ) . '</code>';
		$refund_reason    = $wc_refund->get_reason();

		if ( '' === $refund_reason ) {
			$note = sprintf(
				/* translators: %1$s: refund amount, %2$s: WooPayments, %3$s: provider refund ID, %4$s: refund status. */
				__( 'A refund of %1$s %4$s using %2$s (%3$s).', 'woocommerce' ),
				$formatted_amount,
				'WooPayments',
				$refund_id_markup,
				$status_text
			);
		} else {
			$note = sprintf(
				/* translators: %1$s: refund amount, %2$s: WooPayments, %3$s: refund reason, %4$s: provider refund ID, %5$s: refund status. */
				__( 'A refund of %1$s %5$s using %2$s. Reason: %3$s. (%4$s)', 'woocommerce' ),
				$formatted_amount,
				'WooPayments',
				esc_html( $refund_reason ),
				$refund_id_markup,
				$status_text
			);
		}

		return wp_kses_post( $note );
	}

	/**
	 * Build a WooPayments-compatible failed-refund note.
	 *
	 * @param WC_Order $order          Order object.
	 * @param string   $refund_id      Provider refund ID.
	 * @param int      $amount         Refund amount in provider minor units.
	 * @param string   $currency       Refund currency.
	 * @param bool     $is_cancelled   Whether the refund was canceled.
	 * @param string   $failure_reason Provider failure reason.
	 * @return string
	 */
	private function get_failed_refund_note( WC_Order $order, string $refund_id, int $amount, string $currency, bool $is_cancelled, string $failure_reason ): string {
		$formatted_amount = $this->format_refund_amount( $this->interpret_stripe_amount( $amount, $currency ), $currency, $order );
		$status           = $is_cancelled ? esc_html__( 'cancelled', 'woocommerce' ) : esc_html__( 'unsuccessful', 'woocommerce' );
		$suffix           = $is_cancelled ? '.' : ': ' . $this->get_refund_failure_message( $failure_reason );
		$note             = sprintf(
			/* translators: %1$s: refund amount, %2$s: refund status, %3$s: WooPayments, %4$s: provider refund ID, %5$s: failure message or period. */
			__( 'A refund of %1$s was <strong>%2$s</strong> using %3$s (<code>%4$s</code>)%5$s', 'woocommerce' ),
			$formatted_amount,
			$status,
			'WooPayments',
			esc_html( $refund_id ),
			$suffix
		);

		return wp_kses_post( $note );
	}

	/**
	 * Format a refund amount with WooPayments explicit-currency behavior.
	 *
	 * @param float    $amount   Refund amount.
	 * @param string   $currency Refund currency.
	 * @param WC_Order $order    Order object.
	 * @return string
	 */
	private function format_refund_amount( float $amount, string $currency, WC_Order $order ): string {
		$formatted_amount = wc_price(
			$amount,
			array(
				'currency' => strtoupper( $currency ),
			)
		);

		$extension_formatter = array( 'WC_Payments_Explicit_Price_Formatter', 'get_explicit_price' );
		if ( class_exists( 'WC_Payments_Explicit_Price_Formatter' ) && is_callable( $extension_formatter ) ) {
			return (string) call_user_func( $extension_formatter, $formatted_amount, $order );
		}

		if ( ! $this->should_output_native_explicit_price() ) {
			return $formatted_amount;
		}

		return $this->append_currency_suffix( $formatted_amount, $order->get_currency() );
	}

	/**
	 * Tell whether native refund notes should include explicit currency suffixes.
	 *
	 * @return bool
	 */
	private function should_output_native_explicit_price(): bool {
		$store_currency     = strtoupper( (string) get_option( 'woocommerce_currency', 'USD' ) );
		$enabled_currencies = get_option( 'wcpay_multi_currency_enabled_currencies', array() );
		$enabled_currencies = is_array( $enabled_currencies ) ? $enabled_currencies : array();
		$enabled_currencies = array_map(
			static fn( $currency_code ) => strtoupper( (string) $currency_code ),
			$enabled_currencies
		);

		return count( array_unique( array_merge( array( $store_currency ), $enabled_currencies ) ) ) > 1;
	}

	/**
	 * Append a currency suffix when the price does not already include one.
	 *
	 * @param string $price         Formatted price.
	 * @param string $currency_code Currency code.
	 * @return string
	 */
	private function append_currency_suffix( string $price, string $currency_code ): string {
		$currency_code = strtoupper( trim( $currency_code ) );
		if ( '' === $currency_code ) {
			return $price;
		}

		$price_to_check = html_entity_decode( wp_strip_all_tags( $price ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
		if ( false === strpos( $price_to_check, $currency_code ) ) {
			return $price . ' ' . $currency_code;
		}

		return $price;
	}

	/**
	 * Build the WooPayments insufficient-balance failed-refund note.
	 *
	 * @param WC_Order $order  Order object.
	 * @param int      $amount Refund amount in provider minor units.
	 * @return string
	 */
	private function get_insufficient_balance_refund_note( WC_Order $order, int $amount ): string {
		$formatted_amount = wc_price(
			$this->interpret_stripe_amount( $amount, $order->get_currency() ),
			array(
				'currency' => $order->get_currency(),
			)
		);

		if ( $this->is_frod_supported( $this->get_account_country() ) ) {
			$learn_more_url = 'https://woocommerce.com/document/woopayments/fees/preventing-negative-balances/#adding-funds';
			$note           = sprintf(
				/* translators: 1: Formatted refund amount, 2: Learn more URL. */
				__( 'Refund of %1$s <strong>failed</strong> due to insufficient funds in your WooPayments balance. To prevent delays in refunding customers, please consider adding funds to your Future Refunds or Disputes (FROD) balance. <a href="%2$s" target="_blank" rel="noopener noreferrer">Learn more</a>.', 'woocommerce' ),
				$formatted_amount,
				esc_url( $learn_more_url )
			);
		} else {
			$note = sprintf(
				/* translators: %1$s: Formatted refund amount. */
				__( 'Refund of %1$s <strong>failed</strong> due to insufficient funds in your WooPayments balance.', 'woocommerce' ),
				$formatted_amount
			);
		}

		return wp_kses_post( $note );
	}

	/**
	 * Get a provider object's first refund payload.
	 *
	 * @param array<string,mixed> $charge Charge object.
	 * @return array<string,mixed>
	 * @throws RuntimeException When the charge payload does not include refund data.
	 */
	private function get_latest_refund( array $charge ): array {
		$refunds = $this->get_required_array( $charge, 'refunds' );
		$data    = $this->get_required_array( $refunds, 'data' );
		$refund  = $data[0] ?? null;

		if ( ! is_array( $refund ) ) {
			throw new RuntimeException( 'WooPayments refund event is missing refund data.' );
		}

		return $refund;
	}

	/**
	 * Resolve a WooPayments order by charge ID.
	 *
	 * @param string              $charge_id    Charge ID.
	 * @param array<string,mixed> $event_object Provider object.
	 * @return WC_Order
	 * @throws RuntimeException When the charge does not resolve to a WooPayments order.
	 */
	private function get_order_for_charge_id( string $charge_id, array $event_object = array() ): WC_Order {
		$order = $this->get_order_by_payment_meta( '_charge_id', $charge_id );
		if ( ! $order instanceof WC_Order || ! $this->is_woopayments_order( $order ) || ! $this->does_order_key_match_event_object( $order, $event_object ) ) {
			throw new RuntimeException( esc_html( sprintf( 'Could not find WooPayments order via charge ID: %s', $charge_id ) ) );
		}

		return $order;
	}

	/**
	 * Tell whether a found order matches the event order key when one is present.
	 *
	 * @param WC_Order            $order        Order object.
	 * @param array<string,mixed> $event_object Provider object.
	 * @return bool
	 */
	private function does_order_key_match_event_object( WC_Order $order, array $event_object ): bool {
		$order_key = $event_object['metadata']['order_key'] ?? null;

		return ! is_string( $order_key ) || '' === $order_key || $order_key === $order->get_order_key();
	}

	/**
	 * Get a WooPayments order by a preserved payment meta key.
	 *
	 * @param string $meta_key   Payment meta key.
	 * @param string $meta_value Payment meta value.
	 * @return WC_Order|null
	 */
	private function get_order_by_payment_meta( string $meta_key, string $meta_value ): ?WC_Order {
		if ( '' === $meta_value ) {
			return null;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'orderby'    => 'ID',
				'order'      => 'DESC',
				'status'     => 'any',
				'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( ! is_array( $orders ) ) {
			return null;
		}

		return isset( $orders[0] ) && $orders[0] instanceof WC_Order ? $orders[0] : null;
	}

	/**
	 * Find an existing local refund by provider refund ID.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string   $refund_id Provider refund ID.
	 * @return WC_Order_Refund|null
	 */
	private function get_refund_by_provider_refund_id( WC_Order $order, string $refund_id ): ?WC_Order_Refund {
		foreach ( $order->get_refunds() as $refund ) {
			if ( $refund instanceof WC_Order_Refund && $refund_id === (string) $refund->get_meta( '_wcpay_refund_id', true ) ) {
				return $refund;
			}
		}

		return null;
	}

	/**
	 * Tell whether an order belongs to WooPayments.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function is_woopayments_order( WC_Order $order ): bool {
		$payment_method = (string) $order->get_payment_method();

		return OrderPaymentStore::GATEWAY_ID === $payment_method || 0 === strpos( $payment_method, OrderPaymentStore::GATEWAY_ID_PREFIX );
	}

	/**
	 * Claim the shared order payment lock for a refund webhook mutation.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string   $refund_id Provider refund ID.
	 * @throws RuntimeException When the order payment lock cannot be claimed.
	 */
	private function claim_refund_lock( WC_Order $order, string $refund_id ): void {
		$reference = 'refund_webhook_' . $refund_id;
		if ( ! $this->order_payment_store->claim_order_payment_lock( $order, $reference ) ) {
			throw new RuntimeException( esc_html( sprintf( 'Could not claim WooPayments refund webhook lock for order %1$d and refund %2$s.', $order->get_id(), $refund_id ) ) );
		}
	}

	/**
	 * Tell whether an order already has a note.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $note  Note content.
	 * @return bool
	 */
	private function order_note_exists( WC_Order $order, string $note ): bool {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $order_note ) {
			if ( $note === $order_note->content ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add an order note only when the exact note is not already present.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $note  Note content.
	 */
	private function add_order_note_once( WC_Order $order, string $note ): void {
		if ( ! $this->order_note_exists( $order, $note ) ) {
			$order->add_order_note( $note );
		}
	}

	/**
	 * Get a required string field.
	 *
	 * @param array<string,mixed> $data Data array.
	 * @param string              $key  Field key.
	 * @return string
	 * @throws RuntimeException When the field is missing or empty.
	 */
	private function get_required_string( array $data, string $key ): string {
		$value = $data[ $key ] ?? null;
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			throw new RuntimeException( esc_html( sprintf( 'WooPayments refund event is missing required field: %s', $key ) ) );
		}

		$value = (string) $value;
		if ( '' === $value ) {
			throw new RuntimeException( esc_html( sprintf( 'WooPayments refund event is missing required field: %s', $key ) ) );
		}

		return $value;
	}

	/**
	 * Get an optional string field.
	 *
	 * @param array<string,mixed> $data Data array.
	 * @param string              $key  Field key.
	 * @return string
	 */
	private function get_optional_string( array $data, string $key ): string {
		$value = $data[ $key ] ?? '';

		return is_string( $value ) || is_numeric( $value ) ? (string) $value : '';
	}

	/**
	 * Get a required integer field.
	 *
	 * @param array<string,mixed> $data Data array.
	 * @param string              $key  Field key.
	 * @return int
	 * @throws RuntimeException When the field is missing or not numeric.
	 */
	private function get_required_int( array $data, string $key ): int {
		$value = $data[ $key ] ?? null;
		if ( ! is_int( $value ) && ! ( is_string( $value ) && is_numeric( $value ) ) ) {
			throw new RuntimeException( esc_html( sprintf( 'WooPayments refund event is missing required field: %s', $key ) ) );
		}

		return (int) $value;
	}

	/**
	 * Get a required array field.
	 *
	 * @param array<string,mixed> $data Data array.
	 * @param string              $key  Field key.
	 * @return array<string,mixed>
	 * @throws RuntimeException When the field is missing or not an array.
	 */
	private function get_required_array( array $data, string $key ): array {
		$value = $data[ $key ] ?? null;
		if ( ! is_array( $value ) ) {
			throw new RuntimeException( esc_html( sprintf( 'WooPayments refund event is missing required field: %s', $key ) ) );
		}

		return $value;
	}

	/**
	 * Get a refund balance transaction ID from a provider value.
	 *
	 * @param mixed $balance_transaction Balance transaction value.
	 * @return string
	 */
	private function get_refund_balance_transaction_id( $balance_transaction ): string {
		if ( is_string( $balance_transaction ) ) {
			return $balance_transaction;
		}

		if ( is_array( $balance_transaction ) && isset( $balance_transaction['id'] ) ) {
			return (string) $balance_transaction['id'];
		}

		return '';
	}

	/**
	 * Interpret a Stripe integer amount for a currency.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private function interpret_stripe_amount( int $amount, string $currency ): float {
		return in_array( strtolower( $currency ), self::ZERO_DECIMAL_CURRENCIES, true ) ? (float) $amount : (float) $amount / 100;
	}

	/**
	 * Tell whether Future Refunds or Disputes balance is supported for a country.
	 *
	 * @param string $country_code Country code.
	 * @return bool
	 */
	private function is_frod_supported( string $country_code ): bool {
		return ! in_array( strtoupper( $country_code ), self::FROD_UNSUPPORTED_COUNTRIES, true );
	}

	/**
	 * Get the connected account country from cached account data.
	 *
	 * @return string
	 */
	private function get_account_country(): string {
		$account_data = get_option( 'wcpay_account_data', array() );
		$country      = '';

		if ( is_array( $account_data ) && isset( $account_data['data'] ) && is_array( $account_data['data'] ) && isset( $account_data['data']['country'] ) && is_scalar( $account_data['data']['country'] ) ) {
			$country = strtoupper( (string) $account_data['data']['country'] );
		}

		if ( '' === $country && function_exists( 'WC' ) && WC() && WC()->countries ) {
			$country = strtoupper( (string) WC()->countries->get_base_country() );
		}

		if ( false !== strpos( $country, ':' ) ) {
			$base_country = strtok( $country, ':' );
			$country      = is_string( $base_country ) ? $base_country : '';
		}

		return '' !== $country ? $country : 'US';
	}

	/**
	 * Get the user-facing failure message for a refund failure reason.
	 *
	 * @param string $failure_reason Provider failure reason.
	 * @return string
	 */
	private function get_refund_failure_message( string $failure_reason ): string {
		switch ( $failure_reason ) {
			case 'lost_or_stolen_card':
				return __( 'The card used for the original payment has been reported lost or stolen.', 'woocommerce' );
			case 'expired_or_canceled_card':
				return __( 'The card used for the original payment has expired or been canceled.', 'woocommerce' );
			case 'charge_for_pending_refund_disputed':
				return __( 'The charge for this refund is being disputed by the customer.', 'woocommerce' );
			case 'insufficient_funds':
				return __( 'Insufficient funds in your WooPayments balance.', 'woocommerce' );
			case 'declined':
				return __( 'The refund was declined by the card issuer.', 'woocommerce' );
			case 'merchant_request':
				return __( 'The refund was canceled at your request.', 'woocommerce' );
		}

		return __( 'An unknown error occurred while processing the refund.', 'woocommerce' );
	}

	/**
	 * Log a local refund side-effect failure.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $message Error message.
	 */
	private function log_refund_failure( WC_Order $order, string $message ): void {
		$logger = $this->legacy_runtime->get_logger();
		if ( ! is_object( $logger ) || ! is_callable( array( $logger, 'error' ) ) ) {
			return;
		}

		$logger->error(
			$message,
			array(
				'source'   => 'native-payments-webhook',
				'order_id' => $order->get_id(),
			)
		);
	}
}
