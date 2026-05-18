<?php
/**
 * Dashboard widgets loader.
 *
 * Bridges the build artifacts produced by @wordpress/build with the
 * experimental dashboard infrastructure in Gutenberg.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\DashboardWidgets;

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
 */
class Loader {

	/**
	 * Wires the loader into WordPress.
	 *
	 * Requires the build artifacts (which self-register script modules)
	 * and attaches the registry + boot-deps hooks. Safe to call multiple
	 * times: actions are no-ops if the build artifacts are missing or if
	 * Gutenberg's experimental dashboard is not active.
	 */
	public static function init(): void {
		$build_file = plugin_dir_path( WC_PLUGIN_FILE ) . 'build/build.php';
		if ( ! file_exists( $build_file ) ) {
			return;
		}

		require_once $build_file;

		add_action( 'init', array( self::class, 'register_widget_types' ), 11 );
		add_filter( 'dashboard-wp-admin_boot_dependencies', array( self::class, 'add_init_module_to_boot_deps' ) );
		add_filter( 'dashboard-wp-admin_boot_dependencies', array( self::class, 'add_widget_modules_to_boot_deps' ) );
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
