<?php
/**
 * WooPaymentsOrderDataService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WC_Order;

/**
 * Builds WooPayments-compatible order data payloads and notes.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsOrderDataService {

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
	 * Build the billing-details payload for payment method updates.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order $order Order being charged.
	 * @return array<string,mixed>
	 */
	public function get_billing_data_from_order( WC_Order $order ): array {
		$billing_details = array();
		$address         = array_filter(
			array(
				'country'     => $order->get_billing_country(),
				'line1'       => $order->get_billing_address_1(),
				'line2'       => $order->get_billing_address_2(),
				'city'        => $order->get_billing_city(),
				'state'       => $order->get_billing_state(),
				'postal_code' => $order->get_billing_postcode(),
			),
			static fn( string $value ): bool => '' !== $value
		);

		if ( ! empty( $address ) ) {
			$billing_details['address'] = $address;
		}

		if ( '' !== $order->get_billing_email() ) {
			$billing_details['email'] = $order->get_billing_email();
		}

		if ( '' !== $order->get_billing_phone() ) {
			$billing_details['phone'] = $order->get_billing_phone();
		}

		if ( '' !== trim( $order->get_formatted_billing_full_name() ) ) {
			$billing_details['name'] = trim( $order->get_formatted_billing_full_name() );
		}

		return $billing_details;
	}

	/**
	 * Get the fee breakdown order note from a PaymentIntent object.
	 *
	 * @since 11.0.0
	 *
	 * @param array<string,mixed> $intent           Native PaymentIntent response.
	 * @param bool                $use_first_charge Whether to use the first charge in the list.
	 * @return string
	 */
	public function get_fee_breakdown_note_from_intent( array $intent, bool $use_first_charge = true ): string {
		$charge = $this->get_charge( $intent, $use_first_charge );

		return $this->get_fee_breakdown_note_from_charge_like_data( $charge );
	}

	/**
	 * Get the fee breakdown order note from a captured timeline event.
	 *
	 * @since 11.0.0
	 *
	 * @param array<string,mixed> $event Native timeline event.
	 * @return string
	 */
	public function get_fee_breakdown_note_from_timeline_event( array $event ): string {
		if ( 'captured' !== ( $event['type'] ?? null ) ) {
			return '';
		}

		return $this->get_fee_breakdown_note_from_charge_like_data( $event );
	}

	/**
	 * Add the WooPayments fee-breakdown note when the native response includes it.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order            $order            Order being charged.
	 * @param array<string,mixed> $intent           Native PaymentIntent response.
	 * @param bool                $use_first_charge Whether to use the first charge in the list.
	 * @return bool True when a note was added.
	 */
	public function add_fee_breakdown_note_from_intent( WC_Order $order, array $intent, bool $use_first_charge = true ): bool {
		return $this->add_fee_breakdown_note( $order, $this->get_fee_breakdown_note_from_intent( $intent, $use_first_charge ) );
	}

	/**
	 * Add the WooPayments fee-breakdown note when the timeline includes it.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order            $order Order being updated.
	 * @param array<string,mixed> $event Native timeline event.
	 * @return bool True when a note was added.
	 */
	public function add_fee_breakdown_note_from_timeline_event( WC_Order $order, array $event ): bool {
		return $this->add_fee_breakdown_note( $order, $this->get_fee_breakdown_note_from_timeline_event( $event ) );
	}

	/**
	 * Add a fee-breakdown note if it is not already present.
	 *
	 * @param WC_Order $order Order being updated.
	 * @param string   $note  Note content.
	 * @return bool True when a note was added.
	 */
	private function add_fee_breakdown_note( WC_Order $order, string $note ): bool {
		if ( '' === $note || $this->order_has_note( $order, $note ) ) {
			return false;
		}

		$order->add_order_note( $note );

		return true;
	}

	/**
	 * Get a fee-breakdown note from charge-shaped provider data.
	 *
	 * @param array<string,mixed> $charge Native charge or captured event response.
	 * @return string
	 */
	private function get_fee_breakdown_note_from_charge_like_data( array $charge ): string {
		$fee_breakdown_v1 = $charge['fee_breakdown_v1'] ?? null;
		if ( ! is_array( $fee_breakdown_v1 ) || ! $this->is_renderable_fee_breakdown( $fee_breakdown_v1 ) ) {
			return '';
		}

		$lines = array();
		$fx    = $fee_breakdown_v1['fx'] ?? null;
		if ( is_array( $fx ) && isset( $fx['from_currency'], $fx['to_currency'], $fx['to_amount'] ) ) {
			$exchange_rate = $fee_breakdown_v1['sources']['balance_transaction_exchange_rate'] ?? null;
			if ( is_numeric( $exchange_rate ) ) {
				$arrow   = html_entity_decode( '&rarr;', ENT_QUOTES, 'UTF-8' );
				$lines[] = sprintf(
					'1.00 %1$s %2$s %3$s %4$s: %5$s',
					strtoupper( (string) $fx['from_currency'] ),
					$arrow,
					$this->format_exchange_rate( $exchange_rate ),
					strtoupper( (string) $fx['to_currency'] ),
					$this->format_explicit_currency_amount( (int) $fx['to_amount'], (string) $fx['to_currency'] )
				);
			}
		}

		$fee_amount   = (int) $fee_breakdown_v1['totals']['fee']['amount'];
		$fee_currency = (string) $fee_breakdown_v1['totals']['fee']['currency'];
		if ( is_array( $fx ) ) {
			$lines[] = sprintf( 'Fee (3.9%% + %1$s): %2$s', $this->format_currency_minor_amount( 30, $fee_currency ), $this->format_explicit_currency_amount( $fee_amount, $fee_currency ) );
			$indent  = str_repeat( '&nbsp;', 4 );
			$lines[] = $indent . 'Base fee: 2.9% + ' . $this->format_currency_minor_amount( 30, $fee_currency );
			$lines[] = $indent . 'Currency conversion fee: 1%';
		} else {
			$lines[] = sprintf( 'Fee: %s', $this->format_explicit_currency_amount( $fee_amount, $fee_currency ) );
		}

		$net_amount   = isset( $fee_breakdown_v1['totals']['capture_net']['amount'] ) ? (int) $fee_breakdown_v1['totals']['capture_net']['amount'] : (int) $fee_breakdown_v1['totals']['net']['amount'];
		$net_currency = (string) ( $fee_breakdown_v1['totals']['capture_net']['currency'] ?? $fee_breakdown_v1['totals']['net']['currency'] );
		$lines[]      = sprintf( 'Net payout: %s', $this->format_explicit_currency_amount( $net_amount, $net_currency ) );

		$html = '';
		foreach ( $lines as $line ) {
			$html .= '<p>' . $line . '</p>' . PHP_EOL;
		}

		return '<strong>Fee details:</strong><div class="captured-event-details">' . PHP_EOL . $html . '</div>';
	}

	/**
	 * Get a charge from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $intent           Native PaymentIntent response.
	 * @param bool                $use_first_charge Whether to use the first charge in the list.
	 * @return array<string,mixed>
	 */
	private function get_charge( array $intent, bool $use_first_charge ): array {
		$charges = isset( $intent['charges']['data'] ) && is_array( $intent['charges']['data'] ) ? $intent['charges']['data'] : array();
		$charge  = empty( $charges ) ? array() : ( $use_first_charge ? reset( $charges ) : end( $charges ) );

		return is_array( $charge ) ? $charge : array();
	}

	/**
	 * Tell whether a fee breakdown has the minimum shape needed for the order note.
	 *
	 * @param array<string,mixed> $fee_breakdown Fee breakdown envelope.
	 * @return bool
	 */
	private function is_renderable_fee_breakdown( array $fee_breakdown ): bool {
		return isset(
			$fee_breakdown['totals']['fee']['amount'],
			$fee_breakdown['totals']['fee']['currency'],
			$fee_breakdown['totals']['net']['amount'],
			$fee_breakdown['totals']['net']['currency']
		);
	}

	/**
	 * Format a Stripe integer amount with an explicit currency code.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return string
	 */
	private function format_explicit_currency_amount( int $amount, string $currency ): string {
		return $this->format_currency_minor_amount( $amount, $currency ) . ' ' . strtoupper( $currency );
	}

	/**
	 * Format a provider exchange rate without changing its meaningful precision.
	 *
	 * @param mixed $exchange_rate Provider exchange rate.
	 * @return string
	 */
	private function format_exchange_rate( $exchange_rate ): string {
		return rtrim( rtrim( (string) $exchange_rate, '0' ), '.' );
	}

	/**
	 * Format a Stripe integer amount with its currency symbol.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return string
	 */
	private function format_currency_minor_amount( int $amount, string $currency ): string {
		$decimals = $this->is_zero_decimal_currency( $currency ) ? 0 : 2;
		$value    = number_format( $this->interpret_stripe_amount( $amount, $currency ), $decimals, '.', '' );
		$symbol   = html_entity_decode( get_woocommerce_currency_symbol( strtoupper( $currency ) ), ENT_QUOTES, 'UTF-8' );

		return $symbol . $value;
	}

	/**
	 * Tell whether the currency uses zero decimal places at the provider boundary.
	 *
	 * @param string $currency Currency code.
	 * @return bool
	 */
	private function is_zero_decimal_currency( string $currency ): bool {
		return in_array( strtolower( $currency ), self::ZERO_DECIMAL_CURRENCIES, true );
	}

	/**
	 * Interpret a Stripe integer amount for a currency.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private function interpret_stripe_amount( int $amount, string $currency ): float {
		return $this->is_zero_decimal_currency( $currency ) ? (float) $amount : (float) $amount / 100;
	}

	/**
	 * Tell whether an order already has an exact order note.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $note  Note content.
	 * @return bool
	 */
	private function order_has_note( WC_Order $order, string $note ): bool {
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
}
