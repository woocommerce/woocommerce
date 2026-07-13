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

		// `done` includes dependencies that WordPress printed while resolving the queue.
		// Keep only queued handles so dependents identify scripts explicitly enqueued for the page.
		$printed_queued_scripts = array_intersect( $wp_scripts->queue, $wp_scripts->done );

		$handles    = array();
		$dependents = array();
		$sources    = array();

		foreach ( $printed_queued_scripts as $handle ) {
			$legacy_handles = $this->get_legacy_handles( $wp_scripts, $handle );

			if ( empty( $legacy_handles ) ) {
				continue;
			}

			$handles              += array_fill_keys( $legacy_handles, true );
			$dependents[ $handle ] = true;
			$source                = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ]->src : '';
			if ( is_string( $source ) && '' !== $source ) {
				$sources[] = $source;
			}
		}

		if ( empty( $handles ) ) {
			return array();
		}

		return array(
			'context'    => $context,
			'page_type'  => $this->get_current_page_type( $context ),
			'handles'    => implode( ',', array_keys( $handles ) ),
			'dependents' => implode( ',', array_keys( $dependents ) ),
			'sources'    => implode( ',', array_unique( $sources ) ),
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
	 * Get the request scope for rate limiting checks.
	 *
	 * @param string $context The request context.
	 * @return array<string, string>
	 */
	private function get_request_scope( string $context ): array {
		return array(
			'context'   => $context,
			'page_type' => $this->get_current_page_type( $context ),
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
