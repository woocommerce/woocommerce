<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums;

/**
 * Session keys used in Agentic Checkout.
 */
class SessionKey {
	/**
	 * Agentic session ID stored in WC session.
	 */
	const AGENTIC_SESSION_ID = 'agentic_session_id';

	/**
	 * Chosen shipping methods.
	 */
	const CHOSEN_SHIPPING_METHODS = 'chosen_shipping_methods';

	/**
	 * Completed order ID.
	 */
	const COMPLETED_ORDER_ID = 'completed_order_id';

	/**
	 * Whether if the session has been canceled.
	 */
	const IS_CANCELED = 'is_canceled';
}
