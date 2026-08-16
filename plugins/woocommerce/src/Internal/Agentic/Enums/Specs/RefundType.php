<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Refund types as defined in the Agentic Commerce Protocol.
 */
class RefundType {
	/**
	 * Refund to store credit.
	 */
	const STORE_CREDIT = 'store_credit';

	/**
	 * Refund to original payment method.
	 */
	const ORIGINAL_PAYMENT = 'original_payment';
}
