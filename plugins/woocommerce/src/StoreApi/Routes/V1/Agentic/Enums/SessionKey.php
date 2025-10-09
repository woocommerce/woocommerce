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
}
