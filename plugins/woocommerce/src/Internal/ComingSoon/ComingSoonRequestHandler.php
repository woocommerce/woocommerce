<?php
namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Handles the template_include hook to determine whether the current page needs
 * to be replaced with a coming soon screen.
 */
class ComingSoonRequestHandler {

	/**
	 * Coming soon helper.
	 *
	 * @var ComingSoonHelper
	 */
	private $coming_soon_helper = null;

	/**
	 * Sets up the hook.
	 *
	 * @internal
	 *
	 * @param ComingSoonHelper $coming_soon_helper Dependency.
	 */
	final public function init( ComingSoonHelper $coming_soon_helper ) {
		$this->coming_soon_helper = $coming_soon_helper;
		add_filter( 'template_include', array( $this, 'handle_template_include' ) );
		add_filter( 'wp_theme_json_data_theme', array( $this, 'experimental_filter_theme_json_theme' ) );
	}


	/**
	 * Replaces the page template with a 'coming soon' when the site is in coming soon mode.
	 *
	 * @internal
	 *
	 * @param string $template The path to the previously determined template.
	 * @return string|null The path to the 'coming soon' template or null to prevent further template loading in FSE themes.
	 */
	public function handle_template_include( $template ) {
		global $wp;

		if ( ! $this->should_show_coming_soon( $wp ) ) {
			return $template;
		}

		// A coming soon page needs to be displayed. Don't cache this response.
		nocache_headers();

		add_theme_support( 'block-templates' );

		$coming_soon_template = get_query_template( 'coming-soon' );

		$is_fse_theme         = wc_current_theme_is_fse_theme();
		$is_store_coming_soon = $this->coming_soon_helper->is_store_coming_soon();

		if ( ! $is_fse_theme && $is_store_coming_soon ) {
			get_header();
		}

		add_action(
			'wp_head',
			function () {
				echo "<meta name='woo-coming-soon-page' content='yes'>";
			}
		);

		include $coming_soon_template;

		if ( ! $is_fse_theme && $is_store_coming_soon ) {
			get_footer();
		}

		if ( $is_fse_theme ) {
			// Since we've already rendered a template, return null to ensure no other template is rendered.
			return null;
		} else {
			// In non-FSE themes, other templates will still be rendered.
			// We need to exit to prevent further processing.
			exit();
		}
	}

	/**
	 * Determines whether the coming soon screen should be shown.
	 *
	 * @param \WP $wp Current WordPress environment instance.
	 *
	 * @return bool
	 */
	private function should_show_coming_soon( \WP &$wp ) {
		// Early exit if LYS feature is disabled.
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
			return false;
		}

		// Early exit if the user is logged in as administrator / shop manager.
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		// Do not show coming soon on 404 pages when restrict to store pages only.
		if ( $this->coming_soon_helper->is_store_coming_soon() && is_404() ) {
			return false;
		}

		// Early exit if the URL doesn't need a coming soon screen.
		$url = $this->coming_soon_helper->get_url_from_wp( $wp );

		if ( ! $this->coming_soon_helper->is_url_coming_soon( $url ) ) {
			return false;
		}

		/**
		 * Check if there is an exclusion.
		 *
		 * @since 9.1.0
		 *
		 * @param bool $is_excluded If the request should be excluded from Coming soon mode. Defaults to false.
		 */
		if ( apply_filters( 'woocommerce_coming_soon_exclude', false ) ) {
			return false;
		}

		// Check if the private link option is enabled.
		if ( get_option( 'woocommerce_private_link' ) === 'yes' ) {
			// Exclude users with a private link.
			if ( isset( $_GET['woo-share'] ) && get_option( 'woocommerce_share_key' ) === $_GET['woo-share'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// Persist the share link with a cookie for 90 days.
				setcookie( 'woo-share', sanitize_text_field( wp_unslash( $_GET['woo-share'] ) ), time() + 60 * 60 * 24 * 90, '/' ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return false;
			}
			if ( isset( $_COOKIE['woo-share'] ) && get_option( 'woocommerce_share_key' ) === $_COOKIE['woo-share'] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Filters the theme.json data to add the Inter and Cardo fonts when they don't exist.
	 *
	 * @param WP_Theme_JSON $theme_json The theme json object.
	 */
	public function experimental_filter_theme_json_theme( $theme_json ) {
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
			return $theme_json;
		}

		$theme_data = $theme_json->get_data();
		$font_data  = $theme_data['settings']['typography']['fontFamilies']['theme'] ?? array();

		$fonts_to_add = array(
			array(
				'fontFamily' => '"Inter", sans-serif',
				'name'       => 'Inter',
				'slug'       => 'inter',
				'fontFace'   => array(
					array(
						'fontFamily'  => 'Inter',
						'fontStretch' => 'normal',
						'fontStyle'   => 'normal',
						'fontWeight'  => '300 900',
						'src'         => array( WC()->plugin_url() . '/assets/fonts/Inter-VariableFont_slnt,wght.woff2' ),
					),
				),
			),
			array(
				'fontFamily' => 'Cardo',
				'name'       => 'Cardo',
				'slug'       => 'cardo',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Cardo',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => array( WC()->plugin_url() . '/assets/fonts/cardo_normal_400.woff2' ),
					),
				),
			),
		);

		// Loops through all existing fonts and append when the font's name is not found.
		foreach ( $fonts_to_add as $font_to_add ) {
			$found = false;
			foreach ( $font_data as $font ) {
				if ( isset( $font['name'] ) && $font['name'] === $font_to_add['name'] ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$font_data[] = $font_to_add;
			}
		}

		$new_data = array(
			'version'  => 1,
			'settings' => array(
				'typography' => array(
					'fontFamilies' => array(
						'theme' => $font_data,
					),
				),
			),
		);
		$theme_json->update_with( $new_data );
		return $theme_json;
	}
}
