<?php
/**
 * Class WC_Email_Customer_Note file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Note', false ) ) :

	/**
	 * Customer Note Order Email.
	 *
	 * Customer note emails are sent when you add a note to an order.
	 *
	 * @class       WC_Email_Customer_Note
	 * @version     3.5.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Note extends WC_Email {

		/**
		 * Customer note.
		 *
		 * @var string
		 */
		public $customer_note;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_note';
			$this->customer_email = true;
			$this->title          = __( 'Customer note', 'woocommerce' );
			$this->template_html  = 'emails/customer-note.php';
			$this->template_plain = 'emails/plain/customer-note.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers.
			add_action( 'woocommerce_new_customer_note_notification', array( $this, 'trigger' ) );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Send an email to customers notifying them when you’ve added a note to their order', 'woocommerce' )
				: __( 'Customer note emails are sent when you add a note to an order.', 'woocommerce' );
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return $this->email_improvements_enabled
				? __( 'A note has been added to your order from {site_title}', 'woocommerce' )
				: __( 'Note added to your {site_title} order from {order_date}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'A note has been added to your order', 'woocommerce' );
		}

		/**
		 * Trigger.
		 *
		 * @param array $args Email arguments.
		 */
		public function trigger( $args ) {
			$this->setup_locale();

			if ( ! empty( $args ) ) {
				$defaults = array(
					'order_id'      => '',
					'customer_note' => '',
				);

				$args = wp_parse_args( $args, $defaults );

				$order_id      = $args['order_id'];
				$customer_note = $args['customer_note'];

				if ( $order_id ) {
					$this->object = wc_get_order( $order_id );

					if ( $this->object ) {
						$this->recipient                      = $this->object->get_billing_email();
						$this->customer_note                  = $customer_note;
						$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
						$this->placeholders['{order_number}'] = $this->object->get_order_number();
					}
				}
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
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
					'customer_note'      => $this->customer_note,
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
					'customer_note'      => $this->customer_note,
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
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for reading.', 'woocommerce' );
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
					'order'         => $this->object,
					'customer_note' => $this->customer_note,
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => $this,
				)
			);
		}
	}

endif;

return new WC_Email_Customer_Note();
