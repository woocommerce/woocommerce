<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities\Ucp;

/**
 * Maps a Store API cart response and the checkout state kept beside it onto the UCP checkout shape.
 *
 * Line items, totals, expiry and cart messages come from {@see CartMapper}, so a checkout prices
 * exactly like the cart it was promoted from.
 */
class CheckoutMapper {
	const STATUS_INCOMPLETE          = 'incomplete';
	const STATUS_READY_FOR_COMPLETE  = 'ready_for_complete';
	const STATUS_REQUIRES_ESCALATION = 'requires_escalation';
	const STATUS_COMPLETED           = 'completed';
	const STATUS_CANCELED            = 'canceled';

	/**
	 * Build the UCP checkout payload, without the `ucp` envelope.
	 *
	 * @param array                $cart Store API cart response, as nested arrays.
	 * @param string               $checkout_id Checkout identifier (a Store API cart token).
	 * @param array<string, mixed> $state Stored `status` and `continue_url`, and the `order` once paid.
	 * @return array
	 */
	public function build_checkout( array $cart, string $checkout_id, array $state ): array {
		$payload  = ( new CartMapper() )->build_cart( $cart, $checkout_id );
		$missing  = $this->missing_requirements( $cart );
		$messages = array_merge( $payload['messages'] ?? array(), $missing );
		$status   = (string) ( $state['status'] ?? '' );

		if ( '' === $status ) {
			$status = empty( $cart['items'] ) || ! empty( $cart['errors'] ) || ! empty( $missing ) ? self::STATUS_INCOMPLETE : self::STATUS_READY_FOR_COMPLETE;
		}

		$payload['status'] = $status;
		$payload['links']  = $this->policy_links();

		$buyer = $this->buyer( (array) ( $cart['billing_address'] ?? array() ) );
		if ( ! empty( $buyer ) ) {
			$payload['buyer'] = $buyer;
		}

		if ( self::STATUS_REQUIRES_ESCALATION === $status && ! empty( $state['continue_url'] ) ) {
			$payload['continue_url'] = (string) $state['continue_url'];
			$messages[]              = array(
				'type'         => 'error',
				'code'         => 'ESCALATION_REQUIRED',
				'content'      => __( 'Payment must be completed by the buyer at continue_url.', 'woocommerce' ),
				'severity'     => 'requires_buyer_review',
				'path'         => '$',
				'content_type' => 'plain',
			);
		}

		if ( ! empty( $state['order'] ) ) {
			$payload['order'] = $state['order'];
		}

		unset( $payload['messages'] );
		if ( ! empty( $messages ) ) {
			$payload['messages'] = $messages;
		}

		return $payload;
	}

	/**
	 * Report what the buyer still has to supply before the checkout can complete.
	 *
	 * @param array $cart Store API cart response.
	 * @return array Error messages, empty when nothing is missing.
	 */
	private function missing_requirements( array $cart ): array {
		$missing = array();

		if ( '' === (string) ( $cart['billing_address']['email'] ?? '' ) ) {
			$missing['$.buyer.email'] = __( 'A buyer email is required.', 'woocommerce' );
		}

		if ( ! empty( $cart['needs_shipping'] ) ) {
			$shipping = (array) ( $cart['shipping_address'] ?? array() );
			foreach ( array( 'address_1', 'city', 'postcode', 'country' ) as $field ) {
				if ( '' === (string) ( $shipping[ $field ] ?? '' ) ) {
					$missing['$.shipping_address'] = __( 'A complete shipping address is required.', 'woocommerce' );
					break;
				}
			}
		}

		$messages = array();
		foreach ( $missing as $path => $content ) {
			$messages[] = array(
				'type'         => 'error',
				'code'         => 'missing',
				'content'      => $content,
				'severity'     => 'recoverable',
				'path'         => $path,
				'content_type' => 'plain',
			);
		}

		return $messages;
	}

	/**
	 * Build the UCP buyer from the cart billing address.
	 *
	 * @param array $billing Store API billing address.
	 * @return array<string, string> Only the fields that are set.
	 */
	private function buyer( array $billing ): array {
		return array_filter(
			array(
				'first_name'   => (string) ( $billing['first_name'] ?? '' ),
				'last_name'    => (string) ( $billing['last_name'] ?? '' ),
				'email'        => (string) ( $billing['email'] ?? '' ),
				'phone_number' => (string) ( $billing['phone'] ?? '' ),
			),
			static function ( string $value ): bool {
				return '' !== $value;
			}
		);
	}

	/**
	 * Build the legal links the platform has to show the buyer, for the policy pages that are published.
	 *
	 * @return array
	 */
	private function policy_links(): array {
		$pages = array(
			'privacy_policy'   => array( (int) get_option( 'wp_page_for_privacy_policy' ), __( 'Privacy policy', 'woocommerce' ) ),
			'terms_of_service' => array( wc_terms_and_conditions_page_id(), __( 'Terms and conditions', 'woocommerce' ) ),
			'refund_policy'    => array( wc_get_page_id( 'refund_returns' ), __( 'Refund and returns policy', 'woocommerce' ) ),
		);

		$links = array();
		foreach ( $pages as $type => list( $page_id, $title ) ) {
			// Unlike wc_get_page_permalink(), never falls back to the home page or links a draft:
			// WooCommerce creates the refund page as a draft.
			$url = $page_id > 0 && 'publish' === get_post_status( $page_id ) ? get_permalink( $page_id ) : false;
			if ( is_string( $url ) && '' !== $url ) {
				$links[] = array(
					'type'  => $type,
					'url'   => $url,
					'title' => $title,
				);
			}
		}

		return $links;
	}
}
