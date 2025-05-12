<?php
/**
 * Class WC_Email_Customer_POS_Refunded_Order file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Internal\Email\OrderPriceFormatter;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_POS_Refunded_Order', false ) ) :

	/**
	 * Customer Refunded Order Email.
	 *
	 * Order refunded emails are sent to the customer when the order is marked refunded.
	 *
	 * @class    WC_Email_Customer_POS_Refunded_Order
	 * @version  3.5.0
	 * @package  WooCommerce\Classes\Emails
	 * @extends  WC_Email
	 */
	class WC_Email_Customer_POS_Refunded_Order extends WC_Email {

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
			$this->customer_email = true;
			$this->id             = 'customer_pos_refunded_order';
			$this->title          = __( 'POS refunded order', 'woocommerce' );
			$this->template_html  = 'emails/customer-pos-refunded-order.php';
			$this->template_plain = 'emails/plain/customer-pos-refunded-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Let shoppers know when a full or partial refund is on its way to them for their POS order.', 'woocommerce' )
				: __( 'Order refunded emails are sent to customers when their POS orders are refunded.', 'woocommerce' );
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
				return $this->email_improvements_enabled
					? __( 'Partial refund: Order {order_number}', 'woocommerce' )
					: __( 'Partial Refund: Order {order_number}', 'woocommerce' );
			} else {
				return $this->email_improvements_enabled
					? __( 'Order refunded: {order_number}', 'woocommerce' )
					: __( 'Order Refunded: {order_number}', 'woocommerce' );
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
			/**
			 * Filter the email subject for customer refunded order.
			 *
			 * @param string $subject The email subject.
			 * @param WC_Order $order Order object.
			 * @param WC_Email_Customer_POS_Refunded_Order $email Email object.
			 * @since 3.7.0
			 */
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
			/**
			 * Filter the email heading for customer refunded order.
			 *
			 * @param string $heading The email heading.
			 * @param WC_Order $order Order object.
			 * @param WC_Email_Customer_POS_Refunded_Order $email Email object.
			 * @since 3.7.0
			 */
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
				$this->object                         = wc_get_order( $order_id );
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
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
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			$this->add_pos_customizations();
			$content = wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'refund'             => $this->refund,
					'partial_refund'     => $this->partial_refund,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'blogname'           => $this->get_blogname(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
			$this->remove_pos_customizations();
			return $content;
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			$this->add_pos_customizations();
			$content = wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'refund'             => $this->refund,
					'partial_refund'     => $this->partial_refund,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'blogname'           => $this->get_blogname(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
			$this->remove_pos_customizations();
			return $content;
		}

		/**
		 * Get block editor email template content.
		 *
		 * @return string
		 */
		public function get_block_editor_email_template_content() {
			$this->add_pos_customizations();
			return wc_get_template_html(
				$this->template_block_content,
				array(
					'order'          => $this->object,
					'refund'         => $this->refund,
					'partial_refund' => $this->partial_refund,
					'sent_to_admin'  => false,
					'plain_text'     => false,
					'email'          => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'We hope to see you again soon.', 'woocommerce' );
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
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
			if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
				$this->form_fields['cc']  = $this->get_cc_field();
				$this->form_fields['bcc'] = $this->get_bcc_field();
			}
		}

		/**
		 * Add actions and filters before generating email content.
		 */
		private function add_pos_customizations() {
			// Add action to display unit price in the beginning of the order item meta.
			add_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10, 4 );
			// Add filter to include additional details in the order item totals table.
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10, 3 );
		}

		/**
		 * Remove actions and filters after generating email content.
		 */
		private function remove_pos_customizations() {
			// Remove actions and filters after generating content to avoid affecting other emails.
			remove_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10 );
			remove_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10 );
		}

		/**
		 * Add unit price to order item meta start position.
		 *
		 * @param int      $item_id       Order item ID.
		 * @param array    $item          Order item data.
		 * @param WC_Order $order         Order object.
		 */
		public function add_unit_price( $item_id, $item, $order ) {
			$unit_price = OrderPriceFormatter::get_formatted_item_subtotal( $order, $item, get_option( 'woocommerce_tax_display_cart' ) );
			echo wp_kses_post( '<br /><small>' . $unit_price . '</small>' );
		}

		/**
		 * Add additional details to the order item totals table.
		 *
		 * @param array    $total_rows Array of total rows.
		 * @param WC_Order $order      Order object.
		 * @param string   $tax_display Tax display.
		 * @return array Modified array of total rows.
		 */
		public function order_item_totals( $total_rows, $order, $tax_display ) {
			$cash_payment_change_due_amount           = $order->get_meta( '_cash_change_amount', true );
			$formatted_cash_payment_change_due_amount = wc_price( $cash_payment_change_due_amount, array( 'currency' => $order->get_currency() ) );
			if ( ! empty( $cash_payment_change_due_amount ) ) {
				$total_rows['cash_payment_change_due_amount'] = array(
					'type'  => 'cash_payment_change_due_amount',
					'label' => __( 'Change due:', 'woocommerce' ),
					'value' => $formatted_cash_payment_change_due_amount,
				);
			}

			$auth_code = $order->get_meta( '_charge_id', true );
			if ( ! empty( $auth_code ) ) {
				$total_rows['payment_auth_code'] = array(
					'type'  => 'payment_auth_code',
					'label' => __( 'Auth code:', 'woocommerce' ),
					'value' => $auth_code,
				);
			}

			if ( $order->get_date_paid() !== null ) {
				$total_rows['date_paid'] = array(
					'type'  => 'date_paid',
					'label' => __( 'Time of payment:', 'woocommerce' ),
					'value' => wc_format_datetime( $order->get_date_paid(), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				);
			}

			return $total_rows;
		}
	}

endif;

return new WC_Email_Customer_POS_Refunded_Order();
