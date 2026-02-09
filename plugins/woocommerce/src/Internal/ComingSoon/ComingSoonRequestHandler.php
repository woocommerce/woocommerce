<?php
namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
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
				add_filter( 'wp_theme_json_data_theme', array( $this, 'experimental_filter_theme_json_theme' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
				add_action( 'after_setup_theme', array( $this, 'possibly_init_block_templates' ), 999 );
			}
		);
	}

	/**
	 * Initializes block templates so we can show coming soon page in non-FSE themes.
	 */
	public function possibly_init_block_templates() {
		// No need to initialize block templates since we've already initialized them in the Block Bootstrap.
		if ( wp_is_block_theme() || current_theme_supports( 'block-template-parts' ) ) {
			return;
		}

		$container = BlocksPackage::container();
		$container->get( BlockTemplatesRegistry::class )->init();
		$container->get( BlockTemplatesController::class )->init();
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
		if ( ! $this->should_show_coming_soon() ) {
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

		add_action(
			'wp_head',
			function () {
				echo "<meta name='woo-coming-soon-page' content='yes'>";
			}
		);

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
	 * Determines whether the coming soon screen should be shown.
	 *
	 * @return bool
	 */
	private function should_show_coming_soon() {
		// Early exit if already determined that the coming soon screen should be shown.
		if ( self::$show_coming_soon ) {
			return true;
		}

		// Early exit if LYS feature is disabled.
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
			return false;
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
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
			return $theme_json;
		}

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

		// Early exit if LYS feature is disabled.
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
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
