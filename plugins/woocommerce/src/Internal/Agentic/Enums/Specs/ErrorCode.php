<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Error codes for message errors as defined in the Agentic Commerce Protocol.
 */
class ErrorCode {
	/**
	 * Required field is missing.
	 */
	const MISSING = 'missing';

	/**
	 * Field value is invalid.
	 */
	const INVALID = 'invalid';

	/**
	 * Product is out of stock.
	 */
	const OUT_OF_STOCK = 'out_of_stock';

	/**
	 * Payment was declined.
	 */
	const PAYMENT_DECLINED = 'payment_declined';

	/**
	 * User sign-in is required.
	 */
	const REQUIRES_SIGN_IN = 'requires_sign_in';

	/**
	 * 3D Secure authentication is required.
	 */
	const REQUIRES_3DS = 'requires_3ds';
}
