<?php
/**
 * Settings UI schema builder.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter -- Deprecated no-op methods keep their signatures.

/**
 * Builds the canonical settings schema consumed by the settings UI renderer.
 *
 * Kept with its signatures because the public SettingsUISchema extends it.
 *
 * @since 10.9.0
 * @deprecated 11.4.0 The Settings UI was removed. Settings pages use the classic renderer.
 */
class SettingsUISchema {

	/**
	 * Build a schema from a legacy WC settings array.
	 *
	 * @since 10.9.0
	 * @deprecated 11.4.0 Returns an empty schema.
	 *
	 * @param string $page_id Settings page id.
	 * @param string $section Section id. Empty string means the default section.
	 * @param string $title Page title.
	 * @param array  $settings Legacy settings definitions.
	 * @param string $default_save_adapter Default save adapter.
	 * @return array
	 */
	public static function from_legacy_settings( string $page_id, string $section, string $title, array $settings, string $default_save_adapter = 'form_post' ): array {
		SettingsUIDeprecation::record_usage( __METHOD__ );
		return array();
	}

	/**
	 * Assert that a schema can safely cross the PHP-to-JavaScript boundary.
	 *
	 * @since 11.2.0
	 * @deprecated 11.4.0 Does nothing.
	 *
	 * @param array $schema Settings UI schema.
	 */
	public static function assert_valid_schema( array $schema ): void {
		SettingsUIDeprecation::record_usage( __METHOD__ );
	}

	/**
	 * Canonicalize option values supplied by native Settings UI schema providers.
	 *
	 * @since 11.0.0
	 * @deprecated 11.4.0 Returns the schema unchanged.
	 *
	 * @param array $schema Settings UI schema.
	 * @return array
	 */
	public static function canonicalize_option_values( array $schema ): array {
		SettingsUIDeprecation::record_usage( __METHOD__ );
		return $schema;
	}

	/**
	 * Canonicalize typed field values and compatibility metadata.
	 *
	 * @since 11.2.0
	 * @deprecated 11.4.0 Returns the schema unchanged.
	 *
	 * @param array $schema Settings UI schema.
	 * @return array
	 */
	public static function canonicalize_schema_values( array $schema ): array {
		SettingsUIDeprecation::record_usage( __METHOD__ );
		return $schema;
	}
}
