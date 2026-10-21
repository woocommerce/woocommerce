<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Internal\Utilities\HtmlSanitizer;

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

		$email = (string) $order->get_billing_email();

		$content  = '<ul class="wc-block-order-confirmation-summary-list">';
		$content .= $this->render_summary_row( __( 'Order #:', 'woocommerce' ), $order->get_order_number() );
		$content .= $this->render_summary_row( __( 'Date:', 'woocommerce' ), wc_format_datetime( $order->get_date_created() ) );
		$content .= $this->render_summary_row( __( 'Total:', 'woocommerce' ), $order->get_formatted_order_total() );
		$content .= $email ? $this->render_summary_row_html( __( 'Email:', 'woocommerce' ), wc_get_container()->get( HtmlSanitizer::class )->esc_html_exact( $email ) ) : '';
		$content .= $this->render_summary_row( __( 'Payment:', 'woocommerce' ), $order->get_payment_method_title() );
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
		return $value ? $this->render_summary_row_html( $name, wp_kses_post( $value ) ) : '';
	}

	/**
	 * Build the markup for a summary row whose value is already safe HTML.
	 *
	 * The email row comes here directly: since WordPress 7.2, wp_kses_post() decodes the character
	 * references esc_html_exact() writes, and wptexturize() would then rewrite the email again.
	 *
	 * @param string $name       Name of the row.
	 * @param string $value_html Escaped value of the row.
	 * @return string
	 */
	private function render_summary_row_html( string $name, string $value_html ): string {
		return '<li class="wc-block-order-confirmation-summary-list-item"><span class="wc-block-order-confirmation-summary-list-item__key">' . esc_html( $name ) . '</span> <span class="wc-block-order-confirmation-summary-list-item__value">' . $value_html . '</span></li>';
	}
}
