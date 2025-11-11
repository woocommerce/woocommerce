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
	 * Whether payment is in progress.
	 */
	const AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS = 'agentic_checkout_payment_in_progress';

	/**
	 * Provider ID that authenticated the request.
	 */
	const AGENTIC_CHECKOUT_PROVIDER_ID = 'agentic_checkout_provider_id';
}
