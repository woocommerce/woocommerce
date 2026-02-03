<?php
/**
 * Class WC_Email_Customer_Partially_Refunded_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include the parent class.
require_once __DIR__ . '/class-wc-email-customer-refunded-order.php';

if ( ! class_exists( 'WC_Email_Customer_Partially_Refunded_Order', false ) ) :

	/**
	 * Customer Partially Refunded Order Email.
	 *
	 * Partial refund emails are sent to customers when their order is partially refunded.
	 *
	 * This email is a variant of the WC_Email_Customer_Refunded_Order email used only for the block email editor.
	 *
	 * The WC_Email_Customer_Refunded_Order email is used for both full and partial refunds.
	 *
	 * We created this custom class to maintain backwards compatibility with other integrations that use the WC_Email_Customer_Refunded_Order email.
	 *
	 * The next version of WooCommerce will move more of the functionality from the parent class to this custom class.
	 *
	 * @class    WC_Email_Customer_Partially_Refunded_Order
	 * @version  10.6.0
	 * @package  WooCommerce\Classes\Emails
	 */
	class WC_Email_Customer_Partially_Refunded_Order extends WC_Email_Customer_Refunded_Order {

		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->id             = 'customer_partially_refunded_order';
			$this->title          = __( 'Partially refunded order', 'woocommerce' );
			$this->description    = __( 'Notifies customers when their order has been partially refunded.', 'woocommerce' );
			$this->partial_refund = true;
			$this->template_block = 'emails/block/customer-partially-refunded-order.php';

			// Remove triggers for this email because they will be handled by the parent class.
			remove_action( 'woocommerce_order_fully_refunded_notification', array( $this, 'trigger_full' ), 10 );
			remove_action( 'woocommerce_order_partially_refunded_notification', array( $this, 'trigger_partial' ), 10 );
		}

		/**
		 * Get block editor email template content.
		 *
		 * @return string
		 */
		public function get_block_editor_email_template_content() {
			return wc_get_template_html(
				$this->template_block_content,
				array(
					'order'          => $this->object,
					'refund'         => $this->refund,
					'partial_refund' => $this->partial_refund,
					'sent_to_admin'  => false,
					'plain_text'     => false,
					'email'          => $this,
				)
			);
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			$subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
			/**
			 * Filter the email subject for customer refunded order.
			 *
			 * @param string $subject The email subject.
			 * @param object|bool $order Order object.
			 * @param WC_Email_Customer_Refunded_Order $email Email object.
			 * @since 3.7.0
			 */
			$subject = apply_filters( 'woocommerce_email_subject_customer_refunded_order', $this->format_string( $subject ), $this->object, $this );
			if ( $this->block_email_editor_enabled ) {
				$subject = $this->personalizer->personalize_transactional_content( $subject, $this );
			}
			return $subject;
		}

		/**
		 * Return the name of the option in the WP DB.
		 *
		 * @since 2.6.0
		 * @return string
		 */
		public function get_option_key() {
			$id = 'customer_refunded_order';
			// we need to continue using the parent class's id because we want to maintain backwards compatibility
			// and allow the parent class continue managing the settings options for this class.
			// We can remove this once we have migrated all the settings options to this class.
			return $this->plugin_id . $id . '_settings';
		}
	}

endif;

return new WC_Email_Customer_Partially_Refunded_Order();
