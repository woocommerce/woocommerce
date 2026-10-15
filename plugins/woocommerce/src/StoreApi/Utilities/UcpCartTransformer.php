<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Projects a Store API cart response onto the UCP cart shape.
 *
 * Reads {@see \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema} output, so
 * cart tax display, minor units, image filters and stock validation follow the
 * storefront.
 */
class UcpCartTransformer {
	/**
	 * Build the UCP cart payload.
	 *
	 * @param array                $cart Store API cart response, as nested arrays.
	 * @param string               $cart_id Cart identifier (a Store API cart token).
	 * @param array<string, mixed> $context Optional `messages` to merge in.
	 * @return array
	 */
	public function build_cart( array $cart, string $cart_id, array $context = array() ): array {
		$payload = array(
			'id'           => $cart_id,
			'currency'     => (string) $cart['totals']['currency_code'],
			'line_items'   => $this->line_items( (array) $cart['items'] ),
			'totals'       => $this->totals( (array) $cart['totals'] ),
			'continue_url' => $this->continue_url( $cart_id ),
		);

		$expires_at = $this->expires_at( $cart_id );
		if ( '' !== $expires_at ) {
			$payload['expires_at'] = $expires_at;
		}

		$messages = array_merge(
			is_array( $context['messages'] ?? null ) ? $context['messages'] : array(),
			$this->error_messages( (array) ( $cart['errors'] ?? array() ) )
		);
		if ( ! empty( $messages ) ) {
			$payload['messages'] = $messages;
		}

		return $payload;
	}

	/**
	 * Build the UCP line items.
	 *
	 * `item.id` is the variation id for variations, matching the ids catalog hands out.
	 *
	 * @param array $items Store API cart items.
	 * @return array
	 */
	private function line_items( array $items ): array {
		$line_items = array();

		foreach ( $items as $cart_item ) {
			// The Store API keeps variation attributes apart from the name; a UCP title
			// is one string, so rejoin them the way WC_Product_Variation::get_name() does.
			$title  = (string) $cart_item['name'];
			$values = array_column( (array) $cart_item['variation'], 'value' );
			if ( ! empty( $values ) ) {
				$title .= ' - ' . implode( ', ', $values );
			}

			$item = array(
				'id'    => (string) $cart_item['id'],
				'title' => $title,
				'price' => (int) $cart_item['prices']['price'],
			);

			$image_url = $cart_item['images'][0]['thumbnail'] ?? '';
			if ( is_string( $image_url ) && '' !== $image_url ) {
				$item['image_url'] = $image_url;
			}

			$line_items[] = array(
				'id'       => (string) $cart_item['key'],
				'item'     => $item,
				'quantity' => (int) $cart_item['quantity'],
				'totals'   => array(
					array(
						'type'   => 'subtotal',
						'amount' => (int) $cart_item['totals']['line_subtotal'],
					),
					array(
						'type'   => 'total',
						'amount' => (int) $cart_item['totals']['line_total'],
					),
				),
			);
		}

		return $line_items;
	}

	/**
	 * Build the totals breakdown.
	 *
	 * `subtotal`, `tax` and `total` are always present; a zero `tax` is valid (schema
	 * `minimum` 0) and tells the agent no tax applied. `discount`/`fulfillment`/`fee` are
	 * dropped when zero — the schema forbids a zero `discount` (`exclusiveMaximum` 0), and a
	 * zero shipping or fee is nothing to show. Discounts stay order-level: no agent can apply
	 * a code without the discounts extension, so there is nothing to attribute.
	 *
	 * @param array $cart_totals Store API cart totals, already in minor units.
	 * @return array
	 */
	private function totals( array $cart_totals ): array {
		$totals = array(
			array(
				'type'   => 'subtotal',
				'amount' => (int) $cart_totals['total_items'],
			),
		);

		$discount = (int) $cart_totals['total_discount'];
		if ( $discount > 0 ) {
			$totals[] = array(
				'type'   => 'discount',
				'amount' => -$discount,
			);
		}

		// Null until shipping is calculated.
		$shipping = (int) ( $cart_totals['total_shipping'] ?? 0 );
		if ( $shipping > 0 ) {
			$totals[] = array(
				'type'   => 'fulfillment',
				'amount' => $shipping,
			);
		}

		$fees = (int) $cart_totals['total_fees'];
		if ( $fees > 0 ) {
			$totals[] = array(
				'type'   => 'fee',
				'amount' => $fees,
			);
		}

		$totals[] = array(
			'type'   => 'tax',
			'amount' => (int) $cart_totals['total_tax'],
		);

		$totals[] = array(
			'type'   => 'total',
			'amount' => (int) $cart_totals['total_price'],
		);

		return $totals;
	}

	/**
	 * Surface the cart's own validation errors, such as stock that ran out since the
	 * item was added, as recoverable messages.
	 *
	 * @param array $errors Store API cart errors, each `{ code, message }`.
	 * @return array
	 */
	private function error_messages( array $errors ): array {
		$messages = array();

		foreach ( $errors as $error ) {
			$messages[] = array(
				'type'         => 'warning',
				'code'         => UcpUtils::cart_error_code( (string) ( $error['code'] ?? '' ) ),
				'content'      => (string) ( $error['message'] ?? '' ),
				'severity'     => 'recoverable',
				'path'         => '$.line_items',
				'content_type' => 'plain',
			);
		}

		return $messages;
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
}
