<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;

/**
 * Projects a WooCommerce cart onto the UCP cart shape.
 *
 * Amounts are minor units, via the same formatter the catalog routes use.
 */
class UcpCartTransformer {
	/**
	 * Money formatter.
	 *
	 * @var MoneyFormatter
	 */
	private $money;

	/**
	 * Constructor.
	 *
	 * @param MoneyFormatter|null $money Money formatter.
	 */
	public function __construct( ?MoneyFormatter $money = null ) {
		$this->money = $money instanceof MoneyFormatter ? $money : new MoneyFormatter();
	}

	/**
	 * Build the UCP cart payload. Totals must already be calculated.
	 *
	 * @param \WC_Cart             $cart Cart instance.
	 * @param string               $cart_id Cart identifier (a Store API cart token).
	 * @param array<string, mixed> $context Optional `messages` to merge in.
	 * @return array
	 */
	public function build_cart( \WC_Cart $cart, string $cart_id, array $context = array() ): array {
		$payload = array(
			'id'           => $cart_id,
			'currency'     => (string) get_woocommerce_currency(),
			'line_items'   => $this->line_items( $cart ),
			'totals'       => $this->totals( $cart ),
			'continue_url' => $this->continue_url( $cart_id ),
		);

		$expires_at = $this->expires_at( $cart_id );
		if ( '' !== $expires_at ) {
			$payload['expires_at'] = $expires_at;
		}

		if ( ! empty( $context['messages'] ) && is_array( $context['messages'] ) ) {
			$payload['messages'] = $context['messages'];
		}

		return $payload;
	}

	/**
	 * Build the UCP line items.
	 *
	 * `item.id` prefers the variation id, matching the ids catalog hands out.
	 *
	 * @param \WC_Cart $cart Cart instance.
	 * @return array
	 */
	private function line_items( \WC_Cart $cart ): array {
		$line_items = array();

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$item = array(
				'id'    => (string) ( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] ),
				'title' => $product->get_name(),
				'price' => $this->to_minor_units( $product->get_price() ),
			);

			$image_url = wp_get_attachment_image_url( (int) $product->get_image_id(), 'woocommerce_thumbnail' );
			if ( is_string( $image_url ) && '' !== $image_url ) {
				$item['image_url'] = $image_url;
			}

			$line_items[] = array(
				'id'       => (string) $key,
				'item'     => $item,
				'quantity' => (int) $cart_item['quantity'],
				'totals'   => array(
					array(
						'type'   => 'subtotal',
						'amount' => $this->to_minor_units( $cart_item['line_subtotal'] ?? 0 ),
					),
					array(
						'type'   => 'total',
						'amount' => $this->to_minor_units( $cart_item['line_total'] ?? 0 ),
					),
				),
			);
		}

		return $line_items;
	}

	/**
	 * Build the totals breakdown.
	 *
	 * Exactly one `subtotal` and one `total`, per the schema; detail entries only when
	 * non-zero, since it forbids a zero `discount`. Discounts stay order-level: no agent
	 * can apply a code without the discounts extension, so there is nothing to attribute.
	 *
	 * @param \WC_Cart $cart Cart instance.
	 * @return array
	 */
	private function totals( \WC_Cart $cart ): array {
		$totals = array(
			array(
				'type'   => 'subtotal',
				'amount' => $this->to_minor_units( $cart->get_subtotal() ),
			),
		);

		$discount = $this->to_minor_units( $cart->get_discount_total() );
		if ( $discount > 0 ) {
			$totals[] = array(
				'type'   => 'discount',
				'amount' => -$discount,
			);
		}

		$shipping = $this->to_minor_units( $cart->get_shipping_total() );
		if ( $shipping > 0 ) {
			$totals[] = array(
				'type'   => 'fulfillment',
				'amount' => $shipping,
			);
		}

		$fees = $this->to_minor_units( $cart->get_fee_total() );
		if ( $fees > 0 ) {
			$totals[] = array(
				'type'   => 'fee',
				'amount' => $fees,
			);
		}

		$totals[] = array(
			'type'   => 'tax',
			'amount' => $this->to_minor_units( $cart->get_total_tax() ),
		);

		$totals[] = array(
			'type'   => 'total',
			'amount' => $this->to_minor_units( $cart->get_total( 'edit' ) ),
		);

		return $totals;
	}

	/**
	 * Build the URL that hands the cart to the buyer's own browser.
	 *
	 * `WC_Session_Handler` restores or clones the guest session named by the `session`
	 * argument, so the shopper lands on checkout holding this cart. A logged-in session
	 * is keyed to the user, not a token, so it gets a plain checkout URL.
	 *
	 * @param string $cart_id Cart identifier.
	 * @return string
	 */
	private function continue_url( string $cart_id ): string {
		$checkout_url = wc_get_checkout_url();
		$continue_url = is_user_logged_in() ? $checkout_url : add_query_arg( 'session', $cart_id, $checkout_url );

		/**
		 * Filters the URL a UCP cart hands the buyer for completing the purchase.
		 *
		 * @since 11.3.0
		 *
		 * @param string $continue_url Continue URL.
		 * @param string $cart_id Cart identifier.
		 */
		return (string) apply_filters( 'woocommerce_ucp_cart_continue_url', $continue_url, $cart_id );
	}

	/**
	 * Resolve the cart's expiry from its token.
	 *
	 * @param string $cart_id Cart identifier.
	 * @return string RFC 3339 timestamp, or '' when the token carries no expiry.
	 */
	private function expires_at( string $cart_id ): string {
		$expiry = (int) ( CartTokenUtils::get_cart_token_payload( $cart_id )['exp'] ?? 0 );

		return $expiry > 0 ? gmdate( 'c', $expiry ) : '';
	}

	/**
	 * Convert a major-unit amount to integer minor units.
	 *
	 * @param mixed $amount Monetary amount with decimals.
	 * @return int
	 */
	private function to_minor_units( $amount ): int {
		return (int) $this->money->format( is_scalar( $amount ) ? (string) $amount : '0' );
	}
}
