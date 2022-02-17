<?php
/**
 * Navigation Experience
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Internal\Admin\Survey;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;
use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\CoreMenu;

/**
 * Contains logic for the Navigation
 */
class Init {
	/**
	 * Option name used to toggle this feature.
	 */
	const TOGGLE_OPTION_NAME = 'woocommerce_navigation_enabled';

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_features', array( $this, 'add_feature_toggle' ) );
		add_action( 'update_option_' . self::TOGGLE_OPTION_NAME, array( $this, 'reload_page_on_toggle' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_opt_out_scripts' ) );

		if ( Features::is_enabled( 'navigation' ) ) {
			Menu::instance()->init();
			CoreMenu::instance()->init();
			Screen::instance()->init();
		}
	}

	/**
	 * Add the feature toggle to the features settings.
	 *
	 * @param array $features Feature sections.
	 * @return array
	 */
	public static function add_feature_toggle( $features ) {
		$description  = __(
			'Adds the new WooCommerce navigation experience to the dashboard',
			'woocommerce-admin'
		);
		$update_text  = '';
		$needs_update = version_compare( get_bloginfo( 'version' ), '5.6', '<' );
		if ( $needs_update && current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
			$update_text = sprintf(
				/* translators: 1: line break tag, 2: open link to WordPress update link, 3: close link tag. */
				__( '%1$s %2$sUpdate WordPress to enable the new navigation%3$s', 'woocommerce-admin' ),
				'<br/>',
				'<a href="' . self_admin_url( 'update-core.php' ) . '" target="_blank">',
				'</a>'
			);
		}

		$features[] = array(
			'title' => __( 'Navigation', 'woocommerce-admin' ),
			'desc'  => $description . $update_text,
			'id'    => self::TOGGLE_OPTION_NAME,
			'type'  => 'checkbox',
			'class' => $needs_update ? 'disabled' : '',
		);

		return $features;
	}

	/**
	 * Determine if sufficient versions are present to support Navigation feature
	 */
	public function is_nav_compatible() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$gutenberg_minimum_version = '9.0.0'; // https://github.com/WordPress/gutenberg/releases/tag/v9.0.0.
		$wp_minimum_version        = '5.6';
		$has_gutenberg             = is_plugin_active( 'gutenberg/gutenberg.php' );
		$gutenberg_version         = $has_gutenberg ? get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' )['Version'] : false;

		if ( $gutenberg_version && version_compare( $gutenberg_version, $gutenberg_minimum_version, '>=' ) ) {
			return true;
		}

		// Get unmodified $wp_version.
		include ABSPATH . WPINC . '/version.php';

		// Strip '-src' from the version string. Messes up version_compare().
		$wp_version = str_replace( '-src', '', $wp_version );

		if ( version_compare( $wp_version, $wp_minimum_version, '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Reloads the page when the option is toggled to make sure all nav features are loaded.
	 *
	 * @param string $old_value Old value.
	 * @param string $value     New value.
	 */
	public static function reload_page_on_toggle( $old_value, $value ) {
		if ( $old_value === $value ) {
			return;
		}

		if ( 'yes' !== $value ) {
			update_option( 'woocommerce_navigation_show_opt_out', 'yes' );
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			wp_safe_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			exit();
		}
	}

	/**
	 * Enqueue the opt out scripts.
	 */
	public function maybe_enqueue_opt_out_scripts() {
		if ( 'yes' !== get_option( 'woocommerce_navigation_show_opt_out', 'no' ) ) {
			return;
		}

		$rtl = is_rtl() ? '.rtl' : '';
		wp_enqueue_style(
			'wc-admin-navigation-opt-out',
			Loader::get_url( "navigation-opt-out/style{$rtl}", 'css' ),
			array( 'wp-components' ),
			Loader::get_file_version( 'css' )
		);

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'navigation-opt-out' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'wc-admin-navigation-opt-out',
			Loader::get_url( 'wp-admin-scripts/navigation-opt-out', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			Loader::get_file_version( 'js' ),
			true
		);

		wp_localize_script(
			'wc-admin-navigation-opt-out',
			'surveyData',
			array(
				'url' => Survey::get_url( '/new-navigation-opt-out' ),
			)
		);

		delete_option( 'woocommerce_navigation_show_opt_out' );
	}
}
