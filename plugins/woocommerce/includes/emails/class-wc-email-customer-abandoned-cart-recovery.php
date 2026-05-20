<?php
/**
 * Class WC_Email_Customer_Abandoned_Cart_Recovery file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Enums\OrderStatus;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email_Customer_Abandoned_Cart_Recovery', false ) ) :

	/**
	 * Customer Abandoned Cart Recovery email.
	 *
	 * A transactional email that prompts the customer to complete a checkout they
	 * left pending. The send is scheduled via Action Scheduler two hours after
	 * the pending order is created, gated on the merchant's `automated` setting.
	 * Merchants can also trigger the email manually from the order edit page.
	 *
	 * @class    WC_Email_Customer_Abandoned_Cart_Recovery
	 * @version  10.9.0
	 * @package  WooCommerce\Classes\Emails
	 */
	class WC_Email_Customer_Abandoned_Cart_Recovery extends WC_Email {

		/**
		 * Plugins known to provide their own abandoned cart recovery flow.
		 *
		 * Detection is install-only.
		 */
		public const KNOWN_RECOVERY_HANDLERS = array(
			'automatewoo/automatewoo.php' => 'AutomateWoo',
			'mailpoet/mailpoet.php'       => 'MailPoet',
		);

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_abandoned_cart_recovery';
			$this->customer_email = true;
			$this->title          = __( 'Abandoned cart recovery', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-abandoned-cart-recovery.php';
			$this->template_plain = 'emails/plain/customer-abandoned-cart-recovery.php';
			$this->template_block = 'emails/block/customer-abandoned-cart-recovery.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{site_address}' => wp_parse_url( home_url(), PHP_URL_HOST ),
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Trigger fires after Action Scheduler dispatches `woocommerce_send_abandoned_cart_recovery_notification`,
			// or when the merchant invokes the manual-send action from the order edit page.
			add_action( 'woocommerce_send_abandoned_cart_recovery_notification', array( $this, 'trigger' ), 10, 1 );

			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = __( 'Win back shoppers who almost bought. Automatically email customers who didn\'t finish checking out, with a one-click link back to their order.', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * Wired to `woocommerce_send_abandoned_cart_recovery_notification`, which Action
		 * Scheduler fires with the order id as its single argument. Also called
		 * directly by the manual-send action on the order edit page.
		 *
		 * @since 10.9.0
		 *
		 * @param int $order_id The order ID.
		 */
		public function trigger( $order_id ): void {
			if ( self::is_suppressed() ) {
				return;
			}

			$this->setup_locale();

			// Reset state from any previous invocation so a call with an invalid order id
			// cannot re-use the previous recipient / placeholders.
			$this->object                         = false;
			$this->recipient                      = '';
			$this->placeholders['{order_date}']   = '';
			$this->placeholders['{order_number}'] = '';

			$order = $order_id ? wc_get_order( $order_id ) : false;

			if ( $order instanceof WC_Order ) {
				$this->object                         = $order;
				$this->recipient                      = $order->get_billing_email();
				$date_created                         = $order->get_date_created();
				$this->placeholders['{order_date}']   = $date_created ? wc_format_datetime( $date_created ) : '';
				$this->placeholders['{order_number}'] = $order->get_order_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() && $this->is_order_eligible_for_send() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Defence-in-depth status check at send time.
		 *
		 * @since 10.9.0
		 * @return bool
		 */
		protected function is_order_eligible_for_send(): bool {
			if ( ! $this->object instanceof WC_Order ) {
				return false;
			}

			/**
			 * Filter the order statuses that are eligible to receive the abandoned cart recovery email.
			 *
			 * Defaults to `pending` only. Partner integrations or merchants who want recovery
			 * to fire for other states (e.g. `failed`) can widen the list here.
			 *
			 * @since 10.9.0
			 *
			 * @param string[] $eligible_statuses Default: `[ 'pending' ]`.
			 * @param WC_Order $order             Order being inspected.
			 */
			$eligible_statuses = (array) apply_filters(
				'woocommerce_abandoned_cart_recovery_eligible_statuses',
				array( OrderStatus::PENDING ),
				$this->object
			);

			return in_array( $this->object->get_status(), $eligible_statuses, true );
		}

		/**
		 * Whether the merchant has opted into automated scheduling.
		 *
		 * When false, the email is only dispatched via the manual-send action on the
		 * order edit page. The Action Scheduler integration consults this before
		 * scheduling a send.
		 *
		 * @since 10.9.0
		 * @return bool
		 */
		public function is_automated(): bool {
			return 'yes' === $this->get_option( 'automated', 'no' );
		}

		/**
		 * Currently-active known recovery handlers, keyed by plugin file path with the display name as value.
		 *
		 * @since 10.9.0
		 * @return array<string, string> Map of plugin file path → display name for plugins that are active.
		 */
		public static function get_active_recovery_handlers(): array {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			return array_filter(
				self::KNOWN_RECOVERY_HANDLERS,
				static fn( $name, $slug ) => is_plugin_active( $slug ),
				ARRAY_FILTER_USE_BOTH
			);
		}

		/**
		 * Whether the recovery email should be skipped.
		 *
		 * Returns true when either the merchant suppression toggle is on or a
		 * `woocommerce_abandoned_cart_recovery_suppress` filter callback short-circuits
		 * the send. Static so the manual-send handler and the scheduler can call
		 * it without instantiating the email class.
		 *
		 * When the merchant has never saved the suppression toggle (so the option
		 * key isn't present in the saved settings) the check falls back to
		 * `get_active_recovery_handlers()`, mirroring the dynamic default applied
		 * in `init_form_fields()`.
		 *
		 * @since 10.9.0
		 *
		 * @return bool
		 */
		public static function is_suppressed(): bool {
			$settings = (array) get_option( 'woocommerce_customer_abandoned_cart_recovery_settings', array() );

			if ( isset( $settings['suppressed'] ) ) {
				if ( 'yes' === $settings['suppressed'] ) {
					return true;
				}
			} elseif ( ! empty( self::get_active_recovery_handlers() ) ) {
				return true;
			}

			/**
			 * Filter to suppress the abandoned cart recovery email send.
			 *
			 * Partner plugins that handle abandoned cart recovery themselves can
			 * return true here to prevent core from sending a duplicate email.
			 *
			 * @since 10.9.0
			 *
			 * @param bool $suppress Default false.
			 */
			return (bool) apply_filters( 'woocommerce_abandoned_cart_recovery_suppress', false );
		}

		/**
		 * Get the URL the recovery email should send the customer to.
		 *
		 * Returns the order's pay endpoint, which resumes the checkout for the
		 * pending order. A future iteration may swap this for a single-use signed
		 * URL with explicit expiry (see `woocommerce_abandoned_cart_recovery_url` filter).
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_recovery_url() {
			if ( ! $this->object instanceof WC_Order ) {
				return '';
			}

			/**
			 * Filter the URL included in the abandoned cart recovery email.
			 *
			 * @since 10.9.0
			 *
			 * @param string   $url   Default: the pending order's pay endpoint.
			 * @param WC_Order $order Order being recovered.
			 */
			return (string) apply_filters( 'woocommerce_abandoned_cart_recovery_url', $this->object->get_checkout_payment_url(), $this->object );
		}

		/**
		 * Get default email subject.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Still want it?', 'woocommerce' );
		}

		/**
		 * Get default email heading.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Pick up where you left off', 'woocommerce' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'If you have any questions, reply to this email and we\'ll help out.', 'woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'recovery_url'       => $this->get_recovery_url(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'recovery_url'       => $this->get_recovery_url(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Initialise settings form fields.
		 *
		 * Adds an `automated` field on top of the standard WC_Email fields so merchants
		 * can choose between scheduled automatic sends and manual-only dispatch.
		 */
		public function init_form_fields(): void {
			$placeholder_text = sprintf(
				/* translators: %s: list of placeholders */
				__( 'Available placeholders: %s', 'woocommerce' ),
				'<code>' . implode( '</code>, <code>', array_map( 'esc_html', array_keys( $this->placeholders ) ) ) . '</code>'
			);

			$active_handlers      = self::get_active_recovery_handlers();
			$suppress_default     = empty( $active_handlers ) ? 'no' : 'yes';
			$suppress_description = empty( $active_handlers )
				? __( 'Check this when another plugin handles abandoned cart recovery, so customers do not receive duplicate emails.', 'woocommerce' )
				: sprintf(
					/* translators: %s: comma-separated list of detected plugins that already handle abandoned cart recovery (e.g. "AutomateWoo, MailPoet"). */
					__( '%s is active on this site. If its abandoned cart workflow is configured, leave this checked to avoid duplicate emails. Uncheck if you want WooCommerce to handle recovery instead.', 'woocommerce' ),
					implode( ', ', $active_handlers )
				);

			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'yes',
				),
				'suppressed'         => array(
					'title'       => __( 'Suppress when another tool is in use', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'I have another tool handling abandoned cart recovery', 'woocommerce' ),
					'description' => $suppress_description,
					'default'     => $suppress_default,
					'desc_tip'    => true,
				),
				'automated'          => array(
					'title'       => __( 'Send automatically', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Schedule the recovery email to send 2 hours after a checkout is abandoned', 'woocommerce' ),
					'description' => __( 'When disabled, the email is only sent when you trigger it manually from the order edit page.', 'woocommerce' ),
					'default'     => 'no',
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
		}
	}

endif;

return new WC_Email_Customer_Abandoned_Cart_Recovery();
