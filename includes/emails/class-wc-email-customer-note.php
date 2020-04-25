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
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Note extends WC_Email {
		/**
		 * Order.
		 *
		 * @var \WC_Order
		 */
		public $object;

		/**
		 * Customer note.
		 *
		 * @var string
		 */
		public $customer_note;

		/**
		 * Initialize placeholders.
		 *
		 * @param array $placeholders contains placeholder keys and values.
		 */
		protected function init_placeholders( array $placeholders = array() ) {
			parent::init_placeholders(
				array(
					'{order_date}' => '',
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
			$this->id = 'customer_note';
		}

		/**
		 * Initialize title.
		 */
		protected function init_title() {
			$this->title = __( 'Customer note', 'woocommerce' );
		}

		/**
		 * Initialize description.
		 */
		protected function init_description() {
			$this->description = __( 'Customer note emails are sent when you add a note to an order.', 'woocommerce' );
		}

		/**
		 * Initialize template html.
		 */
		protected function init_template_html() {
			$this->template_html = 'emails/customer-note.php';
		}

		/**
		 * Initialize template plain.
		 */
		protected function init_template_plain() {
			$this->template_plain = 'emails/plain/customer-note.php';
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
		 * True when the email notification is sent to customers.
		 *
		 * @var bool
		 */
		protected function init_customer_email() {
			$this->customer_email = true;
		}


		/**
		 * Instance specific hooks
		 */
		protected function hooks() {
			add_action( 'woocommerce_new_customer_note_notification', array( $this, 'trigger' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Note added to your {site_title} order from {order_date}', 'woocommerce' );
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

				$order_id            = $args['order_id'];
				$this->customer_note = $args['customer_note'];

				if ( $order_id ) {
					$this->object = wc_get_order( $order_id );

					if ( $this->object instanceof \WC_Order ) {
						$this->init_recipient();
						$this->fill_placeholders();
					}
				}
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
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
				'customer_note'      => $this->customer_note,
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
				'customer_note'      => $this->customer_note,
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
			return __( 'Thanks for reading.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Note();
