<?php
/**
 * Class WC_Email_Customer_POS_Completed_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Internal\Email\OrderPriceFormatter;
use Automattic\WooCommerce\Internal\Orders\PointOfSaleOrderUtil;

if ( ! class_exists( 'WC_Email_Customer_POS_Completed_Order', false ) ) :

	/**
	 * Customer Completed Order Email.
	 *
	 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
	 *
	 * @class       WC_Email_Customer_POS_Completed_Order
	 * @version     2.0.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_POS_Completed_Order extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_pos_completed_order';
			$this->customer_email = true;
			$this->title          = __( 'POS completed order', 'woocommerce' );
			$this->template_html  = 'emails/customer-pos-completed-order.php';
			$this->template_plain = 'emails/plain/customer-pos-completed-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			$this->enable_order_email_actions_for_pos_orders();

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Let shoppers know once their POS order is complete.', 'woocommerce' )
				: __( 'Order complete emails are sent to customers when their POS orders are marked completed.', 'woocommerce' );

			$this->manual = true;
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int    $order_id The order ID.
		 * @param string $template_id The email template ID.
		 */
		public function trigger( $order_id, $template_id ) {
			if ( $this->id !== $template_id ) {
				return;
			}

			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return $this->email_improvements_enabled
				? __( 'Your order from {site_title} is on its way!', 'woocommerce' )
				: __( 'Your {site_title} order is now complete', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'Good things are heading your way!', 'woocommerce' )
				: __( 'Thanks for shopping with us', 'woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			$this->add_pos_customizations();
			$content = wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
			$this->remove_pos_customizations();
			return $content;
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			$this->add_pos_customizations();
			$content = wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
			$this->remove_pos_customizations();
			return $content;
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for shopping with us.', 'woocommerce' );
		}

		/**
		 * Get block editor email template content.
		 *
		 * @return string
		 */
		public function get_block_editor_email_template_content() {
			$this->add_pos_customizations();
			return wc_get_template_html(
				$this->template_block_content,
				array(
					'order'         => $this->object,
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => $this,
				)
			);
		}

		/**
		 * Enable order email actions for POS orders.
		 */
		private function enable_order_email_actions_for_pos_orders() {
			$this->enable_email_template_for_pos_orders();
			// Enable send email when requested.
			add_action( 'woocommerce_rest_order_actions_email_send', array( $this, 'trigger' ), 10, 2 );
		}

		/**
		 * Add actions and filters before generating email content.
		 */
		private function add_pos_customizations() {
			// Add action to display unit price in the beginning of the order item meta.
			add_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10, 4 );
			// Add filter to include additional details in the order item totals table.
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10, 3 );
		}

		/**
		 * Remove actions and filters after generating email content.
		 */
		private function remove_pos_customizations() {
			// Remove actions and filters after generating content to avoid affecting other emails.
			remove_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10 );
			remove_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10 );
		}

		/**
		 * Add unit price to order item meta start position.
		 *
		 * @param int      $item_id       Order item ID.
		 * @param array    $item          Order item data.
		 * @param WC_Order $order         Order object.
		 */
		public function add_unit_price( $item_id, $item, $order ) {
			$unit_price = OrderPriceFormatter::get_formatted_item_subtotal( $order, $item, get_option( 'woocommerce_tax_display_cart' ) );
			echo wp_kses_post( '<br /><small>' . $unit_price . '</small>' );
		}

		/**
		 * Add additional details to the order item totals table.
		 *
		 * @param array    $total_rows Array of total rows.
		 * @param WC_Order $order      Order object.
		 * @param string   $tax_display Tax display.
		 * @return array Modified array of total rows.
		 */
		public function order_item_totals( $total_rows, $order, $tax_display ) {
			$cash_payment_change_due_amount           = $order->get_meta( '_cash_change_amount', true );
			$formatted_cash_payment_change_due_amount = wc_price( $cash_payment_change_due_amount, array( 'currency' => $order->get_currency() ) );
			if ( ! empty( $cash_payment_change_due_amount ) ) {
				$total_rows['cash_payment_change_due_amount'] = array(
					'type'  => 'cash_payment_change_due_amount',
					'label' => __( 'Change due:', 'woocommerce' ),
					'value' => $formatted_cash_payment_change_due_amount,
				);
			}

			$auth_code = $order->get_meta( '_charge_id', true );
			if ( ! empty( $auth_code ) ) {
				$total_rows['payment_auth_code'] = array(
					'type'  => 'payment_auth_code',
					'label' => __( 'Auth code:', 'woocommerce' ),
					'value' => $auth_code,
				);
			}

			if ( $order->get_date_paid() !== null ) {
				$total_rows['date_paid'] = array(
					'type'  => 'date_paid',
					'label' => __( 'Time of payment:', 'woocommerce' ),
					'value' => wc_format_datetime( $order->get_date_paid(), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				);
			}

			return $total_rows;
		}

		/**
		 * Enable email template for REST API order valid templates for POS orders.
		 */
		private function enable_email_template_for_pos_orders() {
			add_filter( 'woocommerce_rest_order_actions_email_valid_template_classes', array( $this, 'add_to_valid_template_classes' ), 10, 2 );
		}

		/**
		 * Add this email template to the list of valid templates for POS orders.
		 *
		 * @param array    $valid_template_classes Array of valid template class names.
		 * @param WC_Order $order                  The order.
		 * @return array Modified array of valid template class names.
		 */
		public function add_to_valid_template_classes( $valid_template_classes, $order ) {
			if ( ! PointOfSaleOrderUtil::is_pos_order( $order ) ) {
				return $valid_template_classes;
			}
			$valid_template_classes[] = get_class( $this );
			return $valid_template_classes;
		}
	}

endif;

return new WC_Email_Customer_POS_Completed_Order();
