<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

/**
 * Summary class.
 */
class Summary extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-summary';

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
		if ( ! $permission ) {
			return '';
		}

		$content  = '<ul class="wc-block-order-confirmation-summary-list">';
		$content .= $this->render_summary_row( __( 'Order number:', 'woocommerce' ), $order->get_order_number() );
		$content .= $this->render_summary_row( __( 'Date:', 'woocommerce' ), wc_format_datetime( $order->get_date_created() ) );
		$content .= $this->render_summary_row( __( 'Total:', 'woocommerce' ), $order->get_formatted_order_total() );
		if ( 'full' === $permission ) {
			$content .= $this->render_summary_row( __( 'Email:', 'woocommerce' ), $order->get_billing_email() );
			$content .= $this->render_summary_row( __( 'Payment method:', 'woocommerce' ), $order->get_payment_method_title() );
		}
		$content .= '</ul>';

		return $content;
	}

	/**
	 * Render row in the order summary.
	 *
	 * @param string $name name of row.
	 * @param string $value value of row.
	 * @return string
	 */
	protected function render_summary_row( $name, $value ) {
		return $value ? '<li class="wc-block-order-confirmation-summary-list-item"><span class="wc-block-order-confirmation-summary-list-item__key">' . esc_html( $name ) . '</span> <span class="wc-block-order-confirmation-summary-list-item__value">' . wp_kses_post( $value ) . '</span></li>' : '';
	}
}
