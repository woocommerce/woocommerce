<?php
/**
 * Class WC_Email_Customer_Withdrawal_Request file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'WC_Email_Customer_Withdrawal_Request', false ) ) :

	/**
	 * Customer Withdrawal Request Email.
	 *
	 * Sent to the customer when they submit a withdrawal request, acknowledging
	 * receipt as required by EU Directive 2023/2673.
	 *
	 * @class   WC_Email_Customer_Withdrawal_Request
	 * @version 10.9.0
	 * @package WooCommerce\Classes\Emails
	 * @extends WC_Email
	 */
	class WC_Email_Customer_Withdrawal_Request extends WC_Email {

		/**
		 * The withdrawal request ID.
		 *
		 * @var string
		 */
		public $request_id = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_withdrawal_request';
			$this->customer_email = true;
			$this->title          = __( 'Withdrawal request received', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-withdrawal-request.php';
			$this->template_plain = 'emails/plain/customer-withdrawal-request.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
				'{site_title}'   => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_customer_withdrawal_request_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			$this->description = __( 'Withdrawal request acknowledgment emails are sent to customers when they submit a withdrawal request.', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int    $order_id   The order ID.
		 * @param string $request_id The withdrawal request ID.
		 */
		public function trigger( $order_id, $request_id = '' ) {
			$this->setup_locale();

			$order = wc_get_order( $order_id );
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$this->object                         = $order;
			$this->request_id                     = $request_id;
			$this->recipient                      = $order->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{site_title}']   = $this->get_blogname();

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send_notification();
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your withdrawal request has been received', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Withdrawal request received', 'woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'request_id'         => $this->request_id,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'request_id'         => $this->request_id,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 10.9.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thank you for your patience. The merchant will review your request and contact you about the next steps.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Withdrawal_Request();
