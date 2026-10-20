<?php
/**
 * ValidationException - a write refused because its input is not valid.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use InvalidArgumentException;
use Throwable;
use WP_Error;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown by the facades when they refuse input: an engine check (built with
 * {@see self::from_core()}) or a `woocommerce_subscriptions_engine_validate_plan`
 * callback. Callers read {@see self::get_errors()}: each error's code (a
 * {@see ValidationErrorCode} or the extension's own), its translated message, and
 * its data, with `field` (bracket path) and `params` for engine errors.
 * `getMessage()` stays English, for logs.
 */
final class ValidationException extends InvalidArgumentException {

	/**
	 * Collected validation errors.
	 *
	 * @var WP_Error
	 */
	private $errors;

	/**
	 * Wrap the collected validation errors.
	 *
	 * @param WP_Error       $errors   Validation errors.
	 * @param string         $message  Escaped exception message; empty joins the error messages.
	 * @param Throwable|null $previous The exception that caused the refusal.
	 */
	public function __construct( WP_Error $errors, string $message = '', ?Throwable $previous = null ) {
		if ( '' === $message ) {
			$message = esc_html( implode( ' ', $errors->get_error_messages() ) );
		}

		parent::__construct( $message, 0, $previous );

		$this->errors = $errors;
	}

	/**
	 * Convert a Core validation error into one translated error with the engine code,
	 * `field` and `params` data. The Core exception is chained as the previous one.
	 *
	 * @param InvalidInputException $e Core validation error.
	 */
	public static function from_core( InvalidInputException $e ): self {
		$field  = $e->get_field();
		$params = $e->get_params();
		$errors = new WP_Error(
			$e->get_error_code(),
			self::get_translated_message( $e->get_error_code(), $field, $params ),
			array(
				'field'  => $field,
				'params' => $params,
			)
		);

		return new self( $errors, esc_html( $e->getMessage() ), $e );
	}

	/**
	 * The validation errors, with their codes and data.
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Translated message for an error code. An unknown code, or params a message cannot
	 * use, get the generic message, so a code a third party constructs never fails here.
	 *
	 * @param string               $code   Error code.
	 * @param string               $field  Field, in bracket notation.
	 * @param array<string, mixed> $params Message values.
	 */
	private static function get_translated_message( string $code, string $field, array $params ): string {
		$min = isset( $params['min'] ) && is_int( $params['min'] ) ? $params['min'] : null;
		$max = isset( $params['max'] ) && is_int( $params['max'] ) ? $params['max'] : null;

		switch ( $code ) {
			case ValidationErrorCode::REQUIRED:
				/* translators: %s: field name. */
				return sprintf( __( '%s is required.', 'woocommerce' ), $field );
			case ValidationErrorCode::INVALID_TYPE:
				if ( isset( $params['type'] ) && is_string( $params['type'] ) ) {
					/* translators: 1: field name, 2: expected type, such as string or integer. */
					return sprintf( __( '%1$s is not of type %2$s.', 'woocommerce' ), $field, $params['type'] );
				}
				break;
			case ValidationErrorCode::OUT_OF_BOUNDS:
				if ( null !== $min && null !== $max ) {
					/* translators: 1: field name, 2: lowest allowed number, 3: highest allowed number. */
					return sprintf( __( '%1$s must be between %2$d and %3$d.', 'woocommerce' ), $field, $min, $max );
				}
				if ( null !== $min ) {
					/* translators: 1: field name, 2: lowest allowed number. */
					return sprintf( __( '%1$s must be greater than or equal to %2$d.', 'woocommerce' ), $field, $min );
				}
				if ( null !== $max ) {
					/* translators: 1: field name, 2: highest allowed number. */
					return sprintf( __( '%1$s must be less than or equal to %2$d.', 'woocommerce' ), $field, $max );
				}
				break;
			case ValidationErrorCode::NOT_IN_ENUM:
				if ( isset( $params['allowed'] ) && is_array( $params['allowed'] ) ) {
					/* translators: 1: field name, 2: comma-separated list of allowed values. */
					return sprintf( __( '%1$s is not one of %2$s.', 'woocommerce' ), $field, implode( ', ', array_map( 'strval', $params['allowed'] ) ) );
				}
				break;
			case ValidationErrorCode::NOT_REGISTERED:
				if ( isset( $params['value'] ) && is_scalar( $params['value'] ) ) {
					/* translators: 1: field name, 2: the status that is not registered. */
					return sprintf( __( '%1$s "%2$s" is not registered.', 'woocommerce' ), $field, (string) $params['value'] );
				}
				break;
		}

		/* translators: %s: field name. */
		return sprintf( __( '%s is not valid.', 'woocommerce' ), $field );
	}
}
