<?php
/**
 * Class WC_Email_Customer_Abandoned_Cart_Recovery file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\AbandonedCartRecovery\ManualSendHandler;
use Automattic\WooCommerce\Internal\Email\Unsubscribes\Endpoint as UnsubscribesEndpoint;
use Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage as UnsubscribesStorage;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

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
	 * @version  11.0.0
	 * @package  WooCommerce\Classes\Emails
	 */
	class WC_Email_Customer_Abandoned_Cart_Recovery extends WC_Email {

		/**
		 * Email identifier — kept in `$this->id` for the rest of WC_Email's
		 * machinery but also exposed as a constant so static methods (and
		 * external callers using the unsubscribe storage) can reference the
		 * same string without it drifting out of sync with the constructor.
		 */
		public const EMAIL_ID = 'customer_abandoned_cart_recovery';

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
		 * Order meta key recording the timestamp of the most recent send.
		 *
		 * Written by `trigger()` after a successful dispatch (manual or automated) of the abandoned cart recovery email.
		 */
		public const META_KEY_SENT_AT = '_abandoned_cart_recovery_email_sent_at';

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
		 * Delay between order creation and the automated recovery send.
		 *
		 * Deliberately longer than `ABANDONMENT_THRESHOLD_SECONDS` so the auto-send
		 * never races the customer's own checkout retry. The shorter eligibility
		 * threshold still gates manual sends from the order edit page so merchants
		 * have an earlier window to intervene.
		 *
		 * @since 11.0.0
		 */
		public const AUTO_SEND_DELAY_SECONDS = 2 * HOUR_IN_SECONDS;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = self::EMAIL_ID;
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

			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = __( 'Win back shoppers who almost bought. Automatically email customers who didn\'t finish checking out, with a one-click link back to their order.', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * Called by `Scheduler::handle_scheduled_send()` after Action Scheduler dispatches
		 * `woocommerce_send_abandoned_cart_recovery_notification`, and directly by the
		 * manual-send action on the order edit page.
		 *
		 * @since 11.0.0
		 *
		 * @param int $order_id The order ID.
		 * @return bool True when an email was dispatched in this call, false when any
		 *              gate (suppression, disabled, ineligible, unsubscribed, dedup) or
		 *              the underlying `send()` rejected the request.
		 */
		public function trigger( $order_id ): bool {
			if ( self::is_suppressed() ) {
				return false;
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

			$dispatched = false;

			if (
				$this->is_enabled()
				&& $this->get_recipient()
				&& $this->object instanceof WC_Order
				&& $this->is_order_eligible_for_recovery( $this->object )
				&& ! self::is_recipient_unsubscribed( $this->get_recipient() )
				&& '' === (string) $this->object->get_meta( self::META_KEY_SENT_AT )
			) {
				$dispatched = (bool) $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

				// Only record the send timestamp when the dispatch actually succeeded.
				// Subsequent invocations (duplicate AS firings, post-manual auto fires)
				// short-circuit on this meta before reaching `send()` again.
				if ( $dispatched ) {
					$this->object->update_meta_data( self::META_KEY_SENT_AT, (string) time() );
					$this->object->save_meta_data();
				}
			}

			$this->restore_locale();

			return $dispatched;
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
		 * @since 11.0.0
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

			// trigger() refuses to dispatch a second time once META_KEY_SENT_AT is
			// set, so don't surface a control that would silently no-op.
			if ( '' !== (string) $order->get_meta( self::META_KEY_SENT_AT ) ) {
				return $actions;
			}

			$actions[ self::MANUAL_RECOVERY_EMAIL_SEND_ACTION ] = __( 'Send abandoned cart recovery email', 'woocommerce' );

			return $actions;
		}

		/**
		 * Whether an order is in a state that warrants a recovery email.
		 *
		 * The order must (a) be in one of the eligible statuses, (b) have lived
		 * in that state for at least `ABANDONMENT_THRESHOLD_SECONDS` (so we don't
		 * nudge customers who are actively still on the page), and (c) have a
		 * valid billing email — checkout-draft orders in particular can land in
		 * the eligible status without a recipient.
		 *
		 * Single source of truth: called both by `trigger()` (defence-in-depth at
		 * send time) and by the manual-send dropdown gates. Partners can widen the
		 * eligible-status set via `woocommerce_abandoned_cart_recovery_eligible_statuses`
		 * and both paths will agree.
		 *
		 * @since 11.0.0
		 *
		 * @param WC_Order $order Order to evaluate.
		 * @return bool
		 */
		protected function is_order_eligible_for_recovery( WC_Order $order ): bool {
			/**
			 * Filter the order statuses that are eligible to receive the abandoned cart recovery email.
			 *
			 * Defaults to the abandoned-checkout statuses (`pending`, `checkout-draft`). Partner
			 * integrations or merchants who want recovery to fire for other states (e.g. `failed`)
			 * can widen the list here.
			 *
			 * @since 11.0.0
			 *
			 * @param string[] $eligible_statuses Default: ABANDONED_STATUSES.
			 * @param WC_Order $order             Order being inspected.
			 */
			$eligible_statuses = (array) apply_filters(
				'woocommerce_abandoned_cart_recovery_eligible_statuses',
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

			if ( ( time() - $date_created->getTimestamp() ) < self::ABANDONMENT_THRESHOLD_SECONDS ) {
				return false;
			}

			return is_email( $order->get_billing_email() ) !== false;
		}

		/**
		 * Handle a merchant-initiated send from the order edit page.
		 *
		 * Fired by `woocommerce_order_action_send_abandoned_cart_recovery_email` after
		 * the order metabox save flow has validated the request. We re-check the
		 * capability and order status as defense in depth in case the hook is
		 * invoked from a non-metabox path.
		 *
		 * @since 11.0.0
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

			// Don't record an order note for a send that the underlying trigger
			// would silently bail on.
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

			// Mirror trigger()'s dedup gate before firing the before/after
			// resend hooks so extensions don't see a resend event for a send
			// that trigger() would short-circuit.
			if ( '' !== (string) $order->get_meta( self::META_KEY_SENT_AT ) ) {
				return;
			}

			/**
			 * Fires before the abandoned cart recovery email is manually resent.
			 *
			 * @since 11.0.0
			 *
			 * @param WC_Order $order      Order being recovered.
			 * @param string   $email_type Email identifier ('customer_abandoned_cart_recovery').
			 */
			do_action( 'woocommerce_before_resend_order_emails', $order, $this->id );

			// Gate the note + admin notice on trigger()'s return so the audit
			// trail only records sends that actually went out.
			if ( ! $this->trigger( $order->get_id() ) ) {
				return;
			}

			$order->add_order_note(
				__( 'Abandoned cart recovery email sent from the order actions menu.', 'woocommerce' ),
				0,
				true,
				array( 'note_group' => OrderNoteGroup::EMAIL_NOTIFICATION )
			);

			/**
			 * Fires after the abandoned cart recovery email has been manually resent.
			 *
			 * @since 11.0.0
			 *
			 * @param WC_Order $order      Order being recovered.
			 * @param string   $email_type Email identifier ('customer_abandoned_cart_recovery').
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
		 * @since 11.0.0
		 * @return bool
		 */
		public function is_automated(): bool {
			return 'yes' === $this->get_option( 'automated', 'no' );
		}

		/**
		 * Currently-active known recovery handlers, keyed by plugin file path with the display name as value.
		 *
		 * @since 11.0.0
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
		 * The merchant's own opt-out lives on the `enabled` toggle in Settings → Emails,
		 * which `trigger()` checks via `is_enabled()`. This method is the additional gate
		 * partner plugins (AutomateWoo, MailPoet, etc.) can hook into to short-circuit
		 * the send without touching the merchant's saved settings. Static so the
		 * manual-send handler and the scheduler can call it without instantiating
		 * the email class.
		 *
		 * @since 11.0.0
		 *
		 * @return bool
		 */
		public static function is_suppressed(): bool {
			/**
			 * Filter to suppress the abandoned cart recovery email send.
			 *
			 * Partner plugins that handle abandoned cart recovery themselves can
			 * return true here to prevent core from sending a duplicate email.
			 *
			 * @since 11.0.0
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
		 * @since  11.0.0
		 * @return string
		 */
		public function get_recovery_url() {
			if ( ! $this->object instanceof WC_Order ) {
				return '';
			}

			/**
			 * Filter the URL included in the abandoned cart recovery email.
			 *
			 * @since 11.0.0
			 *
			 * @param string   $url   Default: the pending order's pay endpoint.
			 * @param WC_Order $order Order being recovered.
			 */
			return (string) apply_filters( 'woocommerce_abandoned_cart_recovery_url', $this->object->get_checkout_payment_url(), $this->object );
		}

		/**
		 * Get the unsubscribe URL for the currently-bound order's recipient.
		 *
		 * Returns an HMAC-signed URL routed through `Endpoint::QUERY_VAR`
		 * (`?wc-email-unsubscribe=…`) and handled by `UnsubscribesEndpoint`.
		 * Empty when no order is bound or the order has no billing email —
		 * both states mean there's no recipient to unsubscribe and the
		 * template should suppress the footer link.
		 *
		 * @since  11.0.0
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
		 * @since  11.0.0
		 *
		 * @param string $email Raw recipient email.
		 * @return bool
		 */
		public static function is_recipient_unsubscribed( string $email ): bool {
			if ( '' === $email ) {
				return false;
			}
			return wc_get_container()->get( UnsubscribesStorage::class )->is_unsubscribed( $email, self::EMAIL_ID );
		}

		/**
		 * Get default email subject.
		 *
		 * @since  11.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Still want it?', 'woocommerce' );
		}

		/**
		 * Get default email heading.
		 *
		 * @since  11.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Pick up where you left off', 'woocommerce' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since  11.0.0
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

			$active_handlers     = self::get_active_recovery_handlers();
			$enabled_default     = empty( $active_handlers ) ? 'yes' : 'no';
			$enabled_description = empty( $active_handlers )
				? ''
				: sprintf(
					/* translators: %s: comma-separated list of detected plugins that already handle abandoned cart recovery (e.g. "AutomateWoo, MailPoet"). */
					__( '%s is active on this site and already handles abandoned cart recovery. We\'ve turned this off so customers don\'t receive duplicate emails. Enable anyway if you want WooCommerce to handle recovery instead.', 'woocommerce' ),
					implode( ', ', $active_handlers )
				);

			$this->form_fields = array(
				'enabled'            => array(
					'title'       => __( 'Enable/Disable', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable this email notification', 'woocommerce' ),
					'description' => $enabled_description,
					'default'     => $enabled_default,
					'desc_tip'    => '' !== $enabled_description,
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
