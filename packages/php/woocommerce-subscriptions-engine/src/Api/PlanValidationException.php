<?php
/**
 * PlanValidationException - a plan write refused by the owner's plan validation.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use InvalidArgumentException;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown by {@see Plans} when a `woocommerce_subscriptions_engine_validate_plan`
 * callback adds errors. Carries the collected errors with their codes and data;
 * the message joins the error messages.
 */
final class PlanValidationException extends InvalidArgumentException {

	/**
	 * Collected validation errors.
	 *
	 * @var WP_Error
	 */
	private $errors;

	/**
	 * Wrap the collected validation errors.
	 *
	 * @param WP_Error $errors Validation errors.
	 */
	public function __construct( WP_Error $errors ) {
		parent::__construct( esc_html( implode( ' ', $errors->get_error_messages() ) ) );

		$this->errors = $errors;
	}

	/**
	 * The validation errors, with their codes and data.
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}
}
