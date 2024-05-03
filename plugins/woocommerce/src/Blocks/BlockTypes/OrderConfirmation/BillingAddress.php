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

		$controller = Package::container()->get( CheckoutFields::class );
		$custom     = $this->render_additional_fields(
			$controller->get_order_additional_fields_with_values( $order, 'address', 'billing', 'view' )
		);

		return $address . $phone . $custom;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'additionalAddressFields', Package::container()->get( CheckoutFields::class )->get_fields_for_location( 'address' ), true );
	}
}
