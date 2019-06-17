<?php
/**
 * Theme information for status report.
 *
 * @package WooCommerce/Utilities
 */

namespace WooCommerce\RestApi\Utilities;

/**
 * ThemeInformation class.
 */
class ThemeInformation {
	/**
	 * Get info on the current active theme, info on parent theme (if presnet)
	 * and a list of template overrides.
	 *
	 * @return array
	 */
	public function get_theme_info() {
		$active_theme = wp_get_theme();

		// Get parent theme info if this theme is a child theme, otherwise
		// pass empty info in the response.
		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->template );
			$parent_theme_info = array(
				'parent_name'           => $parent_theme->name,
				'parent_version'        => $parent_theme->version,
				'parent_version_latest' => \WC_Admin_Status::get_latest_theme_version( $parent_theme ),
				'parent_author_url'     => $parent_theme->{'Author URI'},
			);
		} else {
			$parent_theme_info = array(
				'parent_name'           => '',
				'parent_version'        => '',
				'parent_version_latest' => '',
				'parent_author_url'     => '',
			);
		}

		/**
		 * Scan the theme directory for all WC templates to see if our theme
		 * overrides any of them.
		 */
		$override_files     = array();
		$outdated_templates = false;
		$scan_files         = \WC_Admin_Status::scan_template_files( WC()->plugin_path() . '/templates/' );
		foreach ( $scan_files as $file ) {
			$located = apply_filters( 'wc_get_template', $file, $file, array(), WC()->template_path(), WC()->plugin_path() . '/templates/' );

			if ( file_exists( $located ) ) {
				$theme_file = $located;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . WC()->template_path() . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . WC()->template_path() . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . WC()->template_path() . $file ) ) {
				$theme_file = get_template_directory() . '/' . WC()->template_path() . $file;
			} else {
				$theme_file = false;
			}

			if ( ! empty( $theme_file ) ) {
				$core_version  = \WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $file );
				$theme_version = \WC_Admin_Status::get_file_version( $theme_file );
				if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
					if ( ! $outdated_templates ) {
						$outdated_templates = true;
					}
				}
				$override_files[] = array(
					'file'         => str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
					'version'      => $theme_version,
					'core_version' => $core_version,
				);
			}
		}

		$active_theme_info = array(
			'name'                    => $active_theme->name,
			'version'                 => $active_theme->version,
			'version_latest'          => \WC_Admin_Status::get_latest_theme_version( $active_theme ),
			'author_url'              => esc_url_raw( $active_theme->{'Author URI'} ),
			'is_child_theme'          => is_child_theme(),
			'has_woocommerce_support' => current_theme_supports( 'woocommerce' ),
			'has_woocommerce_file'    => ( file_exists( get_stylesheet_directory() . '/woocommerce.php' ) || file_exists( get_template_directory() . '/woocommerce.php' ) ),
			'has_outdated_templates'  => $outdated_templates,
			'overrides'               => $override_files,
		);

		return array_merge( $active_theme_info, $parent_theme_info );
	}
}
