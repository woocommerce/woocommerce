<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Customer_Invoice', false ) ) :

/**
 * Customer Invoice.
 *
 * An email sent to the customer via admin.
 *
 * @class       WC_Email_Customer_Invoice
 * @version     2.3.0
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
 * @extends     WC_Email
 */
class WC_Email_Customer_Invoice extends WC_Email {

	/**
	 * Strings to find in subjects/headings.
	 *
	 * @var array
	 */
	public $find;

	/**
	 * Strings to replace in subjects/headings.
	 *
	 * @var array
	 */
	public $replace;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id             = 'customer_invoice';
		$this->title          = __( 'Customer invoice', 'woocommerce' );
		$this->description    = __( 'Customer invoice emails can be sent to customers containing their order information and payment links.', 'woocommerce' );

		$this->template_html  = 'emails/customer-invoice.php';
		$this->template_plain = 'emails/plain/customer-invoice.php';

		$this->subject        = __( 'Invoice for order {order_number} from {order_date}', 'woocommerce' );
		$this->heading        = __( 'Invoice for order {order_number}', 'woocommerce' );

		$this->subject_paid   = __( 'Your {site_title} order from {order_date}', 'woocommerce' );
		$this->heading_paid   = __( 'Order {order_number} details', 'woocommerce' );

		// Call parent constructor
		parent::__construct();

		$this->customer_email = true;
		$this->manual         = true;
		$this->heading_paid   = $this->get_option( 'heading_paid', $this->heading_paid );
		$this->subject_paid   = $this->get_option( 'subject_paid', $this->subject_paid );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int $order_id The order ID.
	 * @param WC_Order $order Order object.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                  = $order;
			$this->recipient               = $this->object->get_billing_email();

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = wc_format_datetime( $this->object->get_date_created() );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get email subject.
	 *
	 * @access public
	 * @return string
	 */
	public function get_subject() {
		if ( $this->object->has_status( wc_get_is_paid_statuses() ) ) {
			return apply_filters( 'woocommerce_email_subject_customer_invoice_paid', $this->format_string( $this->subject_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_subject_customer_invoice', $this->format_string( $this->subject ), $this->object );
		}
	}

	/**
	 * Get email heading.
	 *
	 * @access public
	 * @return string
	 */
	public function get_heading() {
		if ( $this->object->has_status( array( 'completed', 'processing' ) ) ) {
			return apply_filters( 'woocommerce_email_heading_customer_invoice_paid', $this->format_string( $this->heading_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_heading_customer_invoice', $this->format_string( $this->heading ), $this->object );
		}
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this,
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this,
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'subject' => array(
				'title'         => __( 'Email subject', 'woocommerce' ),
				'type'          => 'text',
				/* translators: %s: default subject */
				'description'   => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->subject . '</code>' ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true,
			),
			'heading' => array(
				'title'         => __( 'Email heading', 'woocommerce' ),
				'type'          => 'text',
				/* translators: %s: default heading */
				'description'   => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->heading . '</code>' ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true,
			),
			'subject_paid' => array(
				'title'         => __( 'Email subject (paid)', 'woocommerce' ),
				'type'          => 'text',
				/* translators: %s: default subject */
				'description'   => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->subject_paid . '</code>' ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true,
			),
			'heading_paid' => array(
				'title'         => __( 'Email heading (paid)', 'woocommerce' ),
				'type'          => 'text',
				/* translators: %s: default heading */
				'description'   => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->heading_paid . '</code>' ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true,
			),
			'email_type' => array(
				'title'         => __( 'Email type', 'woocommerce' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options(),
				'desc_tip'      => true,
			),
		);
	}
}

endif;

return new WC_Email_Customer_Invoice();
