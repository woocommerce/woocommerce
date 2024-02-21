<?php

namespace Automattic\WooCommerce\RemoteSpecsValidation;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\ValidationResult;

/**
 * The result of a remote spec validation.
 */
class RemoteSpecValidationResult {
	/**
	 * @var ValidationResult
	 */
	private $result;

	public function __construct(ValidationResult $result) {
		$this->result = $result;
	}

	public function is_valid() {
		return $this->result->isValid();
	}

	public function get_result() {
	    return $this->result;
	}

	public function get_errors($formatter = null) {
		if ( !$this->result->isValid() ) {
			if ( $formatter ) {
				return $formatter->format( $this->result->error() );
			}

			return ( new ErrorFormatter() )->format( $this->result->error() );
		}
		return null;
	}
}
