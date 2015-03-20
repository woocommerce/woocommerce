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

		$this->id             = 'customer_refunded_order';
		$this->title          = __( 'Refunded order', 'woocommerce' );
		$this->description    = __( 'Order refunded emails are sent to customers when their orders are marked refunded.', 'woocommerce' );

		$this->heading        = __( 'Your order has been refunded', 'woocommerce' );
		$this->subject        = __( 'Your {site_title} order from {order_date} has been refunded', 'woocommerce' );

		$this->template_html  = 'emails/customer-refunded-order.php';
		$this->template_plain = 'emails/plain/customer-refunded-order.php';

		// Triggers for this email
		add_action( 'woocommerce_order_status_refunded_notification', array( $this, 'trigger' ) );

		// Call parent constuctor
		parent::__construct();
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object    = wc_get_order( $order_id );
			$this->recipient = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
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
	 * Initialise Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes'
			),
			'subject' => array(
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading' => array(
				'title'       => __( 'Email Heading', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
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
