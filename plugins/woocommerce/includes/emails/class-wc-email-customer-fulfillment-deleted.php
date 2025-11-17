<?php
/**
 * Class WC_Email_Customer_Fulfillment_Deleted file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Fulfillment_Deleted', false ) ) :

	/**
	 * Customer Fulfillment Deleted Email.
	 *
	 * Fulfillment deleted emails are sent to the customer when the merchant cancels an already fulfilled fulfillment. The notification isn’t sent for draft fulfillments.
	 *
	 * @class       WC_Email_Customer_Fulfillment_Deleted
	 * @version     1.0.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Fulfillment_Deleted extends WC_Email {
		/**
		 * Fulfillment object.
		 *
		 * @var Fulfillment|null
		 */
		private $fulfillment;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_fulfillment_deleted';
			$this->customer_email = true;
			$this->title          = __( 'Fulfillment deleted', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-fulfillment-deleted.php';
			$this->template_plain = 'emails/plain/customer-fulfillment-deleted.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_fulfillment_deleted_notification', array( $this, 'trigger' ), 10, 3 );

			// Call parent constructor.
			parent::__construct();

			$this->description = __( 'Fulfillment deleted emails are sent to the customer when the merchant cancels an already fulfilled fulfillment. The notification isn’t sent for draft fulfillments.', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param Fulfillment    $fulfillment The fulfillment.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $fulfillment, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->fulfillment                    = $fulfillment;
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
			return __( 'A shipment from {site_title} order {order_number} has been cancelled', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'One of your shipments has been removed', 'woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			$this->maybe_init_fulfillment_for_preview( $this->object );
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'fulfillment'        => $this->fulfillment,
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
			$this->maybe_init_fulfillment_for_preview( $this->object );
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'fulfillment'        => $this->fulfillment,
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
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'If you have any questions or notice anything unexpected, feel free to reach out to our support team through your account or reply to this email.', 'woocommerce' );
		}

		/**
		 * Initialize fulfillment for email preview.
		 *
		 * This method sets up a dummy fulfillment object when the email is being previewed in the admin.
		 *
		 * @param WC_Order $order The order object.
		 *
		 * @since 10.1.0
		 */
		private function maybe_init_fulfillment_for_preview( $order ) {
			/**
			 * Filter to determine if this is an email preview.
			 *
			 * @since 9.8.0
			 */
			$is_email_preview = apply_filters( 'woocommerce_is_email_preview', false );
			if ( $is_email_preview ) {
				// If this is a preview, we need to set up a dummy fulfillment object.
				$this->fulfillment = new Fulfillment();
				$this->fulfillment->set_items(
					array_map(
						function ( $item ) {
							return array(
								'item_id' => $item->get_id(),
								'qty'     => 1,
							);
						},
						$order->get_items()
					)
				);

				// Set the deleted status.
				$this->fulfillment->set_date_deleted( gmdate( 'Y-m-d H:i:s' ) );
			}
		}
	}

endif;

return new WC_Email_Customer_Fulfillment_Deleted();
