<?php
/**
 * Dashboard widgets loader.
 *
 * Bridges the build artifacts produced by @wordpress/build with the
 * experimental dashboard infrastructure in Gutenberg.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\DashboardWidgets;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WP_Widget_Type_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard widgets loader.
 *
 * On `init`, registers each widget produced by the build pipeline with
 * `WP_Widget_Type_Registry`, and adds the script modules to the dashboard
 * page's boot dependencies so dynamic imports resolve through the import
 * map. Mirrors what Gutenberg does for its own widgets in
 * `lib/experimental/dashboard-widgets/widget-types.php`; cross-plugin
 * discovery is opt-in.
 *
 * Gated behind the `dashboard_widgets` experimental feature flag (see
 * `FeaturesController`). The whole subsystem is a no-op when the flag is
 * off — no hooks are registered, no PHP files included.
 */
class Loader {

	/**
	 * Wires the loader into WordPress.
	 *
	 * Called during plugin file load from `woocommerce.php`, so the actual
	 * setup is deferred to the `init` action. Two things conspire to make
	 * earlier hooks unsafe:
	 *
	 * - `FeaturesUtil::feature_is_enabled()` instantiates `FeaturesController`
	 *   through the DI container, which references classes like
	 *   `WC_Site_Tracking` that are not autoloaded until WC's `includes()`
	 *   runs on `plugins_loaded`. So the check itself must be on
	 *   `plugins_loaded` or later.
	 * - `FeaturesController::init_feature_definitions()` calls `__()` on the
	 *   `woocommerce` text domain for every feature label/description.
	 *   WordPress 6.7+ warns whenever translation loading is triggered
	 *   before the `init` action has fired (`did_action( 'init' )`), so the
	 *   check must wait until at least `init`.
	 *
	 * We hook at `init` priority 1 — early enough that the higher-priority
	 * widget-type registration (priority 11) still fires in the same `init`
	 * cycle, late enough that the text domain is safe to load.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'maybe_bootstrap' ), 1 );
	}

	/**
	 * Conditionally bootstraps the dashboard widgets subsystem.
	 *
	 * Returns early when the `dashboard_widgets` feature flag is disabled
	 * (the default on a fresh install) or when the build artifacts have
	 * not been produced. Otherwise loads the build registries and attaches
	 * the hooks that wire widgets into the dashboard page.
	 */
	public static function maybe_bootstrap(): void {
		if ( ! FeaturesUtil::feature_is_enabled( 'dashboard_widgets' ) ) {
			return;
		}

		$build_file = plugin_dir_path( WC_PLUGIN_FILE ) . 'build/build.php';
		if ( ! file_exists( $build_file ) ) {
			return;
		}

		require_once $build_file;

		add_action( 'init', array( self::class, 'register_widget_types' ), 11 );
		add_filter( 'dashboard-wp-admin_boot_dependencies', array( self::class, 'add_init_module_to_boot_deps' ) );
		add_filter( 'dashboard-wp-admin_boot_dependencies', array( self::class, 'add_widget_modules_to_boot_deps' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_init_module_evaluator' ), 20 );
	}

	/**
	 * Forces the core-dashboard init module to actually run on the dashboard
	 * page.
	 *
	 * wp-build's `pages/dashboard/loader.js` is an empty module — its
	 * `boot_dependencies` are preloaded via the import map but never imported,
	 * so their top-level code never executes. We piggyback on the dashboard
	 * prerequisites inline script (which is where Gutenberg dispatches
	 * `@wordpress/boot`) to add a dynamic import that evaluates the module
	 * and runs its `addFilter` side effects.
	 *
	 * Safe-guard: the `dashboard-wp-admin-prerequisites` script handle is
	 * registered by wp-build's generated `page-wp-admin.php` only when
	 * Gutenberg's experimental dashboard infrastructure is loading the
	 * page. If the handle is not registered, the experimental dashboard
	 * is either turned off or this is some other admin page — either way
	 * we have nothing to extend, so we no-op.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public static function enqueue_init_module_evaluator( string $hook_suffix ): void {
		if ( ! wp_script_is( 'dashboard-wp-admin-prerequisites', 'registered' ) ) {
			return;
		}

		wp_add_inline_script(
			'dashboard-wp-admin-prerequisites',
			'import("@woocommerce/core-dashboard-init");'
		);
	}

	/**
	 * Registers each WooCommerce widget in the shared widget type registry.
	 */
	public static function register_widget_types(): void {
		if ( ! class_exists( WP_Widget_Type_Registry::class ) ) {
			return;
		}
		if ( ! function_exists( 'woocommerce_get_registered_widget_modules' ) ) {
			return;
		}

		$registry = WP_Widget_Type_Registry::get_instance();

		foreach ( woocommerce_get_registered_widget_modules() as $widget ) {
			if ( empty( $widget['name'] ) || $registry->is_registered( $widget['name'] ) ) {
				continue;
			}

			$registry->register(
				$widget['name'],
				array(
					'render_module' => $widget['render_module'] ?? null,
					'widget_module' => $widget['widget_module'] ?? null,
				)
			);
		}
	}

	/**
	 * Adds the WooCommerce core-dashboard init module to the dashboard page
	 * boot dependencies. Loaded as a static import so its top-level filters
	 * (e.g. `storeActivity.sources`) register before any widget renders.
	 *
	 * @param array $boot_dependencies Boot dependencies for the dashboard page.
	 * @return array Updated boot dependencies.
	 */
	public static function add_init_module_to_boot_deps( array $boot_dependencies ): array {
		$boot_dependencies[] = array(
			'import' => 'static',
			'id'     => '@woocommerce/core-dashboard-init',
		);

		return $boot_dependencies;
	}

	/**
	 * Adds WooCommerce widget modules to the dashboard page boot dependencies.
	 *
	 * @param array $boot_dependencies Boot dependencies for the dashboard page.
	 * @return array Updated boot dependencies.
	 */
	public static function add_widget_modules_to_boot_deps( array $boot_dependencies ): array {
		if ( ! function_exists( 'woocommerce_get_registered_widget_modules' ) ) {
			return $boot_dependencies;
		}

		foreach ( woocommerce_get_registered_widget_modules() as $widget ) {
			if ( ! empty( $widget['render_module'] ) ) {
				$boot_dependencies[] = array(
					'import' => 'dynamic',
					'id'     => $widget['render_module'],
				);
			}

			if ( ! empty( $widget['widget_module'] ) ) {
				$boot_dependencies[] = array(
					'import' => 'dynamic',
					'id'     => $widget['widget_module'],
				);
			}
		}

		return $boot_dependencies;
	}
}
