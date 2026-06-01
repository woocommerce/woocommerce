<?php
/**
 * Class WC_Email_New_Withdrawal_Request file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'WC_Email_New_Withdrawal_Request', false ) ) :

	/**
	 * New Withdrawal Request Email.
	 *
	 * Sent to the admin/mail forwarding address when a customer submits a
	 * withdrawal request.
	 *
	 * @class   WC_Email_New_Withdrawal_Request
	 * @version 10.9.0
	 * @package WooCommerce\Classes\Emails
	 * @extends WC_Email
	 */
	class WC_Email_New_Withdrawal_Request extends WC_Email {

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
			$this->id             = 'new_withdrawal_request';
			$this->customer_email = false;
			$this->title          = __( 'New withdrawal request', 'woocommerce' );
			$this->email_group    = 'admin-notifications';
			$this->template_html  = 'emails/admin-withdrawal-request.php';
			$this->template_plain = 'emails/plain/admin-withdrawal-request.php';
			$this->placeholders   = array(
				'{order_date}'           => '',
				'{order_number}'         => '',
				'{site_title}'           => '',
				'{request_date_created}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_new_withdrawal_request_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			$this->description = __( 'Admin notification emails are sent to the store admin when a customer submits a withdrawal request.', 'woocommerce' );
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

			$this->object                                 = $order;
			$this->request_id                             = $request_id;
			$this->recipient                              = $this->get_option( 'recipient', get_option( 'admin_email' ) );
			$this->placeholders['{order_date}']           = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}']         = $order->get_order_number();
			$this->placeholders['{site_title}']           = $this->get_blogname();
			$this->placeholders['{request_date_created}'] = $this->get_request_date_created( $order, $request_id );

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send_notification();
			}

			$this->restore_locale();
		}

		/**
		 * Get the date the withdrawal request was created from the order meta.
		 *
		 * @param WC_Order $order      Order object.
		 * @param string   $request_id Withdrawal request ID.
		 * @return string Formatted date or empty string.
		 */
		private function get_request_date_created( $order, $request_id ) {
			if ( ! is_a( $order, 'WC_Order' ) || '' === $request_id ) {
				return '';
			}
			$requests = $order->get_meta( '_withdrawal_requests', true );
			if ( ! is_array( $requests ) ) {
				return '';
			}
			foreach ( $requests as $request ) {
				if ( isset( $request['request_id'] ) && $request['request_id'] === $request_id && ! empty( $request['date_created'] ) ) {
					$date = $request['date_created'];
					if ( ! $date instanceof WC_DateTime ) {
						$date = wc_string_to_datetime( (string) $date );
					}
					return wc_format_datetime( $date );
				}
			}
			return '';
		}

		/**
		 * Get email subject.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'New withdrawal request for order {order_number} — {site_title}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'New withdrawal request', 'woocommerce' );
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
					'order'                => $this->object,
					'request_id'           => $this->request_id,
					'request_date_created' => $this->placeholders['{request_date_created}'],
					'email_heading'        => $this->get_heading(),
					'additional_content'   => $this->get_additional_content(),
					'sent_to_admin'        => true,
					'plain_text'           => false,
					'email'                => $this,
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
					'order'                => $this->object,
					'request_id'           => $this->request_id,
					'request_date_created' => $this->placeholders['{request_date_created}'],
					'email_heading'        => $this->get_heading(),
					'additional_content'   => $this->get_additional_content(),
					'sent_to_admin'        => true,
					'plain_text'           => true,
					'email'                => $this,
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
			return __( 'You can manage this withdrawal request from the order edit screen.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_New_Withdrawal_Request();
