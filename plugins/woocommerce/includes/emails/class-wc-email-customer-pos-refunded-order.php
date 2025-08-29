<?php
/**
 * Class WC_Email_Customer_POS_Refunded_Order file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Internal\Email\OrderPriceFormatter;
use Automattic\WooCommerce\Internal\Orders\PointOfSaleOrderUtil;
use Automattic\WooCommerce\Internal\Settings\PointOfSaleDefaultSettings;
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
			$this->email_group    = 'payments';
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
				? __( 'Let customers know when a full or partial refund is on its way to them for their POS order.', 'woocommerce' )
				: __( 'Order refunded emails are sent to customers when their POS orders are refunded.', 'woocommerce' );

			$this->disable_default_refund_emails_for_pos_orders();
			$this->register_refund_email_triggers();
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject( $partial = false ) {
			$store_name = $this->get_pos_store_name();
			if ( $partial ) {
				/* translators: %1$s: Store name, %2$s: Order number */
				return sprintf( __( 'Your %1$s order #%2$s has been partially refunded', 'woocommerce' ), esc_html( $store_name ), '{order_number}' );
			} else {
				/* translators: %1$s: Store name, %2$s: Order number */
				return sprintf( __( 'Your %1$s order #%2$s has been refunded', 'woocommerce' ), esc_html( $store_name ), '{order_number}' );
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
		 *
		 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
		 */
		public function trigger_full( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, false, $refund_id );
		}

		/**
		 * Partial refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 *
		 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
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
		private function trigger( $order_id, $partial_refund = false, $refund_id = null ) {
			if ( ! $order_id ) {
				return;
			}
			// Only trigger for POS orders.
			$order = wc_get_order( $order_id );
			if ( ! $order || ! PointOfSaleOrderUtil::is_pos_order( $order ) ) {
				return;
			}
			$this->setup_locale();
			$this->partial_refund = $partial_refund;

			$this->object                         = $order;
			$this->recipient                      = $this->object->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();

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
			add_action( 'woocommerce_pos_email_header', array( $this, 'email_header' ) );
			add_action( 'woocommerce_pos_email_footer', array( $this, 'email_footer' ) );
			$content = wc_get_template_html(
				$this->template_html,
				array(
					'order'                     => $this->object,
					'refund'                    => $this->refund,
					'partial_refund'            => $this->partial_refund,
					'email_heading'             => $this->get_heading(),
					'additional_content'        => $this->get_additional_content(),
					'pos_store_name'            => $this->get_pos_store_name(),
					'pos_store_email'           => $this->get_pos_store_email(),
					'pos_store_phone_number'    => $this->get_pos_store_phone_number(),
					'pos_store_address'         => $this->get_pos_store_address(),
					'pos_refund_returns_policy' => $this->get_pos_refund_returns_policy(),
					'blogname'                  => $this->get_blogname(),
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
					'refund'                    => $this->refund,
					'partial_refund'            => $this->partial_refund,
					'email_heading'             => $this->get_heading(),
					'additional_content'        => $this->get_additional_content(),
					'pos_store_name'            => $this->get_pos_store_name(),
					'pos_store_email'           => $this->get_pos_store_email(),
					'pos_store_phone_number'    => $this->get_pos_store_phone_number(),
					'pos_store_address'         => $this->get_pos_store_address(),
					'pos_refund_returns_policy' => $this->get_pos_refund_returns_policy(),
					'blogname'                  => $this->get_blogname(),
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
		 * Disable default WooCommerce refund emails for POS orders.
		 * The core refund email IDs are in WC_Email_Customer_Refunded_Order's trigger method.
		 *
		 * This method adds filters to prevent the default WooCommerce refund emails
		 * from being sent for orders created through the Point of Sale system.
		 * Instead, the POS-specific refund emails will be used.
		 */
		private function disable_default_refund_emails_for_pos_orders() {
			add_filter( 'woocommerce_email_enabled_customer_partially_refunded_order', array( $this, 'disable_default_refund_email_for_pos_orders' ), 10, 3 );
			add_filter( 'woocommerce_email_enabled_customer_refunded_order', array( $this, 'disable_default_refund_email_for_pos_orders' ), 10, 3 );
		}

		/**
		 * Disable the default WooCommerce refund email for POS orders.
		 *
		 * @param bool          $enabled Whether the email is enabled.
		 * @param WC_Order|null $order   The order object.
		 * @param WC_Email|null $email   The email object.
		 * @return bool
		 *
		 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
		 */
		public function disable_default_refund_email_for_pos_orders( $enabled, $order, $email ) {
			if ( $order && PointOfSaleOrderUtil::is_pos_order( $order ) ) {
				return false;
			}
			return $enabled;
		}

		/**
		 * Register triggers for POS refund emails.
		 *
		 * This method adds actions to trigger the refund emails for POS orders.
		 * It ensures that the emails are sent correctly when a full or partial refund is made.
		 */
		private function register_refund_email_triggers() {
			add_action( 'woocommerce_order_fully_refunded_notification', array( $this, 'trigger_full' ), 10, 2 );
			add_action( 'woocommerce_order_partially_refunded_notification', array( $this, 'trigger_partial' ), 10, 2 );
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
		 * Get the store name from POS settings.
		 *
		 * @return string
		 */
		private function get_pos_store_name() {
			return $this->format_string(
				get_option( 'woocommerce_pos_store_name', PointOfSaleDefaultSettings::get_default_store_name() )
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

return new WC_Email_Customer_POS_Refunded_Order();
