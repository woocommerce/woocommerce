<?php
/**
 * InvalidInputException - a refused input value, reported as a stable error code,
 * the field it concerns and the values a message needs. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

use DomainException;
use LogicException;

defined( 'ABSPATH' ) || exit;

/**
 * Validation error thrown by Core checks, entities and value objects.
 *
 * Carries one or more errors. Each has a code (one of {@see ValidationErrorCode}), a
 * field as a dot path (`name`, `trial_duration.length`, `items.2`) and the params the
 * code's message needs; {@see self::get_errors()} lists them, and the getters return the
 * first one's. The message is an English developer message, for logs.
 */
final class InvalidInputException extends DomainException {

	/**
	 * Stable error code.
	 *
	 * @var string
	 */
	private $error_code;

	/**
	 * Field the error concerns, in dot notation.
	 *
	 * @var string
	 */
	private $field;

	/**
	 * Values the code's message needs, keyed as documented on the code.
	 *
	 * @var array<string, mixed>
	 */
	private $params;

	/**
	 * Every single error, when built from several; empty for a single error.
	 *
	 * @var array<int, InvalidInputException>
	 */
	private $errors = array();

	/**
	 * Build the exception.
	 *
	 * @param string               $error_code Stable error code, one of {@see ValidationErrorCode}.
	 * @param string               $field      Field the error concerns, in dot notation.
	 * @param string               $message    English developer message.
	 * @param array<string, mixed> $params     Values the code's message needs.
	 */
	public function __construct( string $error_code, string $field, string $message, array $params = array() ) {
		parent::__construct( $message );
		$this->error_code = $error_code;
		$this->field      = $field;
		$this->params     = $params;
	}

	/**
	 * Stable error code, one of {@see ValidationErrorCode}.
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Field the error concerns, in dot notation.
	 */
	public function get_field(): string {
		return $this->field;
	}

	/**
	 * Values the code's message needs.
	 *
	 * @return array<string, mixed>
	 */
	public function get_params(): array {
		return $this->params;
	}

	/**
	 * Combine errors into one exception. Each element's errors are flattened in, in order;
	 * a single resulting error is returned as is. Several keep the first one's code, field
	 * and params, and join the English messages.
	 *
	 * @param array<int, InvalidInputException> $errors Errors, at least one.
	 * @phpstan-param non-empty-list<InvalidInputException> $errors
	 * @throws LogicException If `$errors` is empty (an engine bug, not input).
	 */
	public static function from_errors( array $errors ): self {
		$flattened = array();
		foreach ( $errors as $error ) {
			foreach ( $error->get_errors() as $single ) {
				$flattened[] = $single;
			}
		}

		if ( array() === $flattened ) {
			throw new LogicException( 'InvalidInputException::from_errors() needs at least one error.' );
		}
		if ( 1 === count( $flattened ) ) {
			return $flattened[0];
		}

		$messages = array();
		foreach ( $flattened as $single ) {
			$messages[] = $single->getMessage();
		}

		$first            = $flattened[0];
		$combined         = new self( $first->get_error_code(), $first->get_field(), implode( ' ', $messages ), $first->get_params() );
		$combined->errors = $flattened;

		return $combined;
	}

	/**
	 * Every single error, in order: this exception itself when it carries one.
	 *
	 * @return array<int, InvalidInputException>
	 * @phpstan-return non-empty-list<InvalidInputException>
	 */
	public function get_errors(): array {
		return array() === $this->errors ? array( $this ) : $this->errors;
	}
}
