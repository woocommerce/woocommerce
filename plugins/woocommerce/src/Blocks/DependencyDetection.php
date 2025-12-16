<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks;

/**
 * DependencyDetection class.
 *
 * Provides runtime detection of extensions that use Blocks related WooCommerce globals
 * (window.wc.*) without properly declaring their PHP script dependencies.
 *
 * This runs by default to warn developers about missing dependencies.
 *
 * @since 10.5.0
 * @internal
 */
final class DependencyDetection {

	/**
	 * Maps window.wc.* property names to their required script handles.
	 *
	 * This is the source of truth for both PHP and JS dependency detection.
	 * Based on wcDepMap and wcHandleMap in client/blocks/bin/webpack-helpers.js.
	 *
	 * @var array<string, string>
	 */
	private const WC_GLOBAL_EXPORTS = array(
		'wcBlocksRegistry'      => 'wc-blocks-registry',
		'wcSettings'            => 'wc-settings',
		'wcBlocksData'          => 'wc-blocks-data-store',
		'data'                  => 'wc-store-data',
		'wcBlocksSharedContext' => 'wc-blocks-shared-context',
		'wcBlocksSharedHocs'    => 'wc-blocks-shared-hocs',
		'priceFormat'           => 'wc-price-format',
		'blocksCheckout'        => 'wc-blocks-checkout',
		'blocksCheckoutEvents'  => 'wc-blocks-checkout-events',
		'blocksComponents'      => 'wc-blocks-components',
		'wcTypes'               => 'wc-types',
		'sanitize'              => 'wc-sanitize',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		// Build registry late (after all scripts registered) but output the inline script early.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_detection_script' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_detection_script' ), 999 );

		// Output an early inline script to set up the Proxy before any other scripts run.
		add_action( 'wp_head', array( $this, 'output_early_proxy_setup' ), 1 );
		add_action( 'admin_head', array( $this, 'output_early_proxy_setup' ), 1 );
	}

	/**
	 * Output early inline script to set up the Proxy on window.wc.
	 *
	 * This must run before any WooCommerce scripts to intercept access.
	 * The script is loaded from a separate file for better IDE support and testing,
	 * but output inline to ensure correct timing (before any enqueued scripts).
	 */
	public function output_early_proxy_setup(): void {
		$script_path = __DIR__ . '/Assets/js/dependency-detection.js';

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read for inline script output.
		$script_content = file_get_contents( $script_path );

		if ( ! $script_content ) {
			return;
		}

		// Inject the global-to-handle mapping from PHP (source of truth).
		$mapping_json   = \wp_json_encode( self::WC_GLOBAL_EXPORTS );
		$script_content = str_replace(
			'__WC_GLOBAL_EXPORTS_PLACEHOLDER__',
			$mapping_json,
			$script_content
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Script content is from a trusted local file, JSON is safely encoded.
		echo '<script id="wc-dependency-detection">' . $script_content . '</script>' . "\n";
	}

	/**
	 * Enqueue the dependency detection script with the script registry data.
	 */
	public function enqueue_detection_script(): void {
		// Build the registry at wp_print_footer_scripts when all scripts (including integration scripts) are registered.
		\add_action(
			'wp_print_footer_scripts',
			function () {
				// Build the script registry mapping URLs to handles and dependencies.
				$script_registry = $this->build_script_registry();
				$registry_json   = wp_json_encode( $script_registry );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safely encoded by wp_json_encode.
				echo '<script id="wc-dependency-detection-registry">if(typeof window.__wcUpdateDependencyRegistry==="function"){window.__wcUpdateDependencyRegistry(' . $registry_json . ');}</script>' . "\n";
			},
			1
		);
	}

	/**
	 * Build a registry of all enqueued scripts with their URLs and dependencies.
	 *
	 * @return array<string, array{handle: string, deps: array<string>}>
	 */
	private function build_script_registry(): array {
		$wp_scripts = wp_scripts();
		$registry   = array();

		foreach ( $wp_scripts->registered as $handle => $script ) {
			// Skip scripts without a source URL.
			if ( empty( $script->src ) ) {
				continue;
			}

			// Get the full URL.
			$src = $script->src;
			if ( ! preg_match( '|^(https?:)?//|', $src ) ) {
				// Relative URL - make it absolute.
				$src = $wp_scripts->base_url . $src;
			}

			// Skip WooCommerce's own scripts - we don't need to check those.
			if ( $this->is_woocommerce_script( $src ) ) {
				continue;
			}

			// Normalize the URL for consistent matching.
			$src = $this->normalize_url( $src );

			$registry[ $src ] = array(
				'handle' => $handle,
				'deps'   => $this->get_all_dependencies( $script->deps ),
			);
		}

		return $registry;
	}

	/**
	 * Check if a script URL belongs to WooCommerce core.
	 *
	 * Checks if the script is loaded from the WooCommerce core plugin directory,
	 * not from third-party extensions that may use similar handle naming.
	 *
	 * @param string $url Script URL.
	 * @return bool
	 */
	private function is_woocommerce_script( string $url ): bool {
		// Check if the URL is from the WooCommerce core plugin directory.
		// This matches /plugins/woocommerce/ but not /plugins/woocommerce-subscriptions/ etc.
		return (bool) preg_match( '#/plugins/woocommerce/(client|assets|build)/#', $url );
	}

	/**
	 * Recursively get all dependencies including nested ones.
	 *
	 * @param array<string> $deps Direct dependencies.
	 * @return array<string> All dependencies (flattened).
	 */
	private function get_all_dependencies( array $deps ): array {
		$wp_scripts      = wp_scripts();
		$all_deps        = array();
		$deps_to_process = $deps;

		while ( ! empty( $deps_to_process ) ) {
			$handle = array_shift( $deps_to_process );

			if ( in_array( $handle, $all_deps, true ) ) {
				continue;
			}

			$all_deps[] = $handle;

			// Add nested dependencies to process.
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				foreach ( $wp_scripts->registered[ $handle ]->deps as $nested_dep ) {
					if ( ! in_array( $nested_dep, $all_deps, true ) ) {
						$deps_to_process[] = $nested_dep;
					}
				}
			}
		}

		// Filter to only include WooCommerce handles we care about.
		$wc_handles = array_values( self::WC_GLOBAL_EXPORTS );
		return array_values(
			array_filter(
				$all_deps,
				function ( $dep ) use ( $wc_handles ) {
					return in_array( $dep, $wc_handles, true );
				}
			)
		);
	}

	/**
	 * Normalize a URL by removing version query strings.
	 *
	 * This helps match URLs in stack traces which may have different version strings.
	 *
	 * @param string $url URL to normalize.
	 * @return string Normalized URL.
	 */
	private function normalize_url( string $url ): string {
		// Parse the URL.
		$parsed = wp_parse_url( $url );

		if ( ! $parsed ) {
			return $url;
		}

		// Rebuild without query string for cleaner matching.
		// Stack traces often don't include query strings.
		$normalized = '';

		if ( ! empty( $parsed['scheme'] ) ) {
			$normalized .= $parsed['scheme'] . '://';
		}

		if ( ! empty( $parsed['host'] ) ) {
			$normalized .= $parsed['host'];
		}

		if ( ! empty( $parsed['port'] ) ) {
			$normalized .= ':' . $parsed['port'];
		}

		if ( ! empty( $parsed['path'] ) ) {
			$normalized .= $parsed['path'];
		}

		return $normalized;
	}
}
