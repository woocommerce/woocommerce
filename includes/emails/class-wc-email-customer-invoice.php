<?php
/**
 * Class WC_Email_Customer_Invoice file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Invoice', false ) ) :

	/**
	 * Customer Invoice.
	 *
	 * An email sent to the customer via admin.
	 *
	 * @class       WC_Email_Customer_Invoice
	 * @version     3.5.0
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Invoice extends WC_Email {
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
			$this->id = 'customer_invoice';
		}

		/**
		 * Initialize title.
		 */
		protected function init_title() {
			$this->title = __( 'Customer invoice / Order details', 'woocommerce' );
		}

		/**
		 * Initialize description.
		 */
		protected function init_description() {
			$this->description = __( 'Customer invoice emails can be sent to customers containing their order information and payment links.', 'woocommerce' );
		}

		/**
		 * Initialize template html.
		 */
		protected function init_template_html() {
			$this->template_html = 'emails/customer-invoice.php';
		}

		/**
		 * Initialize template plain.
		 */
		protected function init_template_plain() {
			$this->template_plain = 'emails/plain/customer-invoice.php';
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
		 * True when the email notification is sent manually only.
		 *
		 * @var bool
		 */
		protected function init_manual() {
			$this->manual = true;
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject( $paid = false ) {
			if ( $paid ) {
				return __( 'Invoice for order #{order_number} on {site_title}', 'woocommerce' );
			} else {
				return __( 'Your latest {site_title} invoice', 'woocommerce' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading( $paid = false ) {
			if ( $paid ) {
				return __( 'Invoice for order #{order_number}', 'woocommerce' );
			} else {
				return __( 'Your invoice for order #{order_number}', 'woocommerce' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->object->has_status( array( 'completed', 'processing' ) ) ) {
				$subject = $this->get_option( 'subject_paid', $this->get_default_subject( true ) );

				return apply_filters( 'woocommerce_email_subject_customer_invoice_paid', $this->format_string( $subject ), $this->object, $this );
			}

			$subject = $this->get_option( 'subject', $this->get_default_subject() );
			return apply_filters( 'woocommerce_email_subject_customer_invoice', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->object->has_status( wc_get_is_paid_statuses() ) ) {
				$heading = $this->get_option( 'heading_paid', $this->get_default_heading( true ) );
				return apply_filters( 'woocommerce_email_heading_customer_invoice_paid', $this->format_string( $heading ), $this->object, $this );
			}

			$heading = $this->get_option( 'heading', $this->get_default_heading() );
			return apply_filters( 'woocommerce_email_heading_customer_invoice', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for using {site_address}!', 'woocommerce' );
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
				$this->init_recipient();
				$this->fill_placeholders();
			}

			if ( $this->get_recipient() ) {
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
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			);
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			$placeholder_text  = $this->get_available_placeholders_text( $this->placeholders );
			$this->form_fields = array(
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
				'subject_paid'       => array(
					'title'       => __( 'Subject (paid)', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_paid'       => array(
					'title'       => __( 'Email heading (paid)', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading( true ),
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

return new WC_Email_Customer_Invoice();
