<?php
/**
 * Changelog_Entry class
 *
 * @package WooCommerce\ExtensionChangelogger
 */

namespace Automattic\WooCommerce\ExtensionTools\Changelogger;

use Automattic\Jetpack\Changelog\ChangelogEntry;

/**
 * A changelog entry that remembers the date string it was parsed from.
 *
 * Preserve the date text in existing headings when a release rewrites the changelog.
 * Entries created by `changelogger write` have no original text and use the configured format.
 */
class Changelog_Entry extends ChangelogEntry {

	/**
	 * Date string this entry was parsed from, if any.
	 *
	 * @var string|null
	 */
	protected $raw_timestamp = null;

	/**
	 * Get the raw date string.
	 *
	 * @return string|null
	 */
	public function getRawTimestamp() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the camelCase API of the parent class.
		return $this->raw_timestamp;
	}

	/**
	 * Set the raw date string.
	 *
	 * @param string|null $timestamp Date string as it appeared in the changelog, or null to format the parsed timestamp instead.
	 * @return $this
	 */
	public function setRawTimestamp( $timestamp ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the camelCase API of the parent class.
		$this->raw_timestamp = null === $timestamp ? null : (string) $timestamp;
		return $this;
	}
}
