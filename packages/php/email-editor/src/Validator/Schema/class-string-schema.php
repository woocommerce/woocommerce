<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator\Schema;

use Automattic\WooCommerce\EmailEditor\Validator\Schema;

/**
 * Represents a schema for a string.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#strings
 */
class String_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'type' => 'string',
	);

	/**
	 * Set minimum length of the string.
	 *
	 * @param int $value Minimum length.
	 */
	public function minLength( int $value ): self {
		return $this->update_schema_property( 'minLength', $value );
	}

	/**
	 * Set maximum length of the string.
	 *
	 * @param int $value Maximum length.
	 */
	public function maxLength( int $value ): self {
		return $this->update_schema_property( 'maxLength', $value );
	}

	/**
	 * Parameter $pattern is a regular expression without leading/trailing delimiters.
	 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#pattern
	 *
	 * @param string $pattern Regular expression pattern.
	 */
	public function pattern( string $pattern ): self {
		$this->validate_pattern( $pattern );
		return $this->update_schema_property( 'pattern', $pattern );
	}

	/**
	 * Set the format of the string according to DateTime.
	 */
	public function formatDateTime(): self {
		return $this->update_schema_property( 'format', 'date-time' );
	}

	/**
	 * Set the format of the string according to email.
	 */
	public function formatEmail(): self {
		return $this->update_schema_property( 'format', 'email' );
	}

	/**
	 * Set the format of the string according to Hex color.
	 */
	public function formatHexColor(): self {
		return $this->update_schema_property( 'format', 'hex-color' );
	}

	/**
	 * Set the format of the string according to IP address.
	 */
	public function formatIp(): self {
		return $this->update_schema_property( 'format', 'ip' );
	}

	/**
	 * Set the format of the string according to uri.
	 */
	public function formatUri(): self {
		return $this->update_schema_property( 'format', 'uri' );
	}

	/**
	 * Set the format of the string according to uuid.
	 */
	public function formatUuid(): self {
		return $this->update_schema_property( 'format', 'uuid' );
	}
}
