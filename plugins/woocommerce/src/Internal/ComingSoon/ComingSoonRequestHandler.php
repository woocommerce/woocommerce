<?php
namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\Jetpack\Constants;

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
	 * Whether the coming soon screen should be shown. Cache the result to avoid multiple calls to the helper.
	 *
	 * @var bool
	 */
	private static $show_coming_soon = false;

	/**
	 * Sets up the hook.
	 *
	 * @internal
	 *
	 * @param ComingSoonHelper $coming_soon_helper Dependency.
	 */
	final public function init( ComingSoonHelper $coming_soon_helper ) {
		$this->coming_soon_helper = $coming_soon_helper;
		// Hook into plugins_loaded to ensure features are initialized to determine coming soon status.
		add_action(
			'plugins_loaded',
			function () {
				// Skip if the site is live.
				if ( $this->coming_soon_helper->is_site_live() ) {
					return;
				}

				add_filter( 'template_include', array( $this, 'handle_template_include' ) );
				add_action(
					'template_redirect',
					function () {
						// Registered last so no other template_include callback can replace the private page.
						add_filter( 'template_include', array( $this, 'handle_classic_template_include' ), PHP_INT_MAX );
					},
					PHP_INT_MAX
				);
				add_filter( 'wp_theme_json_data_theme', array( $this, 'experimental_filter_theme_json_theme' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			}
		);
	}

	/**
	 * Formerly initialized block templates so classic themes could show the block Coming Soon page.
	 *
	 * @deprecated 11.3.0 Classic themes now use PHP templates, and block themes initialize block templates in the Blocks bootstrap.
	 */
	public function possibly_init_block_templates() {
		wc_deprecated_function( __METHOD__, '11.3.0' );
	}

	/**
	 * Replaces the page template with a 'coming soon' when the site is in coming soon mode.
	 *
	 * @internal
	 *
	 * @param string $template The path to the previously determined template.
	 * @return string The path to the 'coming soon' template or any empty string to prevent further template loading in FSE themes.
	 */
	public function handle_template_include( $template ) {
		if ( $this->should_use_classic_templates() || ! $this->should_show_coming_soon() ) {
			return $template;
		}

		// A coming soon page needs to be displayed. Set a short cache duration to prevents ddos attacks.
		header( 'Cache-Control: max-age=60' );

		$is_fse_theme         = wp_is_block_theme();
		$is_store_coming_soon = $this->coming_soon_helper->is_store_coming_soon();
		add_theme_support( 'block-templates' );

		$coming_soon_template = get_query_template( 'coming-soon' );

		if ( ! $is_fse_theme && $is_store_coming_soon ) {
			get_header();
		}

		$this->add_coming_soon_meta();

		if ( ! empty( $coming_soon_template ) && file_exists( $coming_soon_template ) ) {
			if ( ! $is_fse_theme && $is_store_coming_soon && function_exists( 'get_the_block_template_html' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo get_the_block_template_html();
			} else {
				include $coming_soon_template;
			}
		}

		if ( ! $is_fse_theme && $is_store_coming_soon ) {
			get_footer();
		}

		if ( $is_fse_theme ) {
			// Since we've already rendered a template, return empty string to ensure no other template is rendered.
			return '';
		} else {
			// In non-FSE themes, other templates will still be rendered.
			// We need to exit to prevent further processing.
			exit();
		}
	}

	/**
	 * Selects a PHP Coming Soon template after every other template_include callback.
	 *
	 * @internal
	 *
	 * @param string $template The previously selected template path.
	 * @return string The Coming Soon PHP path, an empty path on lookup failure, or the original path on bypass requests.
	 */
	public function handle_classic_template_include( $template ) {
		if ( ! $this->should_use_classic_templates() || ! $this->should_show_coming_soon() ) {
			return $template;
		}

		if ( ! headers_sent() ) {
			header( 'Cache-Control: max-age=60' );
		}

		/*
		 * A theme's root coming-soon.php is deliberately not looked up: some classic themes ship a page template
		 * with that name that prints the current post, which would expose the protected page.
		 */
		$template_name = $this->coming_soon_helper->is_store_coming_soon() ? 'coming-soon-store-only.php' : 'coming-soon-entire-site.php';
		$selected      = wc_locate_template( 'coming-soon/' . $template_name );

		/*
		 * Fire core's coming-soon_template filter with the arguments the block renderer passes through
		 * get_query_template( 'coming-soon' ), so callbacks see the same contract on either renderer.
		 */
		/** This filter is documented in wp-includes/template.php */
		$selected = apply_filters( 'coming-soon_template', $selected, 'coming-soon', array( 'coming-soon.php' ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		if ( ! is_string( $selected ) || ! is_file( $selected ) || ! is_readable( $selected ) ) {
			// Never fall back to protected content when a template override cannot be loaded.
			return '';
		}

		wp_enqueue_style(
			'woocommerce-coming-soon-classic',
			WC()->plugin_url() . '/assets/css/coming-soon-classic.css',
			array(),
			Constants::get_constant( 'WC_VERSION' )
		);
		wp_style_add_data( 'woocommerce-coming-soon-classic', 'rtl', 'replace' );
		$this->add_coming_soon_meta();

		return $selected;
	}

	/**
	 * Marks the page as Coming Soon for caches and other integrations.
	 */
	private function add_coming_soon_meta(): void {
		add_action(
			'wp_head',
			function () {
				echo "<meta name='woo-coming-soon-page' content='yes'>";
			}
		);
	}

	/**
	 * Classic themes without block template part support use the PHP templates.
	 *
	 * @return bool Whether the PHP templates should be used.
	 */
	private function should_use_classic_templates() {
		return ! BlockTemplateUtils::supports_block_templates( 'wp_template_part' );
	}

	/**
	 * Determines whether the coming soon screen should be shown.
	 *
	 * @return bool
	 */
	private function should_show_coming_soon() {
		// Early exit if already determined that the coming soon screen should be shown.
		if ( self::$show_coming_soon ) {
			return true;
		}

		// Early exit if the user is logged in as administrator / shop manager.
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		// Do not show coming soon on 404 pages when applied to store pages only.
		if ( $this->coming_soon_helper->is_store_coming_soon() && is_404() ) {
			return false;
		}

		// Early exit if the current page doesn't need a coming soon screen.
		if ( ! $this->coming_soon_helper->is_current_page_coming_soon() ) {
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

		self::$show_coming_soon = true;
		return true;
	}

	/**
	 * Filters the theme.json data to add Coming Soon fonts.
	 * This runs after child theme merging to ensure parent theme fonts are included.
	 *
	 * @param WP_Theme_JSON_Data $theme_json The theme json data object.
	 * @return WP_Theme_JSON_Data The filtered theme json data.
	 */
	public function experimental_filter_theme_json_theme( $theme_json ) {
		$theme_data = $theme_json->get_data();
		$font_data  = $theme_data['settings']['typography']['fontFamilies']['theme'] ?? array();

		// Check if the current theme is a child theme. And if so, merge the parent theme fonts with the existing fonts.
		if ( wp_get_theme()->parent() ) {
			$parent_theme           = wp_get_theme()->parent();
			$parent_theme_json_file = $parent_theme->get_file_path( 'theme.json' );

			if ( is_readable( $parent_theme_json_file ) ) {
				$parent_theme_json_data = json_decode( file_get_contents( $parent_theme_json_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

				if ( isset( $parent_theme_json_data['settings']['typography']['fontFamilies'] ) ) {
					$parent_fonts = $parent_theme_json_data['settings']['typography']['fontFamilies'];

					// Merge parent theme fonts with existing fonts.
					foreach ( $parent_fonts as $parent_font ) {
						$found = false;
						foreach ( $font_data as $existing_font ) {
							if ( isset( $parent_font['name'] ) && isset( $existing_font['name'] ) &&
							$parent_font['name'] === $existing_font['name'] ) {
								$found = true;
								break;
							}
						}

						if ( ! $found ) {
							$font_data[] = $parent_font;
						}
					}
				}
			}
		}

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

		// Add WooCommerce fonts if they don't already exist.
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

	/**
	 * Enqueues the coming soon banner styles.
	 */
	public function enqueue_styles() {
		// Early exit if the user is not logged in as administrator / shop manager.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( $this->coming_soon_helper->is_site_live() ) {
			return;
		}

		wp_enqueue_style(
			'woocommerce-coming-soon',
			WC()->plugin_url() . '/assets/css/coming-soon' . ( is_rtl() ? '-rtl' : '' ) . '.css',
			array(),
			Constants::get_constant( 'WC_VERSION' )
		);
	}
}
