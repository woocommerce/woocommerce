<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

/**
 * Settings handling for the GraphQL API.
 *
 * Registers the "GraphQL" section under WooCommerce - Settings - Advanced.
 * Only active when Main::is_enabled() returns true (feature flag on and
 * PHP 8.1+), so the section is hidden when the feature is disabled.
 */
class Settings {
	/**
	 * Identifier for the GraphQL section under the Advanced settings tab.
	 */
	public const SECTION_ID = 'graphql';

	/**
	 * Register the filter hooks that expose the GraphQL settings section.
	 */
	public function register(): void {
		add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_settings' ), 10, 2 );
	}

	/**
	 * Append the GraphQL section to the Advanced settings tab.
	 *
	 * @param array $sections Existing sections keyed by id.
	 * @return array
	 */
	public function add_section( array $sections ): array {
		$sections[ self::SECTION_ID ] = __( 'GraphQL', 'woocommerce' );
		return $sections;
	}

	/**
	 * Provide the settings fields for the GraphQL section.
	 *
	 * @param array  $settings   Existing settings for the current section.
	 * @param string $section_id Current section id.
	 * @return array
	 */
	public function add_settings( array $settings, string $section_id ): array {
		if ( self::SECTION_ID !== $section_id ) {
			return $settings;
		}

		return array(
			array(
				'title' => __( 'GraphQL', 'woocommerce' ),
				'desc'  => __( 'Configure the WooCommerce GraphQL API.', 'woocommerce' ),
				'type'  => 'title',
				'id'    => 'woocommerce_graphql_options',
			),
			array(
				'title'   => __( 'Enable GET endpoint', 'woocommerce' ),
				'desc'    => __( 'Allow GraphQL queries over GET in addition to POST', 'woocommerce' ),
				'id'      => Main::OPTION_GET_ENDPOINT_ENABLED,
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'woocommerce_graphql_options',
			),
		);
	}
}
