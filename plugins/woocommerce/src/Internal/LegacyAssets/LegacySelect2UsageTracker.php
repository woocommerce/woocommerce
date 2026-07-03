<?php
/**
 * Tracks usage of WooCommerce's bundled legacy Select2 handles.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\LegacyAssets;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Scripts;

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
		add_action( 'admin_print_footer_scripts', array( $this, 'handle_admin_print_footer_scripts' ), PHP_INT_MAX );
		add_action( 'wp_print_footer_scripts', array( $this, 'handle_wp_print_footer_scripts' ), PHP_INT_MAX );
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
		$scope = $this->get_request_scope( $context );

		if ( $this->was_recently_checked( $scope ) ) {
			return;
		}

		$event = $this->get_usage_event( $context );
		$this->mark_recently_checked( $scope );

		if ( empty( $event ) ) {
			return;
		}

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

		$loaded_scripts = array_intersect( $wp_scripts->queue, $wp_scripts->done );

		$handles    = array();
		$dependents = array();

		foreach ( $loaded_scripts as $handle ) {
			$legacy_handles = $this->get_legacy_handles( $wp_scripts, $handle );

			if ( empty( $legacy_handles ) ) {
				continue;
			}

			$handles              += array_fill_keys( $legacy_handles, true );
			$dependents[ $handle ] = true;
		}

		if ( empty( $handles ) ) {
			return array();
		}

		return array(
			'context'    => $context,
			'screen_id'  => self::CONTEXT_ADMIN === $context ? $this->get_current_screen_id() : '',
			'handles'    => implode( ',', array_keys( $handles ) ),
			'dependents' => implode( ',', array_keys( $dependents ) ),
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
			$this->load_tracks();
		}

		if ( class_exists( 'WC_Tracks' ) ) {
			\WC_Tracks::record_event( $event_name, $properties );
		}
	}

	/**
	 * Whether this request scope was already checked recently.
	 *
	 * @param array<string, string> $scope Request scope.
	 * @return bool
	 */
	private function was_recently_checked( array $scope ): bool {
		return false !== get_transient( $this->get_transient_key( $scope ) );
	}

	/**
	 * Mark this request scope as recently checked.
	 *
	 * @param array<string, string> $scope Request scope.
	 * @return void
	 */
	private function mark_recently_checked( array $scope ): void {
		set_transient( $this->get_transient_key( $scope ), 'yes', WEEK_IN_SECONDS );
	}

	/**
	 * Get the request scope for rate limiting checks.
	 *
	 * @param string $context The request context.
	 * @return array<string, string>
	 */
	private function get_request_scope( string $context ): array {
		return array(
			'context'   => $context,
			'screen_id' => self::CONTEXT_ADMIN === $context ? $this->get_current_screen_id() : '',
			'path'      => self::CONTEXT_FRONTEND === $context ? $this->get_current_request_path() : '',
		);
	}

	/**
	 * Get the transient key for a request scope.
	 *
	 * @param array<string, string> $scope Request scope.
	 * @return string
	 */
	private function get_transient_key( array $scope ): string {
		ksort( $scope );

		$scope_json = wp_json_encode( $scope );

		return self::TRANSIENT_KEY_PREFIX . md5( is_string( $scope_json ) ? $scope_json : '' );
	}

	/**
	 * Load Tracks classes when they have not been loaded yet.
	 *
	 * @return void
	 */
	private function load_tracks(): void {
		if ( ! defined( 'WC_ABSPATH' ) ) {
			return;
		}

		$tracks_files = array(
			'includes/tracks/class-wc-tracks.php',
			'includes/tracks/class-wc-tracks-event.php',
			'includes/tracks/class-wc-tracks-client.php',
			'includes/tracks/class-wc-tracks-footer-pixel.php',
			'includes/tracks/class-wc-site-tracking.php',
		);

		foreach ( $tracks_files as $tracks_file ) {
			$tracks_file_path = WC_ABSPATH . $tracks_file;

			if ( ! file_exists( $tracks_file_path ) ) {
				return;
			}

			include_once $tracks_file_path;
		}
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
	 * Get the current request path.
	 *
	 * @return string
	 */
	private function get_current_request_path(): string {
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		return is_string( $path ) ? $path : '';
	}
}
