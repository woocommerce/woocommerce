<?php
/**
 * Addons Page
 *
 * @package  WooCommerce\Admin
 * @version  2.5.0
 */

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Addons Class.
 */
class WC_Admin_Addons {

	/**
	 * Get featured for the addons screen
	 *
	 * @return void
	 */
	public static function render_featured() {
		$featured = false; // get_transient( 'wc_addons_featured' ); // TODO: revert.
		if ( false === $featured ) {
			$headers = array();
			$auth    = WC_Helper_Options::get( 'auth' );

			if ( ! empty( $auth['access_token'] ) ) {
				$headers['Authorization'] = 'Bearer ' . $auth['access_token'];
			}

			// TODO: replace with WC.com, leave 2.0.
			$raw_featured = wp_safe_remote_get(
				'https://woocommerce.test/wp-json/wccom-extensions/2.0/featured',
				array(
					'headers'    => $headers,
					'user-agent' => 'WooCommerce Addons Page',
				)
			);

			if ( ! is_wp_error( $raw_featured ) ) {
				$featured = json_decode( wp_remote_retrieve_body( $raw_featured ) );
				if ( $featured ) {
					set_transient( 'wc_addons_featured', $featured, DAY_IN_SECONDS );
				}
			}
		}

		if ( is_object( $featured ) ) {
			self::output_featured( $featured );
		}
	}

	/**
	 * Build url parameter string
	 *
	 * @param  string $category Addon (sub) category.
	 * @param  string $term     Search terms.
	 * @param  string $country  Store country.
	 *
	 * @return string url parameter string
	 */
	public static function build_parameter_string( $category, $term, $country ) {

		$parameters = array(
			'category' => $category,
			'term'     => $term,
			'country'  => $country,
		);

		return '?' . http_build_query( $parameters );
	}

	/**
	 * Call API to get extensions
	 *
	 * @param  string $category Addon (sub) category.
	 * @param  string $term     Search terms.
	 * @param  string $country  Store country.
	 *
	 * @return array of extensions
	 */
	public static function get_extension_data( $category, $term, $country ) {
		$parameters     = self::build_parameter_string( $category, $term, $country );

		$headers = array();
		$auth    = WC_Helper_Options::get( 'auth' );

		if ( ! empty( $auth['access_token'] ) ) {
			$headers['Authorization'] = 'Bearer ' . $auth['access_token'];
		}

		$raw_extensions = wp_safe_remote_get(
			'https://woocommerce.com/wp-json/wccom-extensions/1.0/search' . $parameters,
			array( 'headers' => $headers )
		);

		if ( ! is_wp_error( $raw_extensions ) ) {
			$addons = json_decode( wp_remote_retrieve_body( $raw_extensions ) )->products;
		}
		return $addons;
	}

	/**
	 * Get sections for the addons screen
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		$addon_sections = get_transient( 'wc_addons_sections' );
		if ( false === ( $addon_sections ) ) {
			$raw_sections = wp_safe_remote_get(
				'https://woocommerce.com/wp-json/wccom-extensions/1.0/categories'
			);
			if ( ! is_wp_error( $raw_sections ) ) {
				$addon_sections = json_decode( wp_remote_retrieve_body( $raw_sections ) );
				if ( $addon_sections ) {
					set_transient( 'wc_addons_sections', $addon_sections, WEEK_IN_SECONDS );
				}
			}
		}
		return apply_filters( 'woocommerce_addons_sections', $addon_sections );
	}

	/**
	 * Get section for the addons screen.
	 *
	 * @param  string $section_id Required section ID.
	 *
	 * @return object|bool
	 */
	public static function get_section( $section_id ) {
		$sections = self::get_sections();
		if ( isset( $sections[ $section_id ] ) ) {
			return $sections[ $section_id ];
		}
		return false;
	}

	/**
	 * Get section content for the addons screen.
	 *
	 * @param  string $section_id Required section ID.
	 *
	 * @return array
	 */
	public static function get_section_data( $section_id ) {
		$section      = self::get_section( $section_id );
		$section_data = '';

		if ( ! empty( $section->endpoint ) ) {
			$section_data = get_transient( 'wc_addons_section_' . $section_id );
			if ( false === $section_data ) {
				$raw_section = wp_safe_remote_get( esc_url_raw( $section->endpoint ), array( 'user-agent' => 'WooCommerce Addons Page' ) );

				if ( ! is_wp_error( $raw_section ) ) {
					$section_data = json_decode( wp_remote_retrieve_body( $raw_section ) );

					if ( ! empty( $section_data->products ) ) {
						set_transient( 'wc_addons_section_' . $section_id, $section_data, WEEK_IN_SECONDS );
					}
				}
			}
		}

		return apply_filters( 'woocommerce_addons_section_data', $section_data->products, $section_id );
	}

	/**
	 * Handles the outputting of a contextually aware Storefront link (points to child themes if Storefront is already active).
	 */
	public static function output_storefront_button() {
		$template   = get_option( 'template' );
		$stylesheet = get_option( 'stylesheet' );

		if ( 'storefront' === $template ) {
			if ( 'storefront' === $stylesheet ) {
				$url         = 'https://woocommerce.com/product-category/themes/storefront-child-theme-themes/';
				$text        = __( 'Need a fresh look? Try Storefront child themes', 'woocommerce' );
				$utm_content = 'nostorefrontchildtheme';
			} else {
				$url         = 'https://woocommerce.com/product-category/themes/storefront-child-theme-themes/';
				$text        = __( 'View more Storefront child themes', 'woocommerce' );
				$utm_content = 'hasstorefrontchildtheme';
			}
		} else {
			$url         = 'https://woocommerce.com/storefront/';
			$text        = __( 'Need a theme? Try Storefront', 'woocommerce' );
			$utm_content = 'nostorefront';
		}

		$url = add_query_arg(
			array(
				'utm_source'   => 'addons',
				'utm_medium'   => 'product',
				'utm_campaign' => 'woocommerceplugin',
				'utm_content'  => $utm_content,
			),
			$url
		);

		echo '<a href="' . esc_url( $url ) . '" class="add-new-h2">' . esc_html( $text ) . '</a>' . "\n";
	}

	/**
	 * Handles the outputting of featured page
	 *
	 * @param array $blocks Featured page's blocks.
	 */
	private static function output_featured( $blocks ) {
		foreach ( $blocks as $block ) {
			switch ( $block->type ) {
				case 'group':
					// TODO:
					break;
				case 'banner':
					// TODO:
					break;
			}
		}
	}

	/**
	 * Returns in-app-purchase URL params.
	 */
	public static function get_in_app_purchase_url_params() {
		// Get url (from path onward) for the current page,
		// so WCCOM "back" link returns user to where they were.
		$back_admin_path = add_query_arg( array() );
		return array(
			'wccom-site'          => site_url(),
			'wccom-back'          => rawurlencode( $back_admin_path ),
			'wccom-woo-version'   => Constants::get_constant( 'WC_VERSION' ),
			'wccom-connect-nonce' => wp_create_nonce( 'connect' ),
		);
	}

	/**
	 * Add in-app-purchase URL params to link.
	 *
	 * Adds various url parameters to a url to support a streamlined
	 * flow for obtaining and setting up WooCommerce extensons.
	 *
	 * @param string $url    Destination URL.
	 */
	public static function add_in_app_purchase_url_params( $url ) {
		return add_query_arg(
			self::get_in_app_purchase_url_params(),
			$url
		);
	}

	/**
	 * Outputs a button.
	 *
	 * @param string $url    Destination URL.
	 * @param string $text   Button label text.
	 * @param string $style  Button style class.
	 * @param string $plugin The plugin the button is promoting.
	 */
	public static function output_button( $url, $text, $style, $plugin = '' ) {
		$style = __( 'Free', 'woocommerce' ) === $text ? 'addons-button-outline-purple' : $style;
		$style = is_plugin_active( $plugin ) ? 'addons-button-installed' : $style;
		$text  = is_plugin_active( $plugin ) ? __( 'Installed', 'woocommerce' ) : $text;
		$url   = self::add_in_app_purchase_url_params( $url );
		?>
		<a
			class="addons-button <?php echo esc_attr( $style ); ?>"
			href="<?php echo esc_url( $url ); ?>">
			<?php echo esc_html( $text ); ?>
		</a>
		<?php
	}


	/**
	 * Handles output of the addons page in admin.
	 */
	public static function output() {
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_featured';
		$search  = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		if ( isset( $_GET['section'] ) && 'helper' === $_GET['section'] ) {
			do_action( 'woocommerce_helper_output' );
			return;
		}

		if ( isset( $_GET['install-addon'] ) ) {
			switch ( $_GET['install-addon'] ) {
				case 'woocommerce-services':
					self::install_woocommerce_services_addon();
					break;
				case 'woocommerce-payments':
					self::install_woocommerce_payments_addon( $section );
					break;
				default:
					// Do nothing.
					break;
			}
		}

		$sections        = self::get_sections();
		$theme           = wp_get_theme();
		$current_section = isset( $_GET['section'] ) ? $section : '_featured';
		$addons          = array();

		if ( '_featured' !== $current_section ) {
			$category = $section ? $section : null;
			$term     = $search ? $search : null;
			$country  = WC()->countries->get_base_country();
			$addons   = self::get_extension_data( $category, $term, $country );
		}

		/**
		 * Addon page view.
		 *
		 * @uses $addons
		 * @uses $search
		 * @uses $sections
		 * @uses $theme
		 * @uses $current_section
		 */
		include_once dirname( __FILE__ ) . '/views/html-admin-page-addons.php';
	}

	/**
	 * Install WooCommerce Services from Extensions screens.
	 */
	public static function install_woocommerce_services_addon() {
		check_admin_referer( 'install-addon_woocommerce-services' );

		$services_plugin_id = 'woocommerce-services';
		$services_plugin    = array(
			'name'      => __( 'WooCommerce Services', 'woocommerce' ),
			'repo-slug' => 'woocommerce-services',
		);

		WC_Install::background_installer( $services_plugin_id, $services_plugin );

		wp_safe_redirect( remove_query_arg( array( 'install-addon', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Install WooCommerce Payments from the Extensions screens.
	 *
	 * @param string $section Optional. Extenstions tab.
	 *
	 * @return void
	 */
	public static function install_woocommerce_payments_addon( $section = '_featured' ) {
		check_admin_referer( 'install-addon_woocommerce-payments' );

		$wcpay_plugin_id = 'woocommerce-payments';
		$wcpay_plugin    = array(
			'name'      => __( 'WooCommerce Payments', 'woocommerce' ),
			'repo-slug' => 'woocommerce-payments',
		);

		WC_Install::background_installer( $wcpay_plugin_id, $wcpay_plugin );

		do_action( 'woocommerce_addon_installed', $wcpay_plugin_id, $section );

		wp_safe_redirect( remove_query_arg( array( 'install-addon', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Should an extension be shown on the featured page.
	 *
	 * @param object $item Item data.
	 * @return boolean
	 */
	public static function show_extension( $item ) {
		$location = WC()->countries->get_base_country();
		if ( isset( $item->geowhitelist ) && ! in_array( $location, $item->geowhitelist, true ) ) {
			return false;
		}

		if ( isset( $item->geoblacklist ) && in_array( $location, $item->geoblacklist, true ) ) {
			return false;
		}

		if ( is_plugin_active( $item->plugin ) ) {
			return false;
		}

		return true;
	}

	/**
	 * We're displaying page=wc-addons and page=wc-addons&section=helper as two separate pages.
	 * When we're on those pages, add body classes to distinguishe them.
	 *
	 * @param string $admin_body_class Unfiltered body class.
	 *
	 * @return string Body class with added class for Marketplace or My Subscriptions page.
	 */
	public static function filter_admin_body_classes( string $admin_body_class = '' ): string {
		if ( isset( $_GET['section'] ) && 'helper' === $_GET['section'] ) {
			return " $admin_body_class woocommerce-page-wc-subscriptions ";
		}

		return " $admin_body_class woocommerce-page-wc-marketplace ";
	}
}
