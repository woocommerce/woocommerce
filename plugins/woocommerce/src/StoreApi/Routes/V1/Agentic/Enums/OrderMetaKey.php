<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums;

/**
 * Order meta keys used in Agentic Checkout.
 */
class OrderMetaKey {
	/**
	 * Checkout session ID for this order.
	 */
	const CHECKOUT_SESSION_ID = 'agentic_session_id';
}
