<?php
/**
 * Class WC_Email_No_Stock file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_No_Stock', false ) ) :

	/**
	 * No Stock Email.
	 *
	 * An email sent to the admin when a product runs out of stock.
	 *
	 * @class   WC_Email_No_Stock
	 * @version 11.2.0
	 * @package WooCommerce\Classes\Emails
	 */
	class WC_Email_No_Stock extends WC_Email {

		/**
		 * Body sentence describing the stock change.
		 *
		 * @var string
		 */
		public $message = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'no_stock';
			$this->title          = __( 'Out of stock', 'woocommerce' );
			$this->email_group    = 'inventory';
			$this->template_html  = 'emails/no-stock.php';
			$this->template_plain = 'emails/plain/no-stock.php';
			$this->placeholders   = array(
				'{product_name}' => '',
			);

			// Trigger for this email.
			add_action( 'woocommerce_no_stock_notification', array( $this, 'trigger' ), 10, 1 );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` and `block_email_editor_enabled` properties.
			$this->description = __( 'Out of stock emails are sent to chosen recipient(s) when a product runs out of stock.', 'woocommerce' );

			if ( $this->block_email_editor_enabled ) {
				$this->description = __( 'Notifies admins when a product has run out of stock.', 'woocommerce' );
			}

			// Other settings. Falls back to the legacy inventory recipient so existing stores keep their configuration.
			$this->recipient = $this->get_option( 'recipient', get_option( 'woocommerce_stock_email_recipient', get_option( 'admin_email' ) ) );
		}

		/**
		 * Output the settings screen, appending a link to the threshold that triggers this email.
		 *
		 * The link is added here rather than in $description because that string is
		 * also rendered as a help tip on the email list, where markup is stripped.
		 *
		 * @since 11.2.0
		 * @return void
		 */
		public function admin_options() {
			add_filter( 'woocommerce_email_description', array( $this, 'append_threshold_link' ), 10, 2 );
			parent::admin_options();
			remove_filter( 'woocommerce_email_description', array( $this, 'append_threshold_link' ), 10 );
		}

		/**
		 * Append a link to the inventory threshold setting.
		 *
		 * @since 11.2.0
		 * @param string   $description The email description.
		 * @param WC_Email $email       The email the description belongs to.
		 * @return string
		 */
		public function append_threshold_link( $description, $email ) {
			if ( ! $email instanceof self ) {
				return $description;
			}

			return $description . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory#woocommerce_notify_no_stock_amount' ) ) . '">' . esc_html__( 'Manage threshold', 'woocommerce' ) . '</a>';
		}

		/**
		 * Get email subject.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] Product out of stock', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 11.2.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Product out of stock', 'woocommerce' );
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
		 * @param WC_Product $product Product that ran out of stock.
		 * @return void
		 */
		public function trigger( $product ) {
			if ( ! $product instanceof WC_Product ) {
				return;
			}

			/**
			 * Determine if the current product should trigger a no stock notification.
			 *
			 * @param bool $send       Whether the no stock notification should be sent.
			 * @param int  $product_id The low stock product id.
			 *
			 * @since 4.7.0
			 */
			if ( false === apply_filters( 'woocommerce_should_send_no_stock_notification', true, $product->get_id() ) ) {
				return;
			}

			// If this is a variation but stock is managed at the parent level, use the parent product for the notification.
			if ( $product->is_type( 'variation' ) && 'parent' === $product->get_manage_stock() ) {
				$parent_product = wc_get_product( $product->get_parent_id() );
				if ( $parent_product ) {
					$product = $parent_product;
				}
			}

			$this->setup_locale();

			$this->object = $product;

			$this->placeholders['{product_name}'] = html_entity_decode( wp_strip_all_tags( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) );

			/* translators: %s: product name */
			$message = sprintf( __( '%s is out of stock.', 'woocommerce' ), $this->placeholders['{product_name}'] );

			/**
			 * Filter the content of the no stock notification email.
			 *
			 * @since 3.0.0
			 * @param string $message The email message.
			 * @param WC_Product $product Product instance.
			 */
			$this->message = apply_filters( 'woocommerce_email_content_no_stock', $message, $product );

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
					'product'            => $this->object,
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
					'product'            => $this->object,
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
					'default' => get_option( 'woocommerce_notify_no_stock', 'yes' ),
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

return new WC_Email_No_Stock();
