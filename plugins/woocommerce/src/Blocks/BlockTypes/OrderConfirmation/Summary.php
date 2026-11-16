<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;

/**
 * Summary class.
 */
class Summary extends AbstractOrderConfirmationBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-summary';

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
			return '';
		}

		$content  = '<ul class="wc-block-order-confirmation-summary-list">';
		$content .= $this->render_summary_row( __( 'Order #:', 'woocommerce' ), $order->get_order_number() );
		$content .= $this->render_summary_row( __( 'Date:', 'woocommerce' ), wc_format_datetime( $order->get_date_created() ) );
		$content .= $this->render_summary_row( __( 'Total:', 'woocommerce' ), $order->get_formatted_order_total() );
		$content .= $this->render_email_row( (string) $order->get_billing_email() );
		$content .= $this->render_summary_row( __( 'Payment:', 'woocommerce' ), $order->get_payment_method_title() );
		$content .= '</ul>';

		return $content;
	}

	/**
	 * Render the email row. The value is an `<address>`, which wptexturize() skips on order pages.
	 *
	 * @param string $email Billing email.
	 * @return string
	 */
	private function render_email_row( string $email ): string {
		if ( '' === $email ) {
			return '';
		}

		return '<li class="wc-block-order-confirmation-summary-list-item"><span class="wc-block-order-confirmation-summary-list-item__key">' . esc_html__( 'Email:', 'woocommerce' ) . '</span> <address class="wc-block-order-confirmation-summary-list-item__value">' . esc_html( $email ) . '</address></li>';
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
