<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Internal\Utilities\BlocksUtil;

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
	 * WooCommerce blocks that use the tracked globals.
	 *
	 * Detection script only runs on pages containing these blocks.
	 *
	 * @var array<string>
	 */
	private const TRACKED_BLOCKS = array(
		'woocommerce/checkout',
		'woocommerce/cart',
		'woocommerce/mini-cart',
	);

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
	 * Whether the proxy script was output.
	 *
	 * Used to ensure we only output the registry if the proxy was set up.
	 *
	 * @var bool
	 */
	private bool $proxy_output = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 10.5.0
	 */
	public function init(): void {
		// Only run when debugging is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Output an early inline script to set up the Proxy before any other scripts run.
		add_action( 'wp_head', array( $this, 'output_early_proxy_setup' ), 1 );
		add_action( 'admin_head', array( $this, 'output_early_proxy_setup' ), 1 );

		// Output registry late when all scripts (including IntegrationInterface) are registered.
		add_action( 'wp_print_footer_scripts', array( $this, 'output_script_registry' ), 1 );
		add_action( 'admin_print_footer_scripts', array( $this, 'output_script_registry' ), 1 );
	}

	/**
	 * Output early inline script to set up the Proxy on window.wc.
	 *
	 * This must run before any WooCommerce scripts to intercept access.
	 * The script is loaded from a separate file for better IDE support and testing,
	 * but output inline to ensure correct timing (before any enqueued scripts).
	 *
	 * @since 10.5.0
	 */
	public function output_early_proxy_setup(): void {
		// Only run on pages that have the tracked blocks.
		if ( ! $this->page_has_tracked_blocks() ) {
			return;
		}

		// Load from the production assets directory (built by webpack and copied during release build).
		$script_path = __DIR__ . '/../../assets/client/blocks/dependency-detection.js';

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read for inline script output.
		$script_content = file_get_contents( $script_path );

		if ( ! $script_content ) {
			return;
		}

		// Inject the global-to-handle mapping from PHP (source of truth).
		$mapping_json = \wp_json_encode( self::WC_GLOBAL_EXPORTS );
		if ( false === $mapping_json ) {
			return;
		}
		$script_content = str_replace(
			'__WC_GLOBAL_EXPORTS_PLACEHOLDER__',
			$mapping_json,
			$script_content
		);

		// Inject the WooCommerce plugin URL for script origin detection.
		// This accounts for custom plugin directories (WP_PLUGIN_DIR, WP_CONTENT_DIR).
		$wc_plugin_url  = \plugins_url( '/', WC_PLUGIN_FILE );
		$script_content = str_replace(
			'__WC_PLUGIN_URL_PLACEHOLDER__',
			'"' . esc_js( $wc_plugin_url ) . '"',
			$script_content
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Script content is from a trusted local file, JSON is safely encoded.
		echo '<script id="wc-dependency-detection">' . $script_content . '</script>' . "\n";

		$this->proxy_output = true;
	}

	/**
	 * Output the script registry JSON for dependency checking.
	 *
	 * This runs late (wp_print_footer_scripts) to ensure all scripts,
	 * including those registered via IntegrationInterface, are captured.
	 *
	 * @since 10.5.0
	 */
	public function output_script_registry(): void {
		// Only output registry if the proxy was set up earlier.
		// This avoids the duplicate page_has_tracked_blocks() check and ensures
		// we don't output a registry without a proxy to consume it.
		if ( ! $this->proxy_output ) {
			return;
		}

		// Build the script registry mapping URLs to handles and dependencies.
		$script_registry = $this->build_script_registry();
		$registry_json   = \wp_json_encode( $script_registry );

		if ( false === $registry_json ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safely encoded by wp_json_encode.
		echo '<script id="wc-dependency-detection-registry">if(typeof window.wc.wcUpdateDependencyRegistry==="function"){window.wc.wcUpdateDependencyRegistry(' . $registry_json . ');}</script>' . "\n";
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
			if ( ! is_string( $src ) ) {
				// Skip malformed src.
				continue;
			}
			if ( ! preg_match( '|^(https?:)?//|', $src ) ) {
				// Relative URL - make it absolute.
				$src = $wp_scripts->base_url . $src;
			}

			// Skip WooCommerce's own scripts - we don't need to check those.
			if ( $this->is_woocommerce_script( $src ) ) {
				continue;
			}

			// Skip WordPress core scripts - they won't use wc.* globals.
			if ( $this->is_wordpress_core_script( $src ) ) {
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
		// Get the WooCommerce plugin URL (accounts for custom plugin directories).
		$wc_plugin_url = \plugins_url( '/', WC_PLUGIN_FILE );

		// Check if the URL starts with the WooCommerce plugin URL and is in a known subdirectory.
		if ( strpos( $url, $wc_plugin_url ) !== 0 ) {
			return false;
		}

		// Get the path after the WooCommerce plugin URL.
		$relative_path = substr( $url, strlen( $wc_plugin_url ) );

		// Check if it's in one of the known WooCommerce asset directories.
		return (bool) preg_match( '#^(client|assets|build|vendor)/#', $relative_path );
	}

	/**
	 * Check if a script URL belongs to WordPress core.
	 *
	 * WordPress core scripts (wp-includes, wp-admin) won't use wc.* globals,
	 * so we can skip them to reduce registry size.
	 *
	 * @param string $url Script URL.
	 * @return bool
	 */
	private function is_wordpress_core_script( string $url ): bool {
		return (bool) preg_match( '#/(wp-includes|wp-admin)/#', $url );
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
	 * Check if the current page contains any of the tracked blocks.
	 * Checks post content, widget areas, and template parts (header) for blocks.
	 *
	 * @return bool True if page has tracked blocks.
	 */
	private function page_has_tracked_blocks(): bool {
		// Check post content for blocks.
		foreach ( self::TRACKED_BLOCKS as $block_name ) {
			if ( \has_block( $block_name ) ) {
				return true;
			}
		}

		// Check widget areas for mini-cart (classic themes).
		$mini_cart_in_widgets = BlocksUtil::get_blocks_from_widget_area( 'woocommerce/mini-cart' );
		if ( ! empty( $mini_cart_in_widgets ) ) {
			return true;
		}

		// Check header template part for mini-cart (block themes).
		try {
			$mini_cart_in_header = BlocksUtil::get_block_from_template_part( 'woocommerce/mini-cart', 'header' );
			if ( ! empty( $mini_cart_in_header ) ) {
				return true;
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Template part may not exist in all themes, silently continue.
		}

		return false;
	}

	/**
	 * Normalize a URL by removing query strings and hash fragments.
	 *
	 * This helps match URLs in stack traces which don't include query strings.
	 *
	 * @param string $url URL to normalize.
	 * @return string Normalized URL without query string or hash.
	 */
	private function normalize_url( string $url ): string {
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$path   = wp_parse_url( $url, PHP_URL_PATH );

		if ( $scheme && $host && $path ) {
			$port = wp_parse_url( $url, PHP_URL_PORT );
			return $scheme . '://' . $host . ( $port ? ':' . $port : '' ) . $path;
		}

		return $url;
	}
}
