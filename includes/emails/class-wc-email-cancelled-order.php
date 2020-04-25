<?php
/**
 * Class WC_Email_Cancelled_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_Cancelled_Order', false ) ) :

	/**
	 * Cancelled Order Email.
	 *
	 * An email sent to the admin when an order is cancelled.
	 *
	 * @class       WC_Email_Cancelled_Order
	 * @version     2.2.7
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Cancelled_Order extends WC_Email {
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
					'{order_date}' => '',
					'{order_number}' => '',
					'{order_billing_full_name}' => '',
				)
			);
		}

		/**
		 * Fill placeholders with already available data. Use this method when object already has set all necessary
		 * properties and data available to be filled in placeholders. trigger method is the right place.
		 */
		protected function fill_placeholders() {
			parent::fill_placeholders();
			$this->placeholders['{order_date}']              = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}']            = $this->object->get_order_number();
			$this->placeholders['{order_billing_full_name}'] = $this->object->get_formatted_billing_full_name();
		}

		/**
		 * Initialize email id.
		 */
		protected function init_id() {
			$this->id = 'cancelled_order';
		}

		/**
		 * Initialize title.
		 */
		protected function init_title() {
			$this->title = __( 'Cancelled order', 'woocommerce' );
		}

		/**
		 * Initialize description.
		 */
		protected function init_description() {
			$this->description = __( 'Cancelled order emails are sent to chosen recipient(s) when orders have been marked cancelled (if they were previously processing or on-hold).', 'woocommerce' );
		}

		/**
		 * Initialize template html.
		 */
		protected function init_template_html() {
			$this->template_html = 'emails/admin-cancelled-order.php';
		}

		/**
		 * Initialize template plain.
		 */
		protected function init_template_plain() {
			$this->template_plain = 'emails/plain/admin-cancelled-order.php';
		}

		/**
		 * Initialize valid recipient.
		 */
		protected function init_recipient() {
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Instance specific hooks
		 */
		protected function hooks() {
			add_action( 'woocommerce_order_status_processing_to_cancelled_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'woocommerce_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ), 10, 2 );
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}]: Order #{order_number} has been cancelled', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Order Cancelled: #{order_number}', 'woocommerce' );
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
				$this->fill_placeholders();
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
				'sent_to_admin'      => true,
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
				'sent_to_admin'      => true,
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

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			$placeholder_text  = $this->get_available_placeholders_text( $this->placeholders );
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'yes',
				),
				'recipient'          => array(
					'title'       => __( 'Recipient(s)', 'woocommerce' ),
					'type'        => 'text',
					/* translators: %s: admin email */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email heading', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'woocommerce' ),
					'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

endif;

return new WC_Email_Cancelled_Order();
