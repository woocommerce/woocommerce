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
	 * Set the timestamp and discard any previously parsed heading text.
	 *
	 * @param \DateTime|string|null $timestamp Timestamp to set.
	 * @return $this
	 */
	public function setTimestamp( $timestamp ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the API of the parent class.
		parent::setTimestamp( $timestamp );
		$this->raw_timestamp = null;
		return $this;
	}

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
