<?php
/**
 * Addons Page
 *
 * @package  WooCommerce\Admin
 * @version  2.5.0
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications as PromotionRuleEngine;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Addons Class.
 */
class WC_Admin_Addons {

	/**
	 * Fetch featured products from WCCOM's the Featured 2.0 Endpoint and cache the data for a day.
	 *
	 * @return array|WP_Error
	 */
	public static function fetch_featured() {
		$transient_name = 'wc_addons_featured';
		// Important: WCCOM Extensions API v2.0 is used.
		$url      = 'https://woocommerce.com/wp-json/wccom-extensions/2.0/featured';
		$locale   = get_user_locale();
		$featured = self::get_locale_data_from_transient( $transient_name, $locale );

		if ( false === $featured ) {
			$fetch_options = array(
				'auth'    => true,
				'locale'  => true,
				'country' => true,
			);
			$raw_featured  = self::fetch( $url, $fetch_options );

			if ( is_wp_error( $raw_featured ) ) {
				do_action( 'woocommerce_page_wc-addons_connection_error', $raw_featured->get_error_message() );

				$message = self::is_ssl_error( $raw_featured->get_error_message() )
					? __( 'We encountered an SSL error. Please ensure your site supports TLS version 1.2 or above.', 'woocommerce' )
					: $raw_featured->get_error_message();

				return new WP_Error( 'wc-addons-connection-error', $message );
			}

			$response_code = (int) wp_remote_retrieve_response_code( $raw_featured );
			if ( 200 !== $response_code ) {
				do_action( 'woocommerce_page_wc-addons_connection_error', $response_code );

				/* translators: %d: HTTP error code. */
				$message = sprintf(
					esc_html(
						/* translators: Error code  */
						__(
							'Our request to the featured API got error code %d.',
							'woocommerce'
						)
					),
					$response_code
				);

				return new WP_Error( 'wc-addons-connection-error', $message );
			}

			$featured = json_decode( wp_remote_retrieve_body( $raw_featured ) );
			if ( empty( $featured ) || ! is_array( $featured ) ) {
				do_action( 'woocommerce_page_wc-addons_connection_error', 'Empty or malformed response' );
				$message = __( 'Our request to the featured API got a malformed response.', 'woocommerce' );

				return new WP_Error( 'wc-addons-connection-error', $message );
			}

			if ( $featured ) {
				self::set_locale_data_in_transient( $transient_name, $featured, $locale, DAY_IN_SECONDS );
			}
		}

		return $featured;
	}

	/**
	 * Check if the error is due to an SSL error
	 *
	 * @param string $error_message Error message.
	 *
	 * @return bool True if SSL error, false otherwise
	 */
	public static function is_ssl_error( $error_message ) {
		return false !== stripos( $error_message, 'cURL error 35' );
	}

	/**
	 * Get sections for the addons screen
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		$locale         = get_user_locale();
		$addon_sections = self::get_locale_data_from_transient( 'wc_addons_sections', $locale );
		if ( false === ( $addon_sections ) ) {
			$parameter_string = '?' . http_build_query( array( 'locale' => get_user_locale() ) );
			$raw_sections     = wp_safe_remote_get(
				'https://woocommerce.com/wp-json/wccom-extensions/1.0/categories' . $parameter_string,
				array(
					'user-agent' => 'WooCommerce/' . WC()->version . '; ' . get_bloginfo( 'url' ),
				)
			);
			if ( ! is_wp_error( $raw_sections ) ) {
				$addon_sections = json_decode( wp_remote_retrieve_body( $raw_sections ) );
				if ( $addon_sections ) {
					self::set_locale_data_in_transient( 'wc_addons_sections', $addon_sections, $locale, WEEK_IN_SECONDS );
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
	 * Process requests to legacy marketplace menu and redirect to correct in-app pages.
	 *
	 * @return void
	 */
	public static function handle_legacy_marketplace_redirects() {
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_featured';
		$search  = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		if ( 'helper' === $section ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&tab=my-subscriptions&path=%2Fextensions' ) );
			exit();
		}

		if ( 'search' === $section ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&term=' . $search . '&tab=search&path=%2Fextensions' ) );
			exit();
		}

		$sections         = self::get_sections();
		$allowed_sections = array_map( fn( $section_object ) => $section_object->slug, $sections );
		// Validate if the category is supported.
		$section = in_array( $section, $allowed_sections, true ) ? $section : '_featured';

		if ( '_featured' === $section ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=%2Fextensions' ) );
			exit();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&category=' . $section ) );
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

	/**
	 * Take an action object and return the URL based on properties of the action.
	 *
	 * @param object $action Action object.
	 * @return string URL.
	 */
	public static function get_action_url( $action ): string {
		if ( ! isset( $action->url ) ) {
			return '';
		}

		if ( isset( $action->url_is_admin_query ) && $action->url_is_admin_query ) {
			return wc_admin_url( $action->url );
		}

		if ( isset( $action->url_is_admin_nonce_query ) && $action->url_is_admin_nonce_query ) {
			if ( empty( $action->nonce ) ) {
				return '';
			}
			return wp_nonce_url(
				admin_url( $action->url ),
				$action->nonce
			);
		}

		return $action->url;
	}

	/**
	 * Retrieves the locale data from a transient.
	 *
	 * Transient value is an array of locale data in the following format:
	 * array(
	 *    'en_US' => ...,
	 *    'fr_FR' => ...,
	 * )
	 *
	 * If the transient does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @param string $locale  Locale to retrieve.
	 * @return mixed Value of transient.
	 */
	private static function get_locale_data_from_transient( $transient, $locale ) {
		$transient_value = get_transient( $transient );
		$transient_value = is_array( $transient_value ) ? $transient_value : array();
		return $transient_value[ $locale ] ?? false;
	}

	/**
	 * Sets the locale data in a transient.
	 *
	 * Transient value is an array of locale data in the following format:
	 * array(
	 *    'en_US' => ...,
	 *    'fr_FR' => ...,
	 * )
	 *
	 * @param string $transient  Transient name. Expected to not be SQL-escaped.
	 *                           Must be 172 characters or fewer in length.
	 * @param mixed  $value      Transient value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param string $locale  Locale to set.
	 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
	 * @return bool True if the value was set, false otherwise.
	 */
	private static function set_locale_data_in_transient( $transient, $value, $locale, $expiration = 0 ) {
		$transient_value            = get_transient( $transient );
		$transient_value            = is_array( $transient_value ) ? $transient_value : array();
		$transient_value[ $locale ] = $value;
		return set_transient( $transient, $transient_value, $expiration );
	}

	/**
	 * Make wp_safe_remote_get request to WooCommerce.com endpoint.
	 * Optionally pass user auth token, locale or country.
	 *
	 * @param string $url     URL to request.
	 * @param ?array $options Options for the request. For example, to pass auth token, locale and country,
	 *                        pass array( 'auth' => true, 'locale' => true, 'country' => true, ).
	 *
	 * @return array|WP_Error
	 */
	public static function fetch( $url, $options = array() ) {
		$headers = array();

		if ( isset( $options['auth'] ) && $options['auth'] ) {
			$auth = WC_Helper_Options::get( 'auth' );

			if ( isset( $auth['access_token'] ) && ! empty( $auth['access_token'] ) ) {
				$headers['Authorization'] = 'Bearer ' . $auth['access_token'];
			}
		}

		$parameters = array();

		if ( isset( $options['locale'] ) && $options['locale'] ) {
			$parameters['locale'] = get_user_locale();
		}

		if ( isset( $options['country'] ) && $options['country'] ) {
			$country = WC()->countries->get_base_country();
			if ( ! empty( $country ) ) {
				$parameters['country'] = $country;
			}
		}

		$query_string = ! empty( $parameters ) ? '?' . http_build_query( $parameters ) : '';

		return wp_safe_remote_get(
			$url . $query_string,
			array(
				'headers'    => $headers,
				'user-agent' => 'WooCommerce/' . WC()->version . '; ' . get_bloginfo( 'url' ),
			)
		);
	}
}
