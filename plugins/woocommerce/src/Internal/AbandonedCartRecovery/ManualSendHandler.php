<?php
/**
 * ManualSendHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AbandonedCartRecovery;

use WC_Email_Customer_Abandoned_Cart_Recovery;
use WC_Order;

/**
 * Registers the order-edit "Send abandoned cart recovery email" action and routes
 * the merchant click to the email class's handler.
 *
 * This lives outside the email class because the email is only instantiated
 * when `WC_Emails::instance()` is first called, which doesn't reliably happen
 * before `WC_Meta_Box_Order_Actions::save()` dispatches the order-action hook.
 * Registering the hooks from this container-managed service guarantees the
 * listener is in place at admin POST time. The callbacks lazy-load the email
 * instance via the mailer so the heavy email class only gets loaded when it's
 * actually needed.
 *
 * The container auto-calls `init()` after instantiation; resolution is driven
 * by `WooCommerce::maybe_init_abandoned_cart_recovery()`, hooked on `init` priority 1.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class ManualSendHandler {

	/**
	 * Order action id used by the manual-send dropdown item.
	 *
	 * Source of truth lives here (PSR-4 autoloaded) rather than on
	 * `WC_Email_Customer_Abandoned_Cart_Recovery` because this class registers hooks
	 * at WP `init` priority 1 — earlier than `WC_Emails::init()` includes the
	 * legacy email file, so the email class isn't loadable yet.
	 *
	 * Kept in sync with `WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION`,
	 * which re-exports this same value for the email-class callers that use it.
	 */
	public const MANUAL_SEND_ACTION = 'send_abandoned_cart_recovery_email';

	/**
	 * Register hooks and filters.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'woocommerce_order_actions', array( $this, 'register_order_action' ), 10, 2 );
		add_action(
			'woocommerce_order_action_' . self::MANUAL_SEND_ACTION,
			array( $this, 'handle_order_action' ),
			10,
			1
		);
	}

	/**
	 * Filter callback that delegates to the email class's dropdown gate.
	 *
	 * @internal
	 *
	 * @param array         $actions Existing order actions keyed by action id.
	 * @param WC_Order|null $order   Order being rendered, or null in contexts without one.
	 * @return array
	 */
	public function register_order_action( $actions, $order ): array {
		if ( ! is_array( $actions ) ) {
			$actions = array();
		}

		$email = $this->get_email();
		if ( ! $email ) {
			return $actions;
		}

		return $email->register_order_action( $actions, $order );
	}

	/**
	 * Action callback fired from `WC_Meta_Box_Order_Actions::save()` when the
	 * merchant submits the dropdown action.
	 *
	 * @internal
	 *
	 * @param WC_Order $order Order the action was invoked on.
	 */
	public function handle_order_action( $order ): void {
		$email = $this->get_email();
		if ( ! $email ) {
			return;
		}

		$email->handle_recovery_email_send( $order );
	}

	/**
	 * Resolve the registered email instance from WC_Emails. Returns null when
	 * the feature flag is off (in which case `WC_Emails::init()` does not
	 * include the class file) so callers can short-circuit cleanly.
	 *
	 * @return WC_Email_Customer_Abandoned_Cart_Recovery|null
	 */
	private function get_email(): ?WC_Email_Customer_Abandoned_Cart_Recovery {
		$emails = WC()->mailer()->get_emails();
		$email  = $emails['WC_Email_Customer_Abandoned_Cart_Recovery'] ?? null;

		return $email instanceof WC_Email_Customer_Abandoned_Cart_Recovery ? $email : null;
	}
}
