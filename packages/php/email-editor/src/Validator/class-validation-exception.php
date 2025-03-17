<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator;

use Automattic\WooCommerce\EmailEditor\UnexpectedValueException;
use WP_Error;

/**
 * Exception thrown when validation fails.
 */
class Validation_Exception extends UnexpectedValueException {
	/**
	 * WP_Error instance.
	 *
	 * @var WP_Error
	 */
	protected $wp_error;

	/**
	 * Creates a new instance of the exception.
	 *
	 * @param WP_Error $wp_error WP_Error instance.
	 */
	public static function create_from_wp_error( WP_Error $wp_error ): self {
		$exception           = self::create()
		->withMessage( $wp_error->get_error_message() );
		$exception->wp_error = $wp_error;
		return $exception;
	}

	/**
	 * Returns the WP_Error instance.
	 */
	public function get_wp_error(): WP_Error {
		return $this->wp_error;
	}
}
