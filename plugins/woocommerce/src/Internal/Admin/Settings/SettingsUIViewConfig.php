<?php
/**
 * Settings UI View Config integration.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Provides layout-only View Config data for WooCommerce settings.
 *
 * @since 11.2.0
 */
final class SettingsUIViewConfig {

	/**
	 * WooCommerce View Config entity kind.
	 *
	 * @var string
	 */
	private const KIND = 'woocommerce-settings';

	/**
	 * Products settings page id.
	 *
	 * @var string
	 */
	private const PAGE_ID = 'products';

	/**
	 * Identity separator. It cannot occur in a sanitize_title() result.
	 *
	 * @var string
	 */
	private const IDENTITY_SEPARATOR = ':';

	/**
	 * Check whether the public WordPress View Config API is available.
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return function_exists( 'wp_get_entity_view_config_hook_name' ) && class_exists( '\WP_View_Config_Data' );
	}

	/**
	 * Get client metadata for a settings page and section.
	 *
	 * @since 11.2.0
	 *
	 * @param string $page_id Settings page id.
	 * @param string $section_key Section id, or "default" for the default section.
	 * @return array View Config support and identity metadata.
	 */
	public static function get_metadata( string $page_id, string $section_key ): array {
		if ( self::PAGE_ID !== $page_id || ! self::is_supported() ) {
			return array( 'supported' => false );
		}

		$name = self::get_name( $page_id, $section_key );
		if ( null === $name ) {
			return array( 'supported' => false );
		}

		return array(
			'supported' => true,
			'kind'      => self::KIND,
			'name'      => $name,
			'version'   => 1,
		);
	}

	/**
	 * Register the request-aware View Config filter loader.
	 *
	 * @since 11.2.0
	 */
	public static function register_rest_filter(): void {
		if ( ! self::is_supported() ) {
			return;
		}

		add_filter( 'rest_pre_dispatch', array( self::class, 'register_filter_for_request' ), 10, 3 );
	}

	/**
	 * Register the View Config filter for a Products request.
	 *
	 * @since 11.2.0
	 *
	 * @param mixed            $response Result to send to the client. Usually null.
	 * @param \WP_REST_Server  $server REST server instance.
	 * @param \WP_REST_Request $request Current REST request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return mixed Unchanged response.
	 */
	public static function register_filter_for_request( $response, \WP_REST_Server $server, \WP_REST_Request $request ) {
		if ( '/wp/v2/view-config' !== $request->get_route() ) {
			return $response;
		}

		$kind = $request->get_param( 'kind' );
		$name = $request->get_param( 'name' );
		if ( self::KIND !== $kind || ! is_string( $name ) || null === self::parse_name( $name ) ) {
			return $response;
		}

		// @phpstan-ignore-next-line function.notFound (WordPress 7.1 API is guarded by register_rest_filter().)
		add_filter( wp_get_entity_view_config_hook_name( self::KIND, $name ), array( self::class, 'provide_layout' ), 10, 2 );

		return $response;
	}

	/**
	 * Set the WooCommerce-owned form layout for a View Config request.
	 *
	 * @since 11.2.0
	 *
	 * @param mixed $data View Config data container.
	 * @param array $entity Requested entity identity.
	 * @return mixed Unchanged or updated View Config data container.
	 */
	public static function provide_layout( $data, array $entity ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
		// @phpstan-ignore-next-line class.notFound (WordPress 7.1 API is guarded by is_supported().)
		if ( ! self::is_supported() || ! $data instanceof \WP_View_Config_Data || ! current_user_can( 'manage_woocommerce' ) ) {
			return $data;
		}

		if ( self::KIND !== ( $entity['kind'] ?? null ) || ! is_string( $entity['name'] ?? null ) ) {
			return $data;
		}

		$identity = self::parse_name( $entity['name'] );
		if ( null === $identity ) {
			return $data;
		}

		if ( ! function_exists( 'woocommerce_settings_get_option' ) ) {
			if ( ! defined( 'WC_ABSPATH' ) ) {
				return $data;
			}

			require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
		}

		$context = SettingsUIRequestContext::for_identity( $identity['page'], $identity['section'] );
		$schema  = $context ? $context->get_schema() : null;
		if ( ! is_array( $schema ) ) {
			return $data;
		}

		// @phpstan-ignore-next-line class.notFound (WordPress 7.1 API is guarded by is_supported().)
		return $data->set(
			array( 'form' => SettingsUISchema::get_dataform_layout( $schema ) ),
			// @phpstan-ignore-next-line class.notFound (WordPress 7.1 API is guarded by is_supported().)
			\WP_View_Config_Data::LATEST_VERSION
		);
	}

	/**
	 * Build a canonical page-and-section entity name.
	 *
	 * @param string $page_id Settings page id.
	 * @param string $section_key Section id, or "default" for the default section.
	 * @return string|null Canonical name, or null for invalid input.
	 */
	private static function get_name( string $page_id, string $section_key ): ?string {
		if ( self::PAGE_ID !== $page_id || ! self::is_normalized_part( $section_key ) ) {
			return null;
		}

		return $page_id . self::IDENTITY_SEPARATOR . $section_key;
	}

	/**
	 * Parse a canonical page-and-section entity name.
	 *
	 * @param string $name Entity name.
	 * @return array{page: string, section: string}|null Parsed identity, or null when malformed.
	 */
	private static function parse_name( string $name ): ?array {
		$parts = explode( self::IDENTITY_SEPARATOR, $name );
		if ( 2 !== count( $parts ) || self::PAGE_ID !== $parts[0] || ! self::is_normalized_part( $parts[1] ) ) {
			return null;
		}

		return array(
			'page'    => $parts[0],
			'section' => $parts[1],
		);
	}

	/**
	 * Check whether an identity part is already in its canonical form.
	 *
	 * @param string $part Identity part.
	 * @return bool
	 */
	private static function is_normalized_part( string $part ): bool {
		return '' !== $part && sanitize_title( $part ) === $part;
	}
}
