<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

/**
 * AdditionalFields class.
 */
class AdditionalFields extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-additional-fields';

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
		if ( ! $permission ) {
			return $content;
		}

		$content .= $this->render_additional_fields( $order );

		return $content;
	}

	/**
	 * Render custom fields for the order from the 'additional' location.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	protected function render_additional_fields( $order ) {
		$additional_fields_controller = Package::container()->get( CheckoutFields::class );
		$additional_fields            = $additional_fields_controller->get_fields_for_location( 'additional' );

		if ( empty( $additional_fields ) ) {
			return '';
		}

		$content = '<dl class="wc-block-order-confirmation-additional-fields-list">';
		foreach ( $additional_fields as $additional_field_key => $additional_field ) {
			$value = $additional_fields_controller->get_field_from_order( $additional_field_key, $order );

			if ( '' === $value ) {
				continue;
			}

			if ( 'checkbox' === $additional_field['type'] ) {
				$value = $value ? __( 'Yes', 'woocommerce' ) : __( 'No', 'woocommerce' );
			}

			$content .= '<dt>' . esc_html( $additional_field['label'] ) . '</dt>';
			$content .= '<dd>' . esc_html( $value ) . '</dd>';
		}
		$content .= '</dl>';

		return $content;
	}
}
