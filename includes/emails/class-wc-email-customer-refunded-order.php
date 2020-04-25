<?php
/**
 * Class WC_Email_Customer_Refunded_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Refunded_Order', false ) ) :

	/**
	 * Customer Refunded Order Email.
	 *
	 * Order refunded emails are sent to the customer when the order is marked refunded.
	 *
	 * @class    WC_Email_Customer_Refunded_Order
	 * @version  3.5.0
	 * @package  WooCommerce/Classes/Emails
	 * @extends  WC_Email
	 */
	class WC_Email_Customer_Refunded_Order extends WC_Email {
		/**
		 * Order.
		 *
		 * @var \WC_Order
		 */
		public $object;

		/**
		 * Refund order.
		 *
		 * @var WC_Order|bool
		 */
		public $refund;

		/**
		 * Is the order partial refunded?
		 *
		 * @var bool
		 */
		public $partial_refund;

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
			$this->id = 'customer_refunded_order';
		}

		/**
		 * Initialize title.
		 */
		protected function init_title() {
			$this->title = __( 'Refunded order', 'woocommerce' );
		}

		/**
		 * Initialize description.
		 */
		protected function init_description() {
			$this->description = __( 'Order refunded emails are sent to customers when their orders are refunded.', 'woocommerce' );
		}

		/**
		 * Initialize template html.
		 */
		protected function init_template_html() {
			$this->template_html = 'emails/customer-refunded-order.php';
		}

		/**
		 * Initialize template plain.
		 */
		protected function init_template_plain() {
			$this->template_plain = 'emails/plain/customer-refunded-order.php';
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
		 * Instance specific hooks
		 */
		protected function hooks() {
			add_action( 'woocommerce_order_fully_refunded_notification', array( $this, 'trigger_full' ), 10, 2 );
			add_action( 'woocommerce_order_partially_refunded_notification', array( $this, 'trigger_partial' ), 10, 2 );
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject( $partial = false ) {
			if ( $partial ) {
				return __( 'Your {site_title} order #{order_number} has been partially refunded', 'woocommerce' );
			} else {
				return __( 'Your {site_title} order #{order_number} has been refunded', 'woocommerce' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			if ( $partial ) {
				return __( 'Partial Refund: Order {order_number}', 'woocommerce' );
			} else {
				return __( 'Order Refunded: {order_number}', 'woocommerce' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->partial_refund ) {
				$subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
			} else {
				$subject = $this->get_option( 'subject_full', $this->get_default_subject() );
			}
			return apply_filters( 'woocommerce_email_subject_customer_refunded_order', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->partial_refund ) {
				$heading = $this->get_option( 'heading_partial', $this->get_default_heading( true ) );
			} else {
				$heading = $this->get_option( 'heading_full', $this->get_default_heading() );
			}
			return apply_filters( 'woocommerce_email_heading_customer_refunded_order', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Set email strings.
		 *
		 * @param bool $partial_refund Whether it is a partial refund or a full refund.
		 * @deprecated 3.1.0 Unused.
		 */
		public function set_email_strings( $partial_refund = false ) {}

		/**
		 * Full refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 */
		public function trigger_full( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, false, $refund_id );
		}

		/**
		 * Partial refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 */
		public function trigger_partial( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, true, $refund_id );
		}

		/**
		 * Trigger.
		 *
		 * @param int  $order_id Order ID.
		 * @param bool $partial_refund Whether it is a partial refund or a full refund.
		 * @param int  $refund_id Refund ID.
		 */
		public function trigger( $order_id, $partial_refund = false, $refund_id = null ) {
			$this->setup_locale();
			$this->partial_refund = $partial_refund;
			$this->id             = $this->partial_refund ? 'customer_partially_refunded_order' : 'customer_refunded_order';

			if ( $order_id ) {
				$this->object = wc_get_order( $order_id );
				if ( $this->object instanceof \WC_Order ) {
					$this->init_recipient();
					$this->fill_placeholders();
				}
			}

			if ( ! empty( $refund_id ) ) {
				$this->refund = wc_get_order( $refund_id );
			} else {
				$this->refund = false;
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
				'refund'             => $this->refund,
				'partial_refund'     => $this->partial_refund,
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
				'refund'             => $this->refund,
				'partial_refund'     => $this->partial_refund,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
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
			return __( 'We hope to see you again soon.', 'woocommerce' );
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
				'subject_full'       => array(
					'title'       => __( 'Full refund subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'subject_partial'    => array(
					'title'       => __( 'Partial refund subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_full'       => array(
					'title'       => __( 'Full refund email heading', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'heading_partial'    => array(
					'title'       => __( 'Partial refund email heading', 'woocommerce' ),
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

return new WC_Email_Customer_Refunded_Order();
