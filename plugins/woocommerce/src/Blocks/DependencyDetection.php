<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;

/**
 * DependencyDetection class.
 *
 * Provides runtime detection of extensions that use WooCommerce globals
 * (window.wc.*) without properly declaring their PHP script dependencies.
 *
 * Enable by defining WC_DEBUG_DEPENDENCIES as true in wp-config.php:
 * define( 'WC_DEBUG_DEPENDENCIES', true );
 *
 * @since 9.8.0
 * @internal
 */
final class DependencyDetection {

	/**
	 * Asset API interface for script registration.
	 *
	 * @var AssetApi
	 */
	private $api;

	/**
	 * WooCommerce script handles that we track for dependency detection.
	 *
	 * @var array<string>
	 */
	private const WC_SCRIPT_HANDLES = array(
		'wc-blocks-checkout',
		'wc-blocks-registry',
		'wc-blocks-data-store',
		'wc-settings',
		'wc-price-format',
		'wc-blocks-components',
	);

	/**
	 * Constructor.
	 *
	 * @param AssetApi $asset_api Asset API interface.
	 */
	public function __construct( AssetApi $asset_api ) {
		$this->api = $asset_api;

		if ( $this->is_enabled() ) {
			$this->init();
		}
	}

	/**
	 * Check if dependency detection is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		// Enable via constant.
		if ( defined( 'WC_DEBUG_DEPENDENCIES' ) && WC_DEBUG_DEPENDENCIES ) {
			return true;
		}

		/**
		 * Filter to enable dependency detection.
		 *
		 * @since 9.8.0
		 * @param bool $enabled Whether dependency detection is enabled.
		 */
		return (bool) apply_filters( 'woocommerce_blocks_debug_dependencies', false );
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_detection_script' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_detection_script' ), 1 );

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
	 */
	public function output_early_proxy_setup(): void {
		?>
		<script id="wc-dependency-detection-early">
		(function() {
			// Set up a placeholder that will be replaced with the real proxy later.
			// This ensures we capture window.wc before any WC scripts set it.
			var originalWc = window.wc || {};
			var proxyEnabled = false;
			var scriptRegistry = {};
			var registryLoaded = false;
			var warnedScripts = {};
			var pendingChecks = []; // Queue checks until registry is loaded

			var WC_GLOBAL_TO_HANDLE = {
				blocksCheckout: 'wc-blocks-checkout',
				blocksRegistry: 'wc-blocks-registry',
				wcBlocksRegistry: 'wc-blocks-registry',
				blocksData: 'wc-blocks-data-store',
				wcBlocksData: 'wc-blocks-data-store',
				wcSettings: 'wc-settings',
				priceFormat: 'wc-price-format',
				blocksComponents: 'wc-blocks-components'
			};

			// Patterns to identify WooCommerce's own scripts (which we should skip).
			var WC_SCRIPT_PATTERNS = [
				/\/woocommerce\//i,
				/\/wc-blocks/i,
				/wc-blocks-/,
				/blocks-checkout/,
				/blocks-components/,
				/blocks-registry/,
				/blocks-data/,
				/price-format/,
				/checkout-frontend/,
				/cart-frontend/,
				/wc-settings/,
				/wc-payment-method-/
			];

			function isWooCommerceScript(url) {
				if (!url) return false;
				for (var i = 0; i < WC_SCRIPT_PATTERNS.length; i++) {
					if (WC_SCRIPT_PATTERNS[i].test(url)) {
						return true;
					}
				}
				return false;
			}

			function getFilename(url) {
				return url.split('/').pop().split('?')[0].split('#')[0];
			}

			function parseStackForCallerUrl(stack) {
				if (!stack) return null;
				var lines = stack.split('\n');
				for (var i = 1; i < lines.length; i++) {
					var line = lines[i];
					if (line.indexOf('wc-dependency-detection') !== -1) continue;
					var match = line.match(/(https?:\/\/[^\s\)]+)/);
					if (match) {
						return match[1].replace(/:\d+:\d+$/, '').replace(/\?.*$/, '');
					}
				}
				return null;
			}

			function getCallerScriptUrl() {
				if (document.currentScript && document.currentScript.src) {
					return document.currentScript.src.replace(/\?.*$/, '');
				}
				var stack = new Error().stack;
				return parseStackForCallerUrl(stack);
			}

			function checkDependency(callerUrl, prop, requiredHandle) {
				// Skip WooCommerce's own scripts - they manage their own dependencies.
				if (isWooCommerceScript(callerUrl)) {
					return;
				}

				// If registry not loaded yet, queue the check for later.
				if (!registryLoaded) {
					pendingChecks.push({callerUrl: callerUrl, prop: prop, requiredHandle: requiredHandle});
					return;
				}

				performCheck(callerUrl, prop, requiredHandle);
			}

			function performCheck(callerUrl, prop, requiredHandle) {
				var warningKey = (callerUrl || 'inline') + ':' + prop;
				if (warnedScripts[warningKey]) return;

				if (!callerUrl) {
					console.warn('[WooCommerce] An inline or unknown script accessed wc.' + prop + ' without proper dependency declaration. This script should declare "' + requiredHandle + '" as a dependency.');
					warnedScripts[warningKey] = true;
					return;
				}

				var scriptInfo = scriptRegistry[callerUrl];
				if (!scriptInfo) {
					console.warn('[WooCommerce] Unregistered script "' + getFilename(callerUrl) + '" accessed wc.' + prop + '. This script should be registered with wp_enqueue_script() and declare "' + requiredHandle + '" as a dependency.');
					warnedScripts[warningKey] = true;
					return;
				}

				if (scriptInfo.deps.indexOf(requiredHandle) === -1) {
					console.warn('[WooCommerce] Script "' + scriptInfo.handle + '" accessed wc.' + prop + ' without declaring "' + requiredHandle + '" as a dependency. Add "' + requiredHandle + '" to the script\'s dependencies array.');
					warnedScripts[warningKey] = true;
				}
			}

			function createWcProxy(target) {
				return new Proxy(target, {
					get: function(obj, prop) {
						if (proxyEnabled && WC_GLOBAL_TO_HANDLE[prop]) {
							var callerUrl = getCallerScriptUrl();
							checkDependency(callerUrl, prop, WC_GLOBAL_TO_HANDLE[prop]);
						}
						return Reflect.get(obj, prop);
					},
					set: function(obj, prop, value) {
						return Reflect.set(obj, prop, value);
					}
				});
			}

			// Create the proxy immediately.
			var wcProxy = createWcProxy(originalWc);

			// Define window.wc as a getter/setter to maintain the proxy.
			Object.defineProperty(window, 'wc', {
				get: function() { return wcProxy; },
				set: function(newValue) {
					// When WC scripts set window.wc, wrap the new value.
					originalWc = newValue;
					wcProxy = createWcProxy(newValue);
				},
				configurable: true,
				enumerable: true
			});

			// Expose function to update registry (called later after all scripts registered).
			window.__wcUpdateDependencyRegistry = function(registry) {
				scriptRegistry = registry || {};
				registryLoaded = true;

				// Process any pending checks now that we have the registry.
				for (var i = 0; i < pendingChecks.length; i++) {
					var check = pendingChecks[i];
					performCheck(check.callerUrl, check.prop, check.requiredHandle);
				}
				pendingChecks = [];
			};

			// Enable detection immediately.
			proxyEnabled = true;
			console.info('[WooCommerce] Dependency detection enabled. Warnings will be shown for scripts that access wc.* globals without proper dependencies.');
		})();
		</script>
		<?php
	}

	/**
	 * Register the dependency detection script.
	 */
	public function register_detection_script(): void {
		$this->api->register_script(
			'wc-dependency-detection',
			'assets/client/blocks/dependency-detection.js',
			array(),
			false
		);
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
		return array_values(
			array_filter(
				$all_deps,
				function ( $dep ) {
					return in_array( $dep, self::WC_SCRIPT_HANDLES, true );
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
