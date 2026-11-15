<?php
/**
 * ValidationErrorCode - the stable reasons a validation error reports.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Validation error codes, named after WordPress REST's validation reasons.
 *
 * A code says why a value was refused and the error's field says where. Once
 * released, a check's code and field are frozen and its params keys are
 * additive-only: a more precise error becomes a new check with a new code.
 */
final class ValidationErrorCode {

	/**
	 * A value is missing or empty. No params.
	 */
	public const REQUIRED = 'required';

	/**
	 * A value has the wrong type. Params: `type`, a JSON-schema type
	 * (`string`, `integer`, `number`, `boolean`, `array`, `object`).
	 */
	public const INVALID_TYPE = 'invalid_type';

	/**
	 * A number is outside its bounds. Params: `min` and/or `max` (int).
	 */
	public const OUT_OF_BOUNDS = 'out_of_bounds';

	/**
	 * A value is not one of a fixed set. Params: `allowed` (list of strings).
	 */
	public const NOT_IN_ENUM = 'not_in_enum';

	/**
	 * A well-typed value is malformed or forbidden. No params.
	 */
	public const INVALID_VALUE = 'invalid_value';

	/**
	 * A status is not registered. Params: `value` (the refused status).
	 */
	public const NOT_REGISTERED = 'not_registered';

	/**
	 * Constants only.
	 */
	private function __construct() {}
}
