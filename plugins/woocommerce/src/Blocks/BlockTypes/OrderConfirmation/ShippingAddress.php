<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

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
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
	 * @return string
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		if ( ! $permission || ! $order->needs_shipping_address() || ! $order->has_shipping_address() ) {
			return $this->render_content_fallback();
		}

		$address = '<address>' . wp_kses_post( $order->get_formatted_shipping_address() ) . '</address>';
		$phone   = $order->get_shipping_phone() ? '<p class="woocommerce-customer-details--phone">' . esc_html( $order->get_shipping_phone() ) . '</p>' : '';
		$custom  = '';

		$controller        = Package::container()->get( CheckoutFields::class );
		$additional_fields = $controller->get_order_additional_fields_with_values( $order, 'address', 'shipping' );

		if ( $additional_fields ) {
			foreach ( $additional_fields as $additional_field_key => $additional_field ) {
				$custom .= sprintf(
					'<p class="woocommerce-customer-details--custom woocommerce-customer-details--%1$s"><strong>%2$s</strong> %3$s</p>',
					esc_attr( $additional_field_key ),
					wp_kses_post( $additional_field['label'] ),
					wp_kses_post( $additional_field['value'] )
				);
			}
		}

		return $address . $phone . $custom;
	}
}
