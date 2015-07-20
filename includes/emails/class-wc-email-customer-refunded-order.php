<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Customer_Refunded_Order' ) ) :

/**
 * Customer Refunded Order Email
 *
 * Order refunded emails are sent to the customer when the order is marked refunded.
 *
 * @class    WC_Email_Customer_Refunded_Order
 * @version  2.4.0
 * @package  WooCommerce/Classes/Emails
 * @author   WooThemes
 * @extends  WC_Email
 */
class WC_Email_Customer_Refunded_Order extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->set_email_strings();

		// Triggers for this email
		add_action( 'woocommerce_order_status_refunded_notification', array( $this, 'trigger' ), null, 3 );
		add_action( 'woocommerce_order_partially_refunded_notification', array( $this, 'trigger' ), null, 3 );


		// Call parent constuctor
		parent::__construct();
	}

	function set_email_strings( $partial_refund = false ) {


		$this->subject_partial     = $this->get_option( 'subject_partial', __( 'Your {site_title} order from {order_date} has been partially refunded', 'woocommerce' ) );
		$this->subject_full        = $this->get_option( 'subject_full', __( 'Your {site_title} order from {order_date} has been refunded', 'woocommerce' ) );

		$this->heading_full        = $this->get_option( 'heading_full', __( 'Your order has been fully refunded', 'woocommerce' ) );
		$this->heading_partial     = $this->get_option( 'heading_partial', __( 'Your order has been partially refunded', 'woocommerce' ) );

		if ( $partial_refund ) {
			$this->id             = 'customer_partially_refunded_order';
			$this->title          = __( 'Partially Refunded order', 'woocommerce' );
			$this->description    = __( 'Order partially refunded emails are sent to customers when their orders are partially refunded.', 'woocommerce' );
			$this->template_html  = 'emails/customer-refunded-order.php';
			$this->template_plain = 'emails/plain/customer-refunded-order.php';
			$this->heading        = $this->heading_partial;
			$this->subject        = $this->subject_partial;
		}
		else {
			$this->id             = 'customer_refunded_order';
			$this->title          = __( 'Refunded order', 'woocommerce' );
			$this->description    = __( 'Order refunded emails are sent to customers when their orders are marked refunded.', 'woocommerce' );
			$this->template_html  = 'emails/customer-refunded-order.php';
			$this->template_plain = 'emails/plain/customer-refunded-order.php';
			$this->heading        = $this->heading_full;
			$this->subject        = $this->subject_full;
		}
	}

	/**
	 * Trigger.
	 */
	function trigger( $order_id, $partial_refund = false, $refund_id = null ) {

		$this->partial_refund = $partial_refund;
		$this->set_email_strings( $partial_refund );

		if ( $order_id ) {
			$this->object    = wc_get_order( $order_id );
			$this->recipient = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( $refund_id ) {
			$this->refund = wc_get_order( $refund_id );
		} else {
			$this->refund = false;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
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
		if ( ! empty( $this->object ) && $this->object->has_downloadable_item() )
			return apply_filters( 'woocommerce_email_subject_customer_refunded_order', $this->format_string( $this->subject_downloadable ), $this->object );
		else
			return apply_filters( 'woocommerce_email_subject_customer_refunded_order', $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * get_heading function.
	 *
	 * @access public
	 * @return string
	 */
	function get_heading() {
		if ( ! empty( $this->object ) && $this->object->has_downloadable_item() )
			return apply_filters( 'woocommerce_email_heading_customer_refunded_order', $this->format_string( $this->heading_downloadable ), $this->object );
		else
			return apply_filters( 'woocommerce_email_heading_customer_refunded_order', $this->format_string( $this->heading ), $this->object );
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
			'order'          => $this->object,
			'refund'		 => $this->refund,
			'partial_refund' => $this->partial_refund,
			'email_heading'  => $this->get_heading(),
			'sent_to_admin'  => false,
			'plain_text'     => false
		) );
		return ob_get_clean();
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'order'          => $this->object,
			'refund'		 => $this->refund,
			'partial_refund' => $this->partial_refund,
			'email_heading'  => $this->get_heading(),
			'sent_to_admin'  => false,
			'plain_text'     => true
		) );
		return ob_get_clean();
	}

	/**
	 * Initialise settings form fields.
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes'
			),
			'subject_full' => array(
				'title'       => __( 'Full Refund Subject', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject_full ),
				'placeholder' => '',
				'default'     => $this->subject_full
			),
			'subject_partial' => array(
				'title'       => __( 'Partial Refund Subject', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject_partial ),
				'placeholder' => '',
				'default'     => $this->subject_partial
			),
			'heading_full' => array(
				'title'       => __( 'Full Refund Email Heading', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading_full ),
				'placeholder' => '',
				'default'     => $this->heading_full
			),
			'heading_partial' => array(
				'title'       => __( 'Partial Refund Email Heading', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading_partial ),
				'placeholder' => '',
				'default'     => $this->heading_partial
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options()
			)
		);
	}
}

endif;

return new WC_Email_Customer_Refunded_Order();
