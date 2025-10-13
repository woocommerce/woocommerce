<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Checkout session status values as defined in the Agentic Commerce Protocol.
 */
class CheckoutSessionStatus {
	/**
	 * Session is not ready for payment (missing required information).
	 */
	const NOT_READY_FOR_PAYMENT = 'not_ready_for_payment';

	/**
	 * Session is ready for payment.
	 */
	const READY_FOR_PAYMENT = 'ready_for_payment';

	/**
	 * Session has been completed (payment successful).
	 */
	const COMPLETED = 'completed';

	/**
	 * Session has been canceled.
	 */
	const CANCELED = 'canceled';

	/**
	 * Session is in progress (payment initiated but not complete).
	 */
	const IN_PROGRESS = 'in_progress';

	/**
	 * Allowed statuses for update operations.
	 */
	const ALLOWED_STATUSES_FOR_UPDATE = array( self::NOT_READY_FOR_PAYMENT, self::READY_FOR_PAYMENT );
}
