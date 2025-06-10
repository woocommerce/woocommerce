<?php
/**
 * Class WC_Email_Customer_POS_Completed_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Internal\Email\OrderPriceFormatter;
use Automattic\WooCommerce\Internal\Orders\PointOfSaleOrderUtil;
use Automattic\WooCommerce\Internal\Settings\PointOfSaleDefaultSettings;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! class_exists( 'WC_Email_Customer_POS_Completed_Order', false ) ) :

	/**
	 * Customer Completed Order Email.
	 *
	 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
	 *
	 * @class       WC_Email_Customer_POS_Completed_Order
	 * @version     2.0.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_POS_Completed_Order extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_pos_completed_order';
			$this->customer_email = true;
			$this->title          = __( 'POS completed order', 'woocommerce' );
			$this->template_html  = 'emails/customer-pos-completed-order.php';
			$this->template_plain = 'emails/plain/customer-pos-completed-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			$this->enable_order_email_actions_for_pos_orders();

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Let customers know once their POS order is complete.', 'woocommerce' )
				: __( 'Order complete emails are sent to customers when their POS orders are marked completed.', 'woocommerce' );

			$this->manual = true;
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int    $order_id The order ID.
		 * @param string $template_id The email template ID.
		 */
		public function trigger( $order_id, $template_id ) {
			if ( $this->id !== $template_id ) {
				return;
			}

			$this->setup_locale();

			if ( $order_id ) {
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
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			$store_name = $this->get_pos_store_name();
			/* translators: %1$s: Order number, %2$s: Store name */
			return sprintf( __( 'Your in-store purchase #%1$s at %2$s', 'woocommerce' ), '{order_number}', esc_html( $store_name ) );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Thank you for your in-store purchase', 'woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			$this->add_pos_customizations();
			add_action( 'woocommerce_pos_email_header', array( $this, 'email_header' ) );
			add_action( 'woocommerce_pos_email_footer', array( $this, 'email_footer' ) );
			$content = wc_get_template_html(
				$this->template_html,
				array(
					'order'                     => $this->object,
					'email_heading'             => $this->get_heading(),
					'additional_content'        => $this->get_additional_content(),
					'pos_store_name'            => $this->get_pos_store_name(),
					'pos_store_email'           => $this->get_pos_store_email(),
					'pos_store_phone_number'    => $this->get_pos_store_phone_number(),
					'pos_store_address'         => $this->get_pos_store_address(),
					'pos_refund_returns_policy' => $this->get_pos_refund_returns_policy(),
					'sent_to_admin'             => false,
					'plain_text'                => false,
					'email'                     => $this,
				)
			);
			$this->remove_pos_customizations();
			remove_action( 'woocommerce_pos_email_header', array( $this, 'email_header' ) );
			remove_action( 'woocommerce_pos_email_footer', array( $this, 'email_footer' ) );
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
					'order'                     => $this->object,
					'email_heading'             => $this->get_heading(),
					'additional_content'        => $this->get_additional_content(),
					'pos_store_name'            => $this->get_pos_store_name(),
					'pos_store_email'           => $this->get_pos_store_email(),
					'pos_store_phone_number'    => $this->get_pos_store_phone_number(),
					'pos_store_address'         => $this->get_pos_store_address(),
					'pos_refund_returns_policy' => $this->get_pos_refund_returns_policy(),
					'sent_to_admin'             => false,
					'plain_text'                => true,
					'email'                     => $this,
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
					'order'         => $this->object,
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => $this,
				)
			);
		}

		/**
		 * Enable order email actions for POS orders.
		 */
		private function enable_order_email_actions_for_pos_orders() {
			$this->enable_email_template_for_pos_orders();
			// Enable send email when requested.
			add_action( 'woocommerce_rest_order_actions_email_send', array( $this, 'trigger' ), 10, 2 );
		}

		/**
		 * Override settings form fields to remove the enabled/disabled field as the email is manually sent.
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
			// Add filter for custom footer text with highest priority to run before the default footer text filtering in `WC_Emails`.
			add_filter( 'woocommerce_email_footer_text', array( $this, 'replace_footer_placeholders' ), 1, 2 );
		}

		/**
		 * Remove actions and filters after generating email content.
		 */
		private function remove_pos_customizations() {
			// Remove actions and filters after generating content to avoid affecting other emails.
			remove_action( 'woocommerce_order_item_meta_start', array( $this, 'add_unit_price' ), 10 );
			remove_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10 );
			remove_filter( 'woocommerce_email_footer_text', array( $this, 'replace_footer_placeholders' ), 1 );
		}

		/**
		 * Get the email header.
		 *
		 * @param mixed $email_heading Heading for the email.
		 *
		 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
		 */
		public function email_header( $email_heading ) {
			wc_get_template(
				'emails/email-header.php',
				array(
					'email_heading' => $email_heading,
					'store_name'    => $this->get_pos_store_name(),
				)
			);
		}

		/**
		 * Get the email footer.
		 *
		 * @param mixed $email Email object.
		 *
		 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
		 */
		public function email_footer( $email ) {
			wc_get_template(
				'emails/email-footer.php',
				array(
					'email' => $email,
				)
			);
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
			$cash_payment_change_due_amount = $order->get_meta( '_cash_change_amount', true );
			if ( '' !== $cash_payment_change_due_amount ) {
				$formatted_cash_payment_change_due_amount     = wc_price( $cash_payment_change_due_amount, array( 'currency' => $order->get_currency() ) );
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

		/**
		 * Enable email template for REST API order valid templates for POS orders.
		 */
		private function enable_email_template_for_pos_orders() {
			add_filter( 'woocommerce_rest_order_actions_email_valid_template_classes', array( $this, 'add_to_valid_template_classes' ), 10, 2 );
		}

		/**
		 * Add this email template to the list of valid templates for POS orders.
		 *
		 * @param array    $valid_template_classes Array of valid template class names.
		 * @param WC_Order $order                  The order.
		 * @return array Modified array of valid template class names.
		 */
		public function add_to_valid_template_classes( $valid_template_classes, $order ) {
			if ( ! PointOfSaleOrderUtil::is_pos_order( $order ) ) {
				return $valid_template_classes;
			}
			$valid_template_classes[] = get_class( $this );
			return $valid_template_classes;
		}

		/**
		 * Get the store name from POS settings.
		 *
		 * @return string
		 */
		private function get_pos_store_name() {
			$store_name = get_option( 'woocommerce_pos_store_name' );
			return $this->format_string(
				empty( $store_name ) ? PointOfSaleDefaultSettings::get_default_store_name() : $store_name
			);
		}

		/**
		 * Get the store email from POS settings.
		 *
		 * @return string
		 */
		private function get_pos_store_email() {
			return $this->format_string(
				get_option( 'woocommerce_pos_store_email', PointOfSaleDefaultSettings::get_default_store_email() )
			);
		}

		/**
		 * Get the store phone number from POS settings.
		 *
		 * @return string
		 */
		private function get_pos_store_phone_number() {
			return $this->format_string(
				get_option( 'woocommerce_pos_store_phone' )
			);
		}

		/**
		 * Get the store address from POS settings.
		 *
		 * @return string
		 */
		private function get_pos_store_address() {
			return $this->format_string(
				get_option( 'woocommerce_pos_store_address', PointOfSaleDefaultSettings::get_default_store_address() )
			);
		}

		/**
		 * Get the refund and returns policy from POS settings.
		 *
		 * @return string
		 */
		private function get_pos_refund_returns_policy() {
			return $this->format_string(
				get_option( 'woocommerce_pos_refund_returns_policy' )
			);
		}

		/**
		 * Replace footer text placeholders with POS-specific values.
		 *
		 * @param string $footer_text The footer text to be filtered.
		 * @param mixed  $email       Email object.
		 * @return string Modified footer text.
		 *
		 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
		 */
		public function replace_footer_placeholders( $footer_text, $email ) {
			// Only replace placeholders if we're in the context of a POS email.
			if ( $email->id !== $this->id ) {
				return $footer_text;
			}

			return str_replace(
				array(
					'{site_title}',
					'{store_address}',
					'{store_email}',
				),
				array(
					$this->get_pos_store_name(),
					$this->get_pos_store_address(),
					$this->get_pos_store_email(),
				),
				$footer_text
			);
		}
	}

endif;

return new WC_Email_Customer_POS_Completed_Order();
