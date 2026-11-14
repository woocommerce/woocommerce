<?php
/**
 * Class WC_Email_Backorder file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_Backorder', false ) ) :

	/**
	 * Backorder Email.
	 *
	 * An email sent to the admin when a customer orders a product that is on backorder.
	 *
	 * @class   WC_Email_Backorder
	 * @version 11.2.0
	 * @package WooCommerce\Classes\Emails
	 */
	class WC_Email_Backorder extends WC_Email {

		/**
		 * Body sentence describing the backorder.
		 *
		 * @var string
		 */
		public $message = '';

		/**
		 * The backordered product.
		 *
		 * @var WC_Product|null
		 */
		public $product = null;

		/**
		 * The order the backorder was placed through.
		 *
		 * @var WC_Order|null
		 */
		public $order = null;

		/**
		 * The backordered quantity.
		 *
		 * @var int|float
		 */
		public $quantity = 0;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'backorder';
			$this->title          = __( 'Product backordered', 'woocommerce' );
			$this->email_group    = 'inventory';
			$this->template_html  = 'emails/backorder.php';
			$this->template_plain = 'emails/plain/backorder.php';
			$this->placeholders   = array(
				'{product_name}' => '',
				'{quantity}'     => '',
				'{order_number}' => '',
			);

			// Trigger for this email.
			add_action( 'woocommerce_product_on_backorder_notification', array( $this, 'trigger' ), 10, 1 );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` and `block_email_editor_enabled` properties.
			$this->description = __( 'Product backordered emails are sent to chosen recipient(s) when a customer orders a product that is on backorder.', 'woocommerce' );

			if ( $this->block_email_editor_enabled ) {
				$this->description = __( 'Notifies admins when a product is ordered on backorder.', 'woocommerce' );
			}

			// Other settings. Falls back to the legacy inventory recipient so existing stores keep their configuration.
			$this->recipient = $this->get_option( 'recipient', get_option( 'woocommerce_stock_email_recipient', get_option( 'admin_email' ) ) );
		}

		/**
		 * Get email subject.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] Product backorder', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Product backorder', 'woocommerce' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return '';
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @since 11.2.0
		 * @param array $args {
		 *     Arguments describing the backorder.
		 *
		 *     @type WC_Product $product  The product that is on backorder.
		 *     @type int        $order_id The ID of the order.
		 *     @type int|float  $quantity The amount of product on backorder.
		 * }
		 * @return void
		 */
		public function trigger( $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'product'  => '',
					'quantity' => '',
					'order_id' => '',
				)
			);

			$order = wc_get_order( $args['order_id'] );
			if (
				! $args['product'] ||
				! $args['product'] instanceof WC_Product ||
				! $args['quantity'] ||
				! $order instanceof WC_Order
			) {
				return;
			}

			/**
			 * Determine if the current product should trigger a backorder notification.
			 *
			 * @param bool $send       Whether the backorder notification should be sent.
			 * @param int  $product_id The backordered product id.
			 * @since 11.0.0
			 */
			if ( false === apply_filters( 'woocommerce_should_send_backorder_notification', true, $args['product']->get_id() ) ) {
				return;
			}

			$this->setup_locale();

			$this->object   = $args['product'];
			$this->product  = $args['product'];
			$this->order    = $order;
			$this->quantity = $args['quantity'];

			$stock_before         = $args['quantity'] + $args['product']->get_stock_quantity();
			$backordered_quantity = $args['quantity'] - max( 0, $stock_before );

			$this->placeholders['{product_name}'] = html_entity_decode( wp_strip_all_tags( $args['product']->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			$this->placeholders['{quantity}']     = (string) $backordered_quantity;
			$this->placeholders['{order_number}'] = $order->get_order_number();

			/* translators: 1: backordered quantity 2: product name 3: order number */
			$message = sprintf( __( '%1$s units of %2$s have been backordered in order #%3$s.', 'woocommerce' ), $backordered_quantity, $this->placeholders['{product_name}'], $order->get_order_number() );

			/**
			 * Filter the content of the backorder notification email.
			 *
			 * @since 3.0.0
			 * @param string $message The email message.
			 * @param array $args Backorder arguments.
			 */
			$this->message = apply_filters( 'woocommerce_email_content_backorder', $message, $args );

			$this->send_notification();

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'product'            => $this->product,
					'order'              => $this->order,
					'quantity'           => $this->quantity,
					'message'            => $this->message,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'product'            => $this->product,
					'order'              => $this->order,
					'quantity'           => $this->quantity,
					'message'            => $this->message,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Initialise settings form fields.
		 *
		 * @since 11.2.0
		 */
		public function init_form_fields(): void {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => get_option( 'woocommerce_notify_backorder', 'yes' ),
				),
				'recipient'          => array(
					'title'       => __( 'Recipient(s)', 'woocommerce' ),
					'type'        => 'text',
					/* translators: %s: admin email */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => get_option( 'woocommerce_stock_email_recipient', '' ),
					'desc_tip'    => true,
				),
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
			if ( $this->block_email_editor_enabled ) {
				$this->form_fields['preheader'] = $this->get_preheader_field();
			}
		}
	}

endif;

return new WC_Email_Backorder();
