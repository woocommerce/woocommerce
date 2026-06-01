<?php
/**
 * Class WC_Email_Customer_Withdrawal_Status file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'WC_Email_Customer_Withdrawal_Status', false ) ) :

	/**
	 * Customer Withdrawal Status Email.
	 *
	 * Sent to the customer when a store admin approves or rejects their withdrawal
	 * request, as required by EU Directive 2023/2673.
	 *
	 * @class   WC_Email_Customer_Withdrawal_Status
	 * @version 10.9.0
	 * @package WooCommerce\Classes\Emails
	 * @extends WC_Email
	 */
	class WC_Email_Customer_Withdrawal_Status extends WC_Email {

		/**
		 * The withdrawal request ID.
		 *
		 * @var string
		 */
		public $request_id = '';

		/**
		 * The new status: 'approved' or 'rejected'.
		 *
		 * @var string
		 */
		public $new_status = '';

		/**
		 * Admin notes attached to the update.
		 *
		 * @var string
		 */
		public $admin_notes = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_withdrawal_status';
			$this->customer_email = true;
			$this->title          = __( 'Withdrawal request update', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-withdrawal-status.php';
			$this->template_plain = 'emails/plain/customer-withdrawal-status.php';
			$this->placeholders   = array(
				'{order_date}'     => '',
				'{order_number}'   => '',
				'{site_title}'     => '',
				'{request_status}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_withdrawal_request_updated_notification', array( $this, 'trigger' ), 10, 4 );

			// Call parent constructor.
			parent::__construct();

			$this->description = __( 'Withdrawal request status emails are sent to customers when the store admin approves or rejects their withdrawal request.', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int    $order_id    The order ID.
		 * @param string $request_id  The withdrawal request ID.
		 * @param string $new_status  The new status (approved or rejected).
		 * @param string $admin_notes Admin notes attached to the update.
		 */
		public function trigger( $order_id, $request_id = '', $new_status = '', $admin_notes = '' ) {
			$this->setup_locale();

			$order = wc_get_order( $order_id );
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$this->object                           = $order;
			$this->request_id                       = $request_id;
			$this->new_status                       = $new_status;
			$this->admin_notes                      = $admin_notes;
			$this->recipient                        = $order->get_billing_email();
			$this->placeholders['{order_date}']     = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}']   = $order->get_order_number();
			$this->placeholders['{site_title}']     = $this->get_blogname();
			$this->placeholders['{request_status}'] = $this->get_status_label( $new_status );

			$this->send_notification();

			$this->restore_locale();
		}

		/**
		 * Get human-readable status label.
		 *
		 * @param string $status Status slug.
		 * @return string
		 */
		private function get_status_label( $status ) {
			$labels = array(
				'approved' => __( 'Approved', 'woocommerce' ),
				'rejected' => __( 'Rejected', 'woocommerce' ),
			);
			return $labels[ $status ] ?? ucfirst( $status );
		}

		/**
		 * Get email subject.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your withdrawal request for order {order_number} has been {request_status}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Withdrawal request update', 'woocommerce' );
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
					'new_status'         => $this->new_status,
					'admin_notes'        => $this->admin_notes,
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
					'new_status'         => $this->new_status,
					'admin_notes'        => $this->admin_notes,
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
			return __( 'If you have any questions about this decision, please contact the store directly.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Withdrawal_Status();
