<?php
/**
 * Class WC_Email_Customer_Checkout_Recovery file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CheckoutRecovery\ManualSendHandler;
use Automattic\WooCommerce\Internal\Email\Unsubscribes\Endpoint as UnsubscribesEndpoint;
use Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage as UnsubscribesStorage;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email_Customer_Checkout_Recovery', false ) ) :

	/**
	 * Customer Checkout Recovery email.
	 *
	 * A transactional email that prompts the customer to complete a checkout they
	 * left pending. The send is scheduled via Action Scheduler two hours after
	 * the pending order is created, gated on the merchant's `automated` setting.
	 * Merchants can also trigger the email manually from the order edit page.
	 *
	 * @class    WC_Email_Customer_Checkout_Recovery
	 * @version  10.9.0
	 * @package  WooCommerce\Classes\Emails
	 */
	class WC_Email_Customer_Checkout_Recovery extends WC_Email {

		/**
		 * Plugins known to provide their own checkout recovery flow.
		 *
		 * Detection is install-only.
		 */
		public const KNOWN_RECOVERY_HANDLERS = array(
			'automatewoo/automatewoo.php' => 'AutomateWoo',
			'mailpoet/mailpoet.php'       => 'MailPoet',
		);

		/**
		 * Order meta key recording the timestamp of the most recent send.
		 *
		 * Written by `trigger()` after a successful dispatch (manual or automated) of the checkout recovery email.
		 */
		public const META_KEY_SENT_AT = '_checkout_recovery_email_sent_at';

		/**
		 * Order action id used by the recovery email send item on the order edit page.
		 *
		 * Re-exports `ManualSendHandler::MANUAL_SEND_ACTION` so external callers
		 * (and tests) can reference it from the email class. Source of truth lives
		 * on the dispatcher because its hook registration runs before this email
		 * class file is included.
		 */
		public const MANUAL_RECOVERY_EMAIL_SEND_ACTION = ManualSendHandler::MANUAL_SEND_ACTION;

		/**
		 * Order statuses that represent an abandoned checkout for the purposes of
		 * the manual-send action and the trigger-level status gate.
		 *
		 * - `pending`        — classic checkout reached "place order" but no payment yet.
		 * - `checkout-draft` — block checkout (Store API) parks the order here while
		 *                     the customer is mid-flow. May have no billing email yet,
		 *                     in which case `trigger()` no-ops.
		 *
		 * @var string[]
		 */
		private const ABANDONED_STATUSES = array(
			OrderStatus::PENDING,
			OrderStatus::CHECKOUT_DRAFT,
		);

		/**
		 * Minimum age (in seconds, from `date_created`) before an order is considered
		 * actually abandoned. Gives the customer a window to come back and complete
		 * the checkout on their own before merchants can nudge them.
		 */
		public const ABANDONMENT_THRESHOLD_SECONDS = HOUR_IN_SECONDS;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_checkout_recovery';
			$this->customer_email = true;
			$this->title          = __( 'Checkout recovery', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-checkout-recovery.php';
			$this->template_plain = 'emails/plain/customer-checkout-recovery.php';
			$this->template_block = 'emails/block/customer-checkout-recovery.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{site_address}' => wp_parse_url( home_url(), PHP_URL_HOST ),
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Trigger fires after Action Scheduler dispatches `woocommerce_send_checkout_recovery_notification`,
			// or when the merchant invokes the manual-send action from the order edit page.
			// The order-edit action hooks live in `Internal\CheckoutRecovery\ManualSendHandler`
			// so the listener is in place before the admin POST runs the order-meta save flow
			// (which happens before the mailer would otherwise be instantiated).
			add_action( 'woocommerce_send_checkout_recovery_notification', array( $this, 'trigger' ), 10, 1 );

			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = __( 'Recovery emails are sent to customers who leave checkout pending, prompting them to complete their order.', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * Wired to `woocommerce_send_checkout_recovery_notification`, which Action
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

			if (
				$this->is_enabled()
				&& $this->get_recipient()
				&& $this->object instanceof WC_Order
				&& $this->is_order_eligible_for_recovery( $this->object )
				&& ! self::is_recipient_unsubscribed( $this->get_recipient() )
			) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

				// Record the send timestamp so the automated-scheduling integration can dedup
				// against orders that have already been emailed via either path.
				$this->object->update_meta_data( self::META_KEY_SENT_AT, (string) time() );
				$this->object->save_meta_data();
			}

			$this->restore_locale();
		}

		/**
		 * Add the manual-send item to the order actions dropdown.
		 *
		 * Surfaced for the statuses listed in `ABANDONED_STATUSES` (pending +
		 * checkout-draft) so merchants can't accidentally email a "pick up where
		 * you left off" prompt to a customer whose order has already moved past
		 * abandonment. A capability check guards against role configurations that
		 * grant the order edit page to users without `edit_shop_orders`.
		 *
		 * @since 10.9.0
		 *
		 * @param array         $actions Existing order actions keyed by action id.
		 * @param WC_Order|null $order   Order being rendered, or null in contexts without one.
		 * @return array
		 */
		public function register_order_action( $actions, $order ): array {
			if ( ! $order instanceof WC_Order ) {
				return $actions;
			}

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				return $actions;
			}

			// Mirror trigger()'s preconditions: don't surface an action that would
			// silently no-op when the merchant clicks it.
			if ( ! $this->is_enabled() || self::is_suppressed() ) {
				return $actions;
			}

			if ( ! $this->is_order_eligible_for_recovery( $order ) ) {
				return $actions;
			}

			// Customer-side preference wins over merchant action — don't surface
			// "Send" if the recipient has already opted out.
			if ( self::is_recipient_unsubscribed( $order->get_billing_email() ) ) {
				return $actions;
			}

			$actions[ self::MANUAL_RECOVERY_EMAIL_SEND_ACTION ] = __( 'Send checkout recovery email', 'woocommerce' );

			return $actions;
		}

		/**
		 * Whether an order is in a state that warrants a recovery email.
		 *
		 * The order must (a) be in one of the eligible statuses and (b) have lived
		 * in that state for at least `ABANDONMENT_THRESHOLD_SECONDS`, so we don't
		 * nudge customers who are actively still on the page.
		 *
		 * Single source of truth: called both by `trigger()` (defence-in-depth at
		 * send time) and by the manual-send dropdown gates. Partners can widen the
		 * eligible-status set via `woocommerce_checkout_recovery_eligible_statuses`
		 * and both paths will agree.
		 *
		 * @since 10.9.0
		 *
		 * @param WC_Order $order Order to evaluate.
		 * @return bool
		 */
		protected function is_order_eligible_for_recovery( WC_Order $order ): bool {
			/**
			 * Filter the order statuses that are eligible to receive the checkout recovery email.
			 *
			 * Defaults to the abandoned-checkout statuses (`pending`, `checkout-draft`). Partner
			 * integrations or merchants who want recovery to fire for other states (e.g. `failed`)
			 * can widen the list here.
			 *
			 * @since 10.9.0
			 *
			 * @param string[] $eligible_statuses Default: ABANDONED_STATUSES.
			 * @param WC_Order $order             Order being inspected.
			 */
			$eligible_statuses = (array) apply_filters(
				'woocommerce_checkout_recovery_eligible_statuses',
				self::ABANDONED_STATUSES,
				$order
			);
			if ( ! in_array( $order->get_status(), $eligible_statuses, true ) ) {
				return false;
			}

			$date_created = $order->get_date_created();
			if ( ! $date_created ) {
				return false;
			}

			return ( time() - $date_created->getTimestamp() ) >= self::ABANDONMENT_THRESHOLD_SECONDS;
		}

		/**
		 * Handle a merchant-initiated send from the order edit page.
		 *
		 * Fired by `woocommerce_order_action_send_checkout_recovery_email` after
		 * the order metabox save flow has validated the request. We re-check the
		 * capability and order status as defense in depth in case the hook is
		 * invoked from a non-metabox path.
		 *
		 * @since 10.9.0
		 *
		 * @param WC_Order $order The order whose customer should receive the email.
		 * @return void
		 */
		public function handle_recovery_email_send( $order ): void {
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				return;
			}

			// Don't write a "manually sent" order note when the underlying trigger
			// would silently bail. Cheaper to short-circuit here than to inspect
			// the meta after the fact.
			if ( ! $this->is_enabled() || self::is_suppressed() ) {
				return;
			}

			if ( ! $this->is_order_eligible_for_recovery( $order ) ) {
				return;
			}

			// Customer-side preference wins over merchant action — don't bypass
			// an unsubscribe by manual send.
			if ( self::is_recipient_unsubscribed( $order->get_billing_email() ) ) {
				return;
			}

			/**
			 * Fires before the checkout recovery email is manually resent.
			 *
			 * @since 10.9.0
			 *
			 * @param WC_Order $order      Order being recovered.
			 * @param string   $email_type Email identifier ('customer_checkout_recovery').
			 */
			do_action( 'woocommerce_before_resend_order_emails', $order, $this->id );

			$this->trigger( $order->get_id() );

			$order->add_order_note(
				__( 'Checkout recovery email manually sent to customer.', 'woocommerce' ),
				0,
				true,
				array( 'note_group' => OrderNoteGroup::EMAIL_NOTIFICATION )
			);

			/**
			 * Fires after the checkout recovery email has been manually resent.
			 *
			 * @since 10.9.0
			 *
			 * @param WC_Order $order      Order being recovered.
			 * @param string   $email_type Email identifier ('customer_checkout_recovery').
			 */
			do_action( 'woocommerce_after_resend_order_email', $order, $this->id );

			// Reuse the existing "Order updated. Email sent." admin notice (message id 11) on the
			// classic order edit page. HPOS uses a different redirect pipeline that sets the
			// message directly in Edit.php, so this filter is a no-op there — matching the
			// behavior of the built-in `send_order_details` action.
			add_filter( 'redirect_post_location', array( 'WC_Meta_Box_Order_Actions', 'set_email_sent_message' ) );
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
		 * `woocommerce_checkout_recovery_suppress` filter callback short-circuits
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
			$settings = (array) get_option( 'woocommerce_customer_checkout_recovery_settings', array() );

			if ( isset( $settings['suppressed'] ) ) {
				if ( 'yes' === $settings['suppressed'] ) {
					return true;
				}
			} elseif ( ! empty( self::get_active_recovery_handlers() ) ) {
				return true;
			}

			/**
			 * Filter to suppress the checkout recovery email send.
			 *
			 * Partner plugins that handle abandoned-checkout recovery themselves can
			 * return true here to prevent core from sending a duplicate email.
			 *
			 * @since 10.9.0
			 *
			 * @param bool $suppress Default false.
			 */
			return (bool) apply_filters( 'woocommerce_checkout_recovery_suppress', false );
		}

		/**
		 * Get the URL the recovery email should send the customer to.
		 *
		 * Returns the order's pay endpoint, which resumes the checkout for the
		 * pending order. A future iteration may swap this for a single-use signed
		 * URL with explicit expiry (see `woocommerce_checkout_recovery_url` filter).
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_recovery_url() {
			if ( ! $this->object instanceof WC_Order ) {
				return '';
			}

			/**
			 * Filter the URL included in the checkout recovery email.
			 *
			 * @since 10.9.0
			 *
			 * @param string   $url   Default: the pending order's pay endpoint.
			 * @param WC_Order $order Order being recovered.
			 */
			return (string) apply_filters( 'woocommerce_checkout_recovery_url', $this->object->get_checkout_payment_url(), $this->object );
		}

		/**
		 * Get the unsubscribe URL for the currently-bound order's recipient.
		 *
		 * Returns an HMAC-signed `?wc-recovery-unsubscribe=…` URL handled by
		 * `UnsubscribeEndpoint`. Empty when no order is bound or the order has
		 * no billing email — both states mean there's no recipient to unsubscribe
		 * and the template should suppress the footer link.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_unsubscribe_url() {
			if ( ! $this->object instanceof WC_Order ) {
				return '';
			}
			$email = $this->object->get_billing_email();
			if ( '' === $email ) {
				return '';
			}
			return UnsubscribesEndpoint::url_for( $this->object->get_id(), $email, $this->id );
		}

		/**
		 * Whether the given email has opted out of checkout recovery emails.
		 *
		 * Static so the gate can be reused from the trigger-side check, the
		 * dropdown gate, and any future auto-send scheduler — without each
		 * caller needing to thread the repository through.
		 *
		 * @since  10.9.0
		 *
		 * @param string $email Raw recipient email.
		 * @return bool
		 */
		public static function is_recipient_unsubscribed( string $email ): bool {
			if ( '' === $email ) {
				return false;
			}
			return wc_get_container()->get( UnsubscribesStorage::class )->is_unsubscribed( $email, 'customer_checkout_recovery' );
		}

		/**
		 * Get default email subject.
		 *
		 * @since  10.9.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your items at {site_title} are waiting', 'woocommerce' );
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
					'unsubscribe_url'    => $this->get_unsubscribe_url(),
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
					'unsubscribe_url'    => $this->get_unsubscribe_url(),
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
				? __( 'Check this when another plugin handles abandoned checkout recovery, so customers do not receive duplicate emails.', 'woocommerce' )
				: sprintf(
					/* translators: %s: comma-separated list of detected plugins that already handle checkout recovery (e.g. "AutomateWoo, MailPoet"). */
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
					'label'       => __( 'I have another tool handling abandoned checkout recovery', 'woocommerce' ),
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

return new WC_Email_Customer_Checkout_Recovery();
