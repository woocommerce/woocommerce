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

defined( 'ABSPATH' ) || exit;

/**
 * Validation error thrown by Core checks, entities and value objects.
 *
 * The code is one of {@see ValidationErrorCode}, the field uses bracket notation
 * (`name`, `trial_duration[length]`, `items[2]`) and the params hold the values the
 * code's message needs. The message is an English developer message, for logs.
 */
final class InvalidInputException extends DomainException {

	/**
	 * Stable error code.
	 *
	 * @var string
	 */
	private $error_code;

	/**
	 * Field the error concerns, in bracket notation.
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
	 * Build the exception.
	 *
	 * @param string               $error_code Stable error code, one of {@see ValidationErrorCode}.
	 * @param string               $field      Field the error concerns, in bracket notation.
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
	 * Field the error concerns, in bracket notation.
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
}
