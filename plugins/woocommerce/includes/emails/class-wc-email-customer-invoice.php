<?php
/**
 * Class WC_Email_Customer_Invoice file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Invoice', false ) ) :

	/**
	 * Order details email.
	 *
	 * An email sent to the customer via admin, that summarizes the details of their order. This was
	 * historically referred to as the 'invoice', and for backwards compatibility reasons that is still
	 * reflected in the class name (although on a user-level we have moved away from that nomenclature).
	 *
	 * @class       WC_Email_Customer_Invoice
	 * @version     3.5.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Invoice extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_invoice';
			$this->customer_email = true;
			$this->title          = __( 'Order details', 'woocommerce' );
			$this->email_group    = 'payments';
			$this->template_html  = 'emails/customer-invoice.php';
			$this->template_plain = 'emails/plain/customer-invoice.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Manually send an email to your customers containing their order information and payment links', 'woocommerce' )
				: __( 'Order detail emails can be sent to customers containing their order information and payment links.', 'woocommerce' );

			$this->manual = true;

			if ( $this->block_email_editor_enabled ) {
				$this->title       = __( 'Payment request', 'woocommerce' );
				$this->description = __( 'Manually send customers an email to review their order and complete payment.', 'woocommerce' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject( $paid = false ) {
			return __( 'Details for order #{order_number} on {site_title}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading( $paid = false ) {
			return __( 'Details for order #{order_number}', 'woocommerce' );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->object->has_status( array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) ) ) {
				$subject = $this->get_option( 'subject_paid', $this->get_default_subject( true ) );

				if ( $this->block_email_editor_enabled ) {
					$subject = $this->personalizer->personalize_transactional_content( $subject, $this );
				}

				return apply_filters( 'woocommerce_email_subject_customer_invoice_paid', $this->format_string( $subject ), $this->object, $this );
			}

			$subject = $this->get_option( 'subject', $this->get_default_subject() );

			if ( $this->block_email_editor_enabled ) {
				$subject = $this->personalizer->personalize_transactional_content( $subject, $this );
			}

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
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for using {site_url}!', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int      $order_id The order ID.
		 * @param WC_Order $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( $this->get_recipient() ) {
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
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
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
			if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
				$this->form_fields['cc']  = $this->get_cc_field();
				$this->form_fields['bcc'] = $this->get_bcc_field();
			}
			if ( $this->block_email_editor_enabled ) {
				$this->form_fields['preheader'] = $this->get_preheader_field();
			}
		}
	}

endif;

return new WC_Email_Customer_Invoice();
