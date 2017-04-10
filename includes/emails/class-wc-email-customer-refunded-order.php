<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Customer_Refunded_Order', false ) ) :

/**
 * Customer Refunded Order Email.
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
	 * Constructor.
	 */
	public function __construct() {
		$this->set_email_strings();
		$this->customer_email = true;

		// Triggers for this email
		add_action( 'woocommerce_order_fully_refunded_notification', array( $this, 'trigger_full' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded_notification', array( $this, 'trigger_partial' ), 10, 2 );

		// Call parent constuctor
		parent::__construct();
	}

	/**
	 * Set email strings.
	 *
	 * @param bool $partial_refund
	 */
	public function set_email_strings( $partial_refund = false ) {
		$this->subject_partial     = $this->get_option( 'subject_partial', __( 'Your {site_title} order from {order_date} has been partially refunded', 'woocommerce' ) );
		$this->subject_full        = $this->get_option( 'subject_full', __( 'Your {site_title} order from {order_date} has been refunded', 'woocommerce' ) );

		$this->heading_full        = $this->get_option( 'heading_full', __( 'Your order has been fully refunded', 'woocommerce' ) );
		$this->heading_partial     = $this->get_option( 'heading_partial', __( 'Your order has been partially refunded', 'woocommerce' ) );

		$this->template_html  = 'emails/customer-refunded-order.php';
		$this->template_plain = 'emails/plain/customer-refunded-order.php';

		if ( $partial_refund ) {
			$this->id             = 'customer_partially_refunded_order';
			$this->title          = __( 'Partially refunded order', 'woocommerce' );
			$this->description    = __( 'Order partially refunded emails are sent to customers when their orders are partially refunded.', 'woocommerce' );
			$this->heading        = $this->heading_partial;
			$this->subject        = $this->subject_partial;
		} else {
			$this->id             = 'customer_refunded_order';
			$this->title          = __( 'Refunded order', 'woocommerce' );
			$this->description    = __( 'Order refunded emails are sent to customers when their orders are marked refunded.', 'woocommerce' );
			$this->heading        = $this->heading_full;
			$this->subject        = $this->subject_full;
		}
	}

	/**
	 * Full refund notification.
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 */
	public function trigger_full( $order_id, $refund_id = null ) {
		$this->trigger( $order_id, false, $refund_id );
	}

	/**
	 * Partial refund notification.
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 */
	public function trigger_partial( $order_id, $refund_id = null ) {
		$this->trigger( $order_id, true, $refund_id );
	}

	/**
	 * Trigger.
	 *
	 * @param int $order_id
	 * @param bool $partial_refund
	 * @param int $refund_id
	 */
	public function trigger( $order_id, $partial_refund = false, $refund_id = null ) {
		$this->partial_refund = $partial_refund;
		$this->set_email_strings( $partial_refund );

		if ( $order_id ) {
			$this->object    = wc_get_order( $order_id );
			$this->recipient = $this->object->get_billing_email();

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = wc_format_datetime( $this->object->get_date_created() );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! empty( $refund_id ) ) {
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
	 * Get email subject.
	 *
	 * @access public
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'woocommerce_email_subject_customer_refunded_order', $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * Get email heading.
	 *
	 * @access public
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'woocommerce_email_heading_customer_refunded_order', $this->format_string( $this->heading ), $this->object );
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'          => $this->object,
			'refund'		 => $this->refund,
			'partial_refund' => $this->partial_refund,
			'email_heading'  => $this->get_heading(),
			'sent_to_admin'  => false,
			'plain_text'     => false,
			'email'			 => $this,
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'          => $this->object,
			'refund'		 => $this->refund,
			'partial_refund' => $this->partial_refund,
			'email_heading'  => $this->get_heading(),
			'sent_to_admin'  => false,
			'plain_text'     => true,
			'email'			 => $this,
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			),
			'subject_full' => array(
				'title'       => __( 'Full refund subject', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->subject_full . '</code>' ),
				'placeholder' => '',
				'default'     => $this->subject_full,
				'desc_tip'    => true,
			),
			'subject_partial' => array(
				'title'       => __( 'Partial refund subject', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->subject_partial . '</code>' ),
				'placeholder' => '',
				'default'     => $this->subject_partial,
				'desc_tip'    => true,
			),
			'heading_full' => array(
				'title'       => __( 'Full refund email heading', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default heading */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->heading_full . '</code>' ),
				'placeholder' => '',
				'default'     => $this->heading_full,
				'desc_tip'    => true,
			),
			'heading_partial' => array(
				'title'       => __( 'Partial refund email heading', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default heading */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->heading_partial . '</code>' ),
				'placeholder' => '',
				'default'     => $this->heading_partial,
				'desc_tip'    => true,
			),
			'email_type' => array(
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
