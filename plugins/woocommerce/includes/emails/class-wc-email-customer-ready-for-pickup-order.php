<?php
/**
 * Class WC_Email_Customer_Ready_For_Pickup_Order file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Internal\Orders\ReadyForPickupStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Ready_For_Pickup_Order', false ) ) :

	/**
	 * Customer Ready For Pickup Order Email.
	 *
	 * Sent to the customer when the order is marked ready for pickup. It shows the pickup location chosen at checkout.
	 *
	 * @since 11.3.0
	 */
	class WC_Email_Customer_Ready_For_Pickup_Order extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_ready_for_pickup_order';
			$this->customer_email = true;
			$this->title          = __( 'Ready for pickup', 'woocommerce' );
			$this->description    = __( 'Notifies customers when their order is ready to be picked up, and where to pick it up.', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-ready-for-pickup-order.php';
			$this->template_plain = 'emails/plain/customer-ready-for-pickup-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_order_status_ready-for-pickup_notification', array( $this, 'trigger' ), 10, 2 );

			// Show the pickup location when the email is built with the block email editor.
			add_action( 'woocommerce_email_general_block_content', array( $this, 'render_block_pickup_locations' ), 10, 3 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ): void {
			$this->setup_locale();

			// Reset state from any previous call so an invalid order cannot reuse the previous recipient.
			$this->object                         = false;
			$this->recipient                      = '';
			$this->placeholders['{order_date}']   = '';
			$this->placeholders['{order_number}'] = '';

			if ( $order_id && ! $order instanceof WC_Order ) {
				$order = wc_get_order( $order_id );
			}

			if ( $order instanceof WC_Order ) {
				$date_created                         = $order->get_date_created();
				$this->object                         = $order;
				$this->recipient                      = $order->get_billing_email();
				$this->placeholders['{order_date}']   = $date_created ? wc_format_datetime( $date_created ) : '';
				$this->placeholders['{order_number}'] = $order->get_order_number();
			}

			$this->send_notification();

			$this->restore_locale();
		}

		/**
		 * Get the pickup locations the customer chose for the order.
		 *
		 * @since 11.3.0
		 *
		 * @return array<int, array{name: string, address: string, details: string}>
		 */
		public function get_pickup_locations(): array {
			if ( ! $this->object instanceof WC_Order ) {
				return array();
			}

			return wc_get_container()->get( ReadyForPickupStatus::class )->get_pickup_locations( $this->object );
		}

		/**
		 * Output the pickup locations in the block email content.
		 *
		 * @internal
		 *
		 * @param bool     $sent_to_admin Whether the email is being sent to admin.
		 * @param bool     $plain_text    Whether the email is being sent as plain text.
		 * @param WC_Email $email         The email being rendered.
		 */
		public function render_block_pickup_locations( $sent_to_admin, $plain_text, $email ): void {
			if ( ! $email instanceof WC_Email || $this->id !== $email->id ) {
				return;
			}

			wc_get_template(
				'emails/email-pickup-locations.php',
				array(
					'pickup_locations' => $this->get_pickup_locations(),
					'order'            => $this->object,
					'sent_to_admin'    => (bool) $sent_to_admin,
					'plain_text'       => false,
					'email'            => $this,
				)
			);
		}

		/**
		 * Get email subject.
		 *
		 * @since 11.3.0
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} order is ready for pickup', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 11.3.0
		 *
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
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'pickup_locations'   => $this->get_pickup_locations(),
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
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'pickup_locations'   => $this->get_pickup_locations(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 11.3.0
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for shopping with us.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Ready_For_Pickup_Order();
