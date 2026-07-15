<?php
/**
 * Tracks usage of WooCommerce's bundled legacy Select2 handles.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\LegacyAssets;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Scripts;
use WC_Site_Tracking;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks extensions that still depend on the legacy Select2 handles bundled by WooCommerce.
 */
class LegacySelect2UsageTracker implements RegisterHooksInterface {

	public const EVENT_NAME = 'legacy_select2_usage_detected';

	private const CONTEXT_ADMIN    = 'admin';
	private const CONTEXT_FRONTEND = 'frontend';

	private const LEGACY_HANDLES = array(
		'select2',
		'wc-select2',
	);

	private const TRANSIENT_KEY_PREFIX = 'wc_legacy_select2_check_';

	/**
	 * Register hook callbacks.
	 *
	 * @return void
	 *
	 * @since 11.0.0
	 */
	public function register() {
		if ( WC_Site_Tracking::is_tracking_enabled() ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'handle_admin_print_footer_scripts' ), PHP_INT_MAX );
			add_action( 'wp_print_footer_scripts', array( $this, 'handle_wp_print_footer_scripts' ), PHP_INT_MAX );
		}
	}

	/**
	 * Handle the admin_print_footer_scripts hook.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_admin_print_footer_scripts(): void {
		$this->track_usage( self::CONTEXT_ADMIN );
	}

	/**
	 * Handle the wp_print_footer_scripts hook.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_wp_print_footer_scripts(): void {
		$this->track_usage( self::CONTEXT_FRONTEND );
	}

	/**
	 * Build and record legacy Select2 usage events for the current request.
	 *
	 * @param string $context The request context.
	 * @return void
	 */
	private function track_usage( string $context ): void {
		if ( ! $this->is_legacy_select2_printed() ) {
			return;
		}

		$event = $this->get_usage_event( $context );
		if ( empty( $event ) ) {
			return;
		}

		if ( $this->was_recently_checked( $event ) ) {
			return;
		}

		$this->mark_recently_checked( $event );
		$this->record_event( self::EVENT_NAME, $event );
	}

	/**
	 * Get a legacy Select2 usage event for the current script registry.
	 *
	 * @internal
	 *
	 * @param string $context The request context.
	 * @return array<string, string>
	 */
	public function get_usage_event( string $context ): array {
		$wp_scripts = wp_scripts();
		if ( ! $wp_scripts instanceof WP_Scripts ) {
			return array();
		}

		// `done` includes dependencies that WordPress printed while resolving the queue.
		// Keep only queued handles so dependents identify scripts explicitly enqueued for the page.
		$printed_queued_scripts = array_intersect( $wp_scripts->queue, $wp_scripts->done );

		$handles            = array();
		$dependents         = array();
		$dependents_sources = array();
		$plugins_path       = wp_parse_url( plugins_url( '/' ), PHP_URL_PATH );

		foreach ( $printed_queued_scripts as $handle ) {
			$legacy_handles = $this->get_legacy_handles( $wp_scripts, $handle );

			if ( empty( $legacy_handles ) ) {
				continue;
			}

			$handles              += array_fill_keys( $legacy_handles, true );
			$dependents[ $handle ] = true;
			$source                = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ]->src : '';
			$source                = self::get_plugin_relative_source( $source, $plugins_path );

			if ( '' !== $source ) {
				$dependents_sources[] = $source;
			}
		}

		if ( empty( $handles ) ) {
			return array();
		}

		$handles            = array_keys( $handles );
		$dependents         = array_keys( $dependents );
		$dependents_sources = array_unique( $dependents_sources );
		$handles_sources    = array();

		foreach ( $handles as $handle ) {
			$source = self::get_legacy_handle_source( $wp_scripts, $handle );
			$source = self::get_plugin_relative_source( $source, $plugins_path );

			if ( '' !== $source ) {
				$handles_sources[] = $source;
			}
		}

		$handles_sources = array_unique( $handles_sources );

		sort( $handles );
		sort( $dependents );
		sort( $dependents_sources );
		sort( $handles_sources );

		return array(
			'context'            => $context,
			'page_type'          => $this->get_current_page_type( $context ),
			'handles'            => implode( ',', $handles ),
			'dependents'         => implode( ',', $dependents ),
			'dependents_sources' => implode( ',', $dependents_sources ),
			'handles_sources'    => implode( ',', $handles_sources ),
		);
	}

	/**
	 * Record a Tracks event.
	 *
	 * @param string                $event_name Event name.
	 * @param array<string, string> $properties Event properties.
	 * @return void
	 *
	 * @since 11.0.0
	 */
	protected function record_event( string $event_name, array $properties ): void {
		if ( ! class_exists( 'WC_Tracks' ) ) {
			return;
		}

		\WC_Tracks::record_event( $event_name, $properties );
	}

	/**
	 * Whether this usage event was already checked recently.
	 *
	 * @param array<string, string> $event Usage event.
	 * @return bool
	 */
	private function was_recently_checked( array $event ): bool {
		return false !== get_transient( $this->get_transient_key( $event ) );
	}

	/**
	 * Mark this usage event as recently checked.
	 *
	 * @param array<string, string> $event Usage event.
	 * @return void
	 */
	private function mark_recently_checked( array $event ): void {
		set_transient( $this->get_transient_key( $event ), 'yes', WEEK_IN_SECONDS );
	}

	/**
	 * Check whether a legacy Select2 handle has been printed.
	 *
	 * @return bool
	 */
	private function is_legacy_select2_printed(): bool {
		foreach ( self::LEGACY_HANDLES as $handle ) {
			if ( wp_script_is( $handle, 'done' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the transient key for a usage event.
	 *
	 * @param array<string, string> $event Usage event.
	 * @return string
	 */
	private function get_transient_key( array $event ): string {
		ksort( $event );

		$event_json = wp_json_encode( $event );

		return self::TRANSIENT_KEY_PREFIX . md5( is_string( $event_json ) ? $event_json : '' );
	}

	/**
	 * Get legacy Select2 handles for a printed top-level handle.
	 *
	 * @param WP_Scripts $wp_scripts WordPress scripts registry.
	 * @param string     $handle     Script handle.
	 * @return array<int, string>
	 */
	private function get_legacy_handles( WP_Scripts $wp_scripts, string $handle ): array {
		$legacy_handles = array();

		if ( in_array( $handle, self::LEGACY_HANDLES, true ) ) {
			$legacy_handles[ $handle ] = true;
			return array_keys( $legacy_handles );
		}

		if ( isset( $wp_scripts->registered[ $handle ] ) ) {
			foreach ( $wp_scripts->registered[ $handle ]->deps as $dependency_handle ) {
				if ( in_array( $dependency_handle, self::LEGACY_HANDLES, true ) ) {
					$legacy_handles[ $dependency_handle ] = true;
				}
			}
		}

		return array_keys( $legacy_handles );
	}

	/**
	 * Get a script source relative to the plugins directory.
	 *
	 * @param string|false      $source       Script source.
	 * @param string|false|null $plugins_path Plugins directory URL path.
	 * @return string
	 */
	private static function get_plugin_relative_source( $source, $plugins_path ): string {
		if ( ! is_string( $source ) || '' === $source || ! is_string( $plugins_path ) || '' === $plugins_path ) {
			return '';
		}

		$source_path = wp_parse_url( $source, PHP_URL_PATH );
		if ( ! is_string( $source_path ) || ! str_starts_with( $source_path, $plugins_path ) ) {
			return '';
		}

		return ltrim( substr( $source_path, strlen( $plugins_path ) ), '/' );
	}

	/**
	 * Get the source for a legacy Select2 handle.
	 *
	 * @param WP_Scripts $wp_scripts WordPress scripts registry.
	 * @param string     $handle     Script handle.
	 * @return string
	 */
	private static function get_legacy_handle_source( WP_Scripts $wp_scripts, string $handle ): string {
		$source_handle     = 'select2' === $handle ? 'wc-select2' : $handle;
		$registered_script = $wp_scripts->registered[ $source_handle ] ?? null;

		if ( null === $registered_script ) {
			return '';
		}

		return is_string( $registered_script->src ) ? $registered_script->src : '';
	}

	/**
	 * Get the current admin screen ID.
	 *
	 * @return string
	 */
	private function get_current_screen_id(): string {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return '';
		}

		$screen = get_current_screen();
		return $screen ? (string) $screen->id : '';
	}

	/**
	 * Get the current page type.
	 *
	 * @param string $context The request context.
	 * @return string
	 */
	private function get_current_page_type( string $context ): string {
		if ( self::CONTEXT_ADMIN === $context ) {
			return $this->get_current_screen_id();
		}

		if ( self::CONTEXT_FRONTEND === $context ) {
			return $this->get_current_frontend_page_type();
		}

		return '';
	}

	/**
	 * Get the current frontend page type.
	 *
	 * @return string
	 */
	private function get_current_frontend_page_type(): string {
		if ( is_cart() ) {
			return 'cart';
		}

		if ( is_checkout() ) {
			return 'checkout';
		}

		if ( is_account_page() ) {
			return 'my_account';
		}

		if ( is_shop() ) {
			return 'shop';
		}

		if ( is_product() ) {
			return 'product';
		}

		if ( is_product_category() ) {
			return 'product_category';
		}

		if ( is_product_tag() ) {
			return 'product_tag';
		}

		if ( is_product_taxonomy() ) {
			return 'product_taxonomy';
		}

		if ( is_front_page() ) {
			return 'front_page';
		}

		if ( is_home() ) {
			return 'home';
		}

		if ( is_search() ) {
			return 'search';
		}

		if ( is_archive() ) {
			return 'archive';
		}

		if ( is_singular() ) {
			return 'singular';
		}

		return 'other';
	}
}
