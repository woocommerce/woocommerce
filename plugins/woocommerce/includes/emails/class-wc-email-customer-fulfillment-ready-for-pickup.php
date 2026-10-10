<?php
/**
 * Class WC_Email_Customer_Fulfillment_Ready_For_Pickup file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Fulfillment_Ready_For_Pickup', false ) ) :

	/**
	 * Customer Fulfillment Ready For Pickup Email.
	 *
	 * Sent to the customer when the merchant marks a local pickup fulfillment ready for pickup. It names the pickup location.
	 *
	 * @class       WC_Email_Customer_Fulfillment_Ready_For_Pickup
	 * @version     11.3.0
	 * @package     WooCommerce\Classes\Emails
	 */
	class WC_Email_Customer_Fulfillment_Ready_For_Pickup extends WC_Email {
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
			$this->id             = 'customer_fulfillment_ready_for_pickup';
			$this->customer_email = true;
			$this->title          = __( 'Ready for pickup', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-fulfillment-ready-for-pickup.php';
			$this->template_plain = 'emails/plain/customer-fulfillment-ready-for-pickup.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_fulfillment_ready_for_pickup_notification', array( $this, 'trigger' ), 10, 3 );

			// Call parent constructor.
			parent::__construct();

			$this->description = __( 'Sent to the customer when you mark local pickup items ready for pickup. It names the pickup location.', 'woocommerce' );

			$this->template_block_content = 'emails/block/general-block-content-for-fulfillment-emails.php';
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param Fulfillment    $fulfillment The fulfillment.
		 * @param WC_Order|false $order Order object.
		 * @return void
		 */
		public function trigger( $order_id, $fulfillment, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! $order instanceof WC_Order ) {
				$order = wc_get_order( $order_id );
			}

			if ( $order instanceof WC_Order ) {
				$date_created                         = $order->get_date_created();
				$this->object                         = $order;
				$this->fulfillment                    = $fulfillment;
				$this->recipient                      = $order->get_billing_email();
				$this->placeholders['{order_date}']   = $date_created ? wc_format_datetime( $date_created ) : '';
				$this->placeholders['{order_number}'] = $order->get_order_number();
			}

			$this->send_notification();

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @since 11.3.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} order {order_number} is ready for pickup', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 11.3.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your order is ready for pickup', 'woocommerce' );
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
		 * Get block editor email template content.
		 *
		 * @return string
		 */
		public function get_block_editor_email_template_content() {
			$this->maybe_init_fulfillment_for_preview( $this->object );
			return wc_get_template_html(
				$this->template_block_content,
				array(
					'order'         => $this->object,
					'fulfillment'   => $this->fulfillment,
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 11.3.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'See you soon.', 'woocommerce' );
		}

		/**
		 * Initialize fulfillment for email preview.
		 *
		 * This method sets up a dummy fulfillment object when the email is being previewed in the admin.
		 *
		 * @param mixed $order The order object.
		 * @return void
		 *
		 * @since 11.3.0
		 */
		private function maybe_init_fulfillment_for_preview( $order ) {
			/**
			 * Filter to determine if this is an email preview.
			 *
			 * @since 9.8.0
			 */
			$is_email_preview = apply_filters( 'woocommerce_is_email_preview', false );
			if ( $is_email_preview && $order instanceof WC_Order ) {
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

				$this->fulfillment->set_status( FulfillmentUtils::STATUS_READY_FOR_PICKUP );
				$this->fulfillment->add_meta_data( FulfillmentUtils::PICKUP_LOCATION_META_KEY, get_bloginfo( 'name', 'display' ) );
				$this->fulfillment->add_meta_data( FulfillmentUtils::PICKUP_ADDRESS_META_KEY, '123 Main Street, Austin, TX 78701' );
				$this->fulfillment->add_meta_data( FulfillmentUtils::PICKUP_DETAILS_META_KEY, __( 'Bring your order number.', 'woocommerce' ) );
			}
		}
	}

endif;

return new WC_Email_Customer_Fulfillment_Ready_For_Pickup();
