<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

/**
 * ShippingAddress class.
 */
class ShippingAddress extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-shipping-address';

	/**
	 * This renders the content of the block within the wrapper.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $permission Permission level for viewing order details.
	 * @param array     $attributes Block attributes.
	 * @param string    $content Original block content.
	 * @return string
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		if ( ! $permission || ! $order->needs_shipping_address() || ! $order->has_shipping_address() ) {
			return $this->render_content_fallback();
		}

		if ( 'full' === $permission ) {
			$address = '<address>' . wp_kses_post( $order->get_formatted_shipping_address() ) . '</address>';
			$phone   = $order->get_shipping_phone() ? '<p class="woocommerce-customer-details--phone">' . esc_html( $order->get_shipping_phone() ) . '</p>' : '';

			return $address . $phone;
		}

		$states  = wc()->countries->get_states( $order->get_shipping_country() );
		$address = esc_html(
			sprintf(
			/* translators: %s location. */
				__( 'Shipping to %s', 'woocommerce' ),
				implode(
					', ',
					array_filter(
						[
							$order->get_shipping_postcode(),
							$order->get_shipping_city(),
							$states[ $order->get_shipping_state() ] ?? $order->get_shipping_state(),
							wc()->countries->countries[ $order->get_shipping_country() ] ?? $order->get_shipping_country(),
						]
					)
				)
			)
		);

		return '<address>' . wp_kses_post( $address ) . '</address>';
	}
}
