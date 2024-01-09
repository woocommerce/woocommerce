<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

/**
 * BillingAddress class.
 */
class BillingAddress extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-billing-address';

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
		if ( ! $permission || ! $order->has_billing_address() ) {
			return '';
		}

		$address = '<address>' . wp_kses_post( $order->get_formatted_billing_address() ) . '</address>';
		$phone   = $order->get_billing_phone() ? '<p class="woocommerce-customer-details--phone">' . esc_html( $order->get_billing_phone() ) . '</p>' : '';
		$custom  = '';

		$additional_fields_controller = Package::container()->get( CheckoutFields::class );
		$additional_fields            = $additional_fields_controller->get_fields_for_location( 'address' );

		if ( $additional_fields ) {
			foreach ( $additional_fields as $additional_field_key => $additional_field ) {
				$value = $additional_fields_controller->get_field_from_order( $additional_field_key, $order, 'billing' );

				if ( '' === $value ) {
					continue;
				}

				$custom .= '<p class="woocommerce-customer-details--custom woocommerce-customer-details--' . esc_attr( $additional_field_key ) . '"><strong>' . wp_kses_post( $additional_field['label'] ) . '</strong> ' . wp_kses_post( $value ) . '</p>';
			}
		}

		return $address . $phone . $custom;
	}
}
