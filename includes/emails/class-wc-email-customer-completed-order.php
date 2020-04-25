<?php
/**
 * Class WC_Email_Customer_Completed_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Completed_Order', false ) ) :

	/**
	 * Customer Completed Order Email.
	 *
	 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
	 *
	 * @class       WC_Email_Customer_Completed_Order
	 * @version     2.0.0
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Completed_Order extends WC_Email {

		/**
		 * Order.
		 *
		 * @var \WC_Order
		 */
		public $object;

		/**
		 * Initialize placeholders.
		 *
		 * @param array $placeholders contains placeholder keys and values.
		 */
		protected function init_placeholders( array $placeholders = array() ) {
			parent::init_placeholders(
				array(
					'{order_date}'   => '',
					'{order_number}' => '',
				)
			);
		}

		/**
		 * Fill placeholders with already available data. Use this method when object already has set all necessary
		 * properties and data available to be filled in placeholders. trigger method is the right place.
		 */
		protected function fill_placeholders() {
			parent::fill_placeholders();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		}

		/**
		 * Initialize email id.
		 */
		protected function init_id() {
			$this->id = 'customer_completed_order';
		}

		/**
		 * Initialize title.
		 */
		protected function init_title() {
			$this->title = __( 'Completed order', 'woocommerce' );
		}

		/**
		 * Initialize description.
		 */
		protected function init_description() {
			$this->description = __( 'Order complete emails are sent to customers when their orders are marked completed and usually indicate that their orders have been shipped.', 'woocommerce' );
		}

		/**
		 * Initialize template html.
		 */
		protected function init_template_html() {
			$this->template_html = 'emails/customer-completed-order.php';
		}

		/**
		 * Initialize template plain.
		 */
		protected function init_template_plain() {
			$this->template_plain = 'emails/plain/customer-completed-order.php';
		}

		/**
		 * Initialize customer email property.
		 */
		protected function init_customer_email() {
			$this->customer_email = true;
		}

		/**
		 * Initialize valid recipient.
		 */
		protected function init_recipient() {
			if ( $this->object instanceof \WC_Order ) {
				$this->recipient = $this->object->get_billing_email();
			} else {
				parent::init_recipient();
			}
		}

		/**
		 * Instance specific hooks
		 */
		protected function hooks() {
			add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 10, 2 );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int           $order_id The order ID.
		 * @param WC_Order|null $order Order object.
		 */
		public function trigger( $order_id, \WC_Order $order = null ) {
			$this->setup_locale();

			if ( ! empty( $order ) ) {
				$this->object = $order;
			} elseif ( ! empty( (int) $order_id ) ) {
				$this->object = wc_get_order( $order_id );
			}

			if ( $this->object instanceof \WC_Order ) {
				$this->init_recipient();
				$this->fill_placeholders();
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
			return __( 'Your {site_title} order is now complete', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Thanks for shopping with us', 'woocommerce' );
		}

		/**
		 * Get arguments for get_content_html method.
		 *
		 * @return array
		 */
		public function get_content_html_args() {
			return array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			);
		}

		/**
		 * Get arguments for get_content_plain method.
		 *
		 * @return array
		 */
		public function get_content_plain_args() {
			return array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for shopping with us.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Completed_Order();
