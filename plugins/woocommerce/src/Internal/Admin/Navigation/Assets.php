<?php
/**
 * Asset enqueuing for navigation_v2.
 *
 * The aliased-CSS trick: read WP's own wp-admin/css/admin-menu.css, rewrite
 * every `#adminmenu` selector to also target `#wc-nav-v2-adminmenu`, and
 * inline the result as a dependent stylesheet. This lets the Woo rail
 * inherit 100% of WP's menu styling — active states, color schemes, hover,
 * flyout, folded mode, RTL — for free. Ported from the WooPro prototype.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the navigation_v2 CSS and JS.
 */
class Assets {

	public const STYLE_HANDLE  = 'wc-nav-v2';
	public const SCRIPT_HANDLE = 'wc-nav-v2';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue.
	 */
	public function enqueue(): void {
		if ( ! is_admin() ) {
			return;
		}

		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '1.0.0';

		wp_enqueue_style(
			self::STYLE_HANDLE,
			WC()->plugin_url() . '/assets/css/admin-navigation-v2.css',
			array( 'admin-menu' ),
			$version
		);

		$aliased = $this->get_aliased_adminmenu_css();
		if ( '' !== $aliased ) {
			wp_add_inline_style( self::STYLE_HANDLE, $aliased );
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WC()->plugin_url() . '/assets/js/admin/admin-navigation-v2.js',
			array( 'jquery' ),
			$version,
			true
		);

		// Expose the computed tree and current-page flag to JS.
		$tree = Menu_Reconciler::get_tree() ?? array();

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcNavV2Config',
			array(
				'isWooPage'      => Context::is_woo_page( $tree ) ? '1' : '0',
				'wpDashboardUrl' => admin_url( 'index.php' ),
				'tree'           => $tree,
			)
		);
	}

	/**
	 * Build an inline CSS block that aliases all WP admin-menu rules to also
	 * target #wc-nav-v2-adminmenu. Cached per WP version + color scheme.
	 *
	 * @return string
	 */
	private function get_aliased_adminmenu_css(): string {
		$color_scheme = get_user_option( 'admin_color' ) ?: 'fresh';
		$cache_key    = 'wc_nav_v2_alias_' . get_bloginfo( 'version' ) . '_' . $color_scheme;
		$cached       = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$css  = '';
		$css .= $this->read_and_alias( ABSPATH . 'wp-admin/css/admin-menu.min.css' );
		if ( '' === $css ) {
			$css .= $this->read_and_alias( ABSPATH . 'wp-admin/css/admin-menu.css' );
		}

		// 'fresh' (default) embeds colors in admin-menu.css itself; other schemes have a separate file.
		if ( 'fresh' !== $color_scheme ) {
			$color_dir  = ABSPATH . 'wp-admin/css/colors/' . sanitize_key( $color_scheme ) . '/';
			$color_file = $color_dir . 'colors.min.css';
			if ( ! file_exists( $color_file ) ) {
				$color_file = $color_dir . 'colors.css';
			}
			if ( file_exists( $color_file ) ) {
				$css .= $this->read_and_alias( $color_file );
			}
		}

		set_transient( $cache_key, $css, WEEK_IN_SECONDS );
		return $css;
	}

	/**
	 * Read one CSS file and rewrite its #adminmenu-family selectors onto our clones.
	 *
	 * @param string $path Absolute path.
	 * @return string Aliased CSS, or '' if file is missing.
	 */
	private function read_and_alias( string $path ): string {
		if ( ! file_exists( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$css = file_get_contents( $path );
		if ( false === $css ) {
			return '';
		}

		$replacements = array(
			'#adminmenuback'         => '#wc-nav-v2',
			'#adminmenuwrap'         => '#wc-nav-v2',
			'#adminmenushadow'       => '#wc-nav-v2',
			'#adminmenu'             => '#wc-nav-v2-adminmenu',
			// Color-scheme CSS sometimes uses ul#adminmenu which the above rewrites break.
			'ul#wc-nav-v2-adminmenu' => '#wc-nav-v2-adminmenu',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $css );
	}
}
