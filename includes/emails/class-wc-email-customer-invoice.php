<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Customer_Invoice' ) ) :

/**
 * Customer Invoice
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

	public $find;
	public $replace;

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id             = 'customer_invoice';
		$this->title          = __( 'Customer invoice', 'woocommerce' );
		$this->description    = __( 'Customer invoice emails can be sent to customers containing their order information and payment links.', 'woocommerce' );

		$this->template_html  = 'emails/customer-invoice.php';
		$this->template_plain = 'emails/plain/customer-invoice.php';

		$this->subject        = __( 'Invoice for order {order_number} from {order_date}', 'woocommerce');
		$this->heading        = __( 'Invoice for order {order_number}', 'woocommerce');

		$this->subject_paid   = __( 'Your {site_title} order from {order_date}', 'woocommerce');
		$this->heading_paid   = __( 'Order {order_number} details', 'woocommerce');

		// Call parent constructor
		parent::__construct();

		$this->heading_paid   = $this->get_option( 'heading_paid', $this->heading_paid );
		$this->subject_paid   = $this->get_option( 'subject_paid', $this->subject_paid );
	}

	/**
	 * Trigger.
	 */
	function trigger( $order ) {

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		if ( $order ) {
			$this->object                  = $order;
			$this->recipient               = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_subject function.
	 *
	 * @access public
	 * @return string
	 */
	function get_subject() {
		if ( $this->object->has_status( array( 'processing', 'completed' ) ) ) {
			return apply_filters( 'woocommerce_email_subject_customer_invoice_paid', $this->format_string( $this->subject_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_subject_customer_invoice', $this->format_string( $this->subject ), $this->object );
		}
	}

	/**
	 * get_heading function.
	 *
	 * @access public
	 * @return string
	 */
	function get_heading() {
		if ( $this->object->has_status( array( 'completed', 'processing' ) ) ) {
			return apply_filters( 'woocommerce_email_heading_customer_invoice_paid', $this->format_string( $this->heading_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_heading_customer_invoice', $this->format_string( $this->heading ), $this->object );
		}
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false
		) );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true
		) );
		return ob_get_clean();
	}

	/**
	 * Initialise settings form fields
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'subject' => array(
				'title'         => __( 'Email Subject', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder'   => '',
				'default'       => ''
			),
			'heading' => array(
				'title'         => __( 'Email Heading', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading ),
				'placeholder'   => '',
				'default'       => ''
			),
			'subject_paid' => array(
				'title'         => __( 'Email Subject (paid)', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject_paid ),
				'placeholder'   => '',
				'default'       => ''
			),
			'heading_paid' => array(
				'title'         => __( 'Email Heading (paid)', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading_paid ),
				'placeholder'   => '',
				'default'       => ''
			),
			'email_type' => array(
				'title'         => __( 'Email Type', 'woocommerce' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options()
			)
		);
	}
}

endif;

return new WC_Email_Customer_Invoice();
