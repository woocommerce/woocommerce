<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums;

/**
 * Order meta keys used in Agentic Checkout.
 */
class OrderMetaKey {
	/**
	 * Agentic checkout session ID for this order.
	 */
	const CHECKOUT_SESSION_ID = 'checkout_session_id';

	/**
	 * Meta key for canceled checkout sessions.
	 */
	const AGENTIC_CHECKOUT_CANCELED = '_agentic_checkout_canceled';
}
