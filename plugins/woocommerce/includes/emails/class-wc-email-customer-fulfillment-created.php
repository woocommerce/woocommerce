<?php
/**
 * Class WC_Email_Customer_Fulfillment_Created file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Fulfillment_Created', false ) ) :

	/**
	 * Customer Fulfillment Created Email.
	 *
	 * Fulfillment created emails are sent to the customer when the merchant creates a fulfillment for the order, and marks it as fulfilled. The notification isn’t sent for draft fulfillments.
	 *
	 * @class       WC_Email_Customer_Fulfillment_Created
	 * @version     1.0.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Fulfillment_Created extends WC_Email {
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
			$this->id             = 'customer_fulfillment_created';
			$this->customer_email = true;
			$this->title          = __( 'Fulfillment created', 'woocommerce' );
			$this->template_html  = 'emails/customer-fulfillment-created.php';
			$this->template_plain = 'emails/plain/customer-fulfillment-created.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_fulfillment_created_notification', array( $this, 'trigger' ), 10, 3 );

			// Call parent constructor.
			parent::__construct();

			$this->description = __( 'Fulfillment created emails are sent to the customer when the merchant creates a fulfillment for the order, and marks it as fulfilled. The notification isn’t sent for draft fulfillments.', 'woocommerce' );
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
			return __( 'An item from {site_title} order {order_number} has been fulfilled!', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your item is on the way!', 'woocommerce' );
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
			return __( 'Please note that couriers may need some time to provide the latest shipping information.', 'woocommerce' );
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

				// Some private meta data to simulate a real fulfillment.
				$this->fulfillment->add_meta_data( '_tracking_number', '123456789' );
				$this->fulfillment->add_meta_data( '_shipment_provider', 'dhl' );
				$this->fulfillment->add_meta_data( '_tracking_url', 'https://www.dhl.com/tracking/123456789' );
				// Some public data to simulate a real fulfillment.
				$this->fulfillment->add_meta_data( 'service', 'Standard Shipping' );
				$this->fulfillment->add_meta_data( 'expected_delivery', '2025-06-30' );

				// Add translations for metadata keys.
				add_filter(
					'woocommerce_fulfillment_meta_key_translations',
					function ( $keys ) {
						$keys['service']           = __( 'Service', 'woocommerce' );
						$keys['expected_delivery'] = __( 'Expected Delivery', 'woocommerce' );
						return $keys;
					}
				);
			}
		}
	}

endif;

return new WC_Email_Customer_Fulfillment_Created();
