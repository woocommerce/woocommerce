<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums;

/**
 * Session keys used in Agentic Checkout.
 */
class SessionKey {
	/**
	 * Chosen shipping methods. This is not specific to Agentic Checkout.
	 */
	const CHOSEN_SHIPPING_METHODS = 'chosen_shipping_methods';

	/**
	 * Agentic session ID stored in WC session.
	 */
	const AGENTIC_CHECKOUT_SESSION_ID = 'agentic_checkout_session_id';

	/**
	 * Completed order ID.
	 */
	const AGENTIC_CHECKOUT_COMPLETED_ORDER_ID = 'agentic_checkout_completed_order_id';

	/**
	 * Whether if the session has been canceled.
	 */
	const AGENTIC_CHECKOUT_IS_CANCELED = 'agentic_checkout_is_canceled';
}
