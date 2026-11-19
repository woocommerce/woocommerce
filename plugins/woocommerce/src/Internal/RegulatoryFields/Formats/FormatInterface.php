<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields\Formats;

/**
 * How the value of a regulatory field is cleaned, checked and displayed.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
interface FormatInterface {

	/**
	 * Convert what the shopper typed into the form that is validated and stored.
	 *
	 * @param string $value Value as typed by the shopper.
	 * @return string
	 */
	public function normalize( string $value ): string;

	/**
	 * Check a normalized value.
	 *
	 * @param string $value Normalized value.
	 * @return bool
	 */
	public function is_valid( string $value ): bool;

	/**
	 * Convert a normalized value into the form shown to people.
	 *
	 * @param string $value Normalized value.
	 * @return string
	 */
	public function format( string $value ): string;

	/**
	 * Message shown when the value is not valid.
	 *
	 * @return string
	 */
	public function get_invalid_message(): string;
}
