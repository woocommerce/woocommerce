<?php
/**
 * Class WC_Email_Admin_Payment_Gateway_Enabled file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_Admin_Payment_Gateway_Enabled', false ) ) :

	/**
	 * Payment Gateway Enabled Email.
	 *
	 * An email sent to the admin when a payment gateway is enabled.
	 *
	 * @class   WC_Email_Admin_Payment_Gateway_Enabled
	 * @version 10.6.0
	 * @package WooCommerce\Classes\Emails
	 */
	class WC_Email_Admin_Payment_Gateway_Enabled extends WC_Email {

		/**
		 * Gateway title.
		 *
		 * @var string
		 */
		public $gateway_title = '';

		/**
		 * Gateway settings URL.
		 *
		 * @var string
		 */
		public $gateway_settings_url = '';

		/**
		 * Admin username.
		 *
		 * @var string
		 */
		public $username = '';

		/**
		 * Admin email address.
		 *
		 * @var string
		 */
		public $admin_email = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'admin_payment_gateway_enabled';
			$this->title          = __( 'Payment gateway enabled', 'woocommerce' );
			$this->email_group    = 'payments';
			$this->template_html  = 'emails/admin-payment-gateway-enabled.php';
			$this->template_plain = 'emails/plain/admin-payment-gateway-enabled.php';
			$this->placeholders   = array(
				'{gateway_title}' => '',
				'{site_title}'    => '',
			);

			// Trigger for this email.
			add_action( 'woocommerce_payment_gateway_enabled_notification', array( $this, 'trigger' ), 10, 1 );

			// Block email editor hooks.
			add_action( 'woocommerce_email_general_block_content', array( $this, 'block_content' ), 10, 3 );
			add_filter( 'woocommerce_emails_general_block_content_emails_without_order_details', array( $this, 'exclude_from_order_details' ) );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` and `block_email_editor_enabled` properties.
			$this->description = __( 'Payment gateway enabled emails are sent to chosen recipient(s) when a payment gateway is enabled.', 'woocommerce' );

			if ( $this->block_email_editor_enabled ) {
				$this->description = __( 'Notifies admins when a payment gateway has been enabled.', 'woocommerce' );
			}

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] Payment gateway "{gateway_title}" enabled', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Payment gateway "{gateway_title}" enabled', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @since 10.6.0
		 * @param WC_Payment_Gateway $gateway The gateway that was enabled.
		 * @return void
		 */
		public function trigger( $gateway ) {
			$this->setup_locale();

			if ( is_a( $gateway, 'WC_Payment_Gateway' ) ) {
				$this->object        = $gateway;
				$this->gateway_title = $gateway->get_method_title();

				$this->gateway_settings_url = esc_url_raw(
					self_admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway->id )
				);

				/**
				 * Filters the payment gateway settings URL for the admin payment gateway enabled email.
				 *
				 * @param string           $gateway_settings_url The payment gateway settings URL.
				 * @param WC_Payment_Gateway $gateway The payment gateway object.
				 * @return string The filtered payment gateway settings URL.
				 *
				 * @since 10.7.0
				 */
				$this->gateway_settings_url = apply_filters( 'woocommerce_payment_gateway_enabled_notification_settings_url', $this->gateway_settings_url, $gateway );

				$this->admin_email = get_option( 'admin_email' );
				$user              = get_user_by( 'email', $this->admin_email );
				$this->username    = $user ? $user->user_login : $this->admin_email;

				$this->placeholders['{gateway_title}'] = $this->gateway_title;
				$this->placeholders['{site_title}']    = $this->get_blogname();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get valid recipients.
		 *
		 * Merges addresses from the `wc_payment_gateway_enabled_notification_email_addresses` filter
		 * for backward compatibility.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_recipient() {
			$recipient = parent::get_recipient();

			if ( $this->object instanceof WC_Payment_Gateway ) {
				/**
				 * Allows adding to the addresses that receive payment gateway enabled notifications.
				 *
				 * @param array              $email_addresses The array of email addresses to notify.
				 * @param WC_Payment_Gateway $gateway The gateway that was enabled.
				 * @return array             The augmented array of email addresses to notify.
				 *
				 * @since 8.5.0
				 */
				$extra_addresses = apply_filters( 'wc_payment_gateway_enabled_notification_email_addresses', array(), $this->object );

				if ( ! empty( $extra_addresses ) && is_array( $extra_addresses ) ) {
					$extra_valid = array_filter(
						$extra_addresses,
						function ( $email_address ): bool {
							return (bool) filter_var( $email_address, FILTER_VALIDATE_EMAIL );
						}
					);

					if ( ! empty( $extra_valid ) ) {
						$existing  = array_map( 'trim', explode( ',', $recipient ) );
						$merged    = array_unique( array_merge( $existing, $extra_valid ) );
						$recipient = implode( ', ', array_filter( $merged ) );
					}
				}
			}

			return $recipient;
		}

		/**
		 * Get content html.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'gateway'              => $this->object,
					'gateway_title'        => $this->gateway_title,
					'gateway_settings_url' => $this->gateway_settings_url,
					'username'             => $this->username,
					'admin_email'          => $this->admin_email,
					'email_heading'        => $this->get_heading(),
					'additional_content'   => $this->get_additional_content(),
					'sent_to_admin'        => true,
					'plain_text'           => false,
					'email'                => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'gateway'              => $this->object,
					'gateway_title'        => $this->gateway_title,
					'gateway_settings_url' => $this->gateway_settings_url,
					'username'             => $this->username,
					'admin_email'          => $this->admin_email,
					'email_heading'        => $this->get_heading(),
					'additional_content'   => $this->get_additional_content(),
					'sent_to_admin'        => true,
					'plain_text'           => true,
					'email'                => $this,
				)
			);
		}

		/**
		 * Get block editor email template content.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_block_editor_email_template_content() {
			return wc_get_template_html(
				$this->template_block_content,
				array(
					'gateway'              => $this->object,
					'gateway_title'        => $this->gateway_title,
					'gateway_settings_url' => $this->gateway_settings_url,
					'username'             => $this->username,
					'admin_email'          => $this->admin_email,
					'sent_to_admin'        => true,
					'plain_text'           => false,
					'email'                => $this,
				)
			);
		}

		/**
		 * Output dynamic block content for this email.
		 *
		 * Hooked into `woocommerce_email_general_block_content` to render the gateway
		 * title, security notice, and gateway settings URL inside the ##WOO_CONTENT## area.
		 *
		 * @since 10.6.0
		 * @param bool     $sent_to_admin Whether the email is being sent to admin.
		 * @param bool     $plain_text    Whether the email is being sent as plain text.
		 * @param WC_Email $email         The email object.
		 * @return void
		 */
		public function block_content( $sent_to_admin, $plain_text, $email ): void {
			if ( $this->id !== $email->id ) {
				return;
			}

			$gateway_title = ! empty( $this->gateway_title ) ? $this->gateway_title : __( 'Dummy Gateway', 'woocommerce' );

			$gateway_settings_url = ! empty( $this->gateway_settings_url ) ? $this->gateway_settings_url : __( 'Dummy Settings URL', 'woocommerce' );

			// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- Template-like output.
			// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- Template-like output.
			?>
			<p><?php
				/* translators: %s: gateway title */
				printf( esc_html__( 'The payment gateway "%s" has been enabled.', 'woocommerce' ), esc_html( $gateway_title ) );
			?></p>
			<p><?php esc_html_e( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', 'woocommerce' ); ?></p>
			<p><a href="<?php echo esc_url( $gateway_settings_url ); ?>"><?php echo esc_url( $gateway_settings_url ); ?></a></p>
			<?php
			// phpcs:enable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
			// phpcs:enable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
		}

		/**
		 * Add this email to the list of emails without order details.
		 *
		 * @since 10.6.0
		 * @param array $emails_without_order_details Array of email IDs.
		 * @return array
		 */
		public function exclude_from_order_details( $emails_without_order_details ) {
			$emails_without_order_details[] = 'admin_payment_gateway_enabled';
			return $emails_without_order_details;
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 10.6.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'If this was intentional, you can safely ignore and delete this email.', 'woocommerce' );
		}

		/**
		 * Initialise settings form fields.
		 *
		 * @since 10.6.0
		 * @return void
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
				'recipient'          => array(
					'title'       => __( 'Recipient(s)', 'woocommerce' ),
					'type'        => 'text',
					/* translators: %s: admin email */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
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

return new WC_Email_Admin_Payment_Gateway_Enabled();
