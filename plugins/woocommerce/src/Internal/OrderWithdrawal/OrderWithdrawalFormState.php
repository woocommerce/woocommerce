<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

/**
 * Holds the processed order withdrawal form state.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalFormState {

	/**
	 * Current screen.
	 *
	 * @var string
	 */
	public string $screen;

	/**
	 * Sanitized form data.
	 *
	 * @var array<string,string>
	 */
	public array $data;

	/**
	 * Validation errors keyed by field.
	 *
	 * @var array<string,string>
	 */
	public array $errors;

	/**
	 * Initialize form state.
	 *
	 * @param string               $screen Current screen.
	 * @param array<string,string> $data Sanitized form data.
	 * @param array<string,string> $errors Validation errors keyed by field.
	 *
	 * @since 11.1.0
	 */
	public function __construct( string $screen, array $data, array $errors ) {
		$this->screen = $screen;
		$this->data   = $data;
		$this->errors = $errors;
	}
}
