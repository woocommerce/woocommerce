<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

/**
 * AdditionalInformation class.
 */
class AdditionalInformation extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-additional-information';

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

		$this->remove_core_hooks();
		$content .= $this->get_hook_content( 'woocommerce_thankyou_' . $order->get_payment_method(), [ $order->get_id() ] );
		$content .= $this->get_hook_content( 'woocommerce_thankyou', [ $order->get_id() ] );
		$this->restore_core_hooks();

		return $content;
	}

	/**
	 * Remove core hooks from the thankyou page.
	 */
	protected function remove_core_hooks() {
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
	}

	/**
	 * Restore core hooks from the thankyou page.
	 */
	protected function restore_core_hooks() {
		add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
	}
}
