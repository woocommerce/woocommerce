<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Jetpack;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\Admin\Features\Features;
use WP_Error;

/**
 * Jetpack Connection wrapper class.
 *
 * @since 8.3.0
 */
class JetpackConnection {
	/**
	 * Jetpack connection manager.
	 *
	 * @var Manager
	 */
	private static $manager;

	/**
	 * Get the Jetpack connection manager.
	 *
	 * @return Manager
	 */
	public static function get_manager() {
		if ( ! self::$manager instanceof Manager ) {
			self::$manager = new Manager( 'woocommerce' );
		}

		return self::$manager;
	}

	/**
	 * Get the authorization URL for the Jetpack connection.
	 *
	 * @param mixed  $redirect_url Redirect URL.
	 * @param string $from         From parameter.
	 *
	 * @return array {
	 *     Authorization data.
	 *
	 *     @type bool   $success      Whether authorization URL generation succeeded.
	 *     @type array  $errors       Array of error messages if any.
	 *     @type string $color_scheme User's admin color scheme.
	 *     @type string $url          The authorization URL.
	 * }
	 */
	public static function get_authorization_url( $redirect_url, $from = '' ) {
		$manager = self::get_manager();
		$errors  = new WP_Error();

		// Register the site to wp.com.
		if ( ! $manager->is_connected() ) {
			$result = $manager->try_registration();
			if ( is_wp_error( $result ) ) {
				$errors->add( $result->get_error_code(), $result->get_error_message() );
			}
		}

		$calypso_env = defined( 'WOOCOMMERCE_CALYPSO_ENVIRONMENT' ) && in_array( WOOCOMMERCE_CALYPSO_ENVIRONMENT, array( 'development', 'wpcalypso', 'horizon', 'stage' ), true ) ? WOOCOMMERCE_CALYPSO_ENVIRONMENT : 'production';

		$authorization_url = $manager->get_authorization_url( null, $redirect_url );
		$authorization_url = add_query_arg( 'locale', self::get_wpcom_locale(), $authorization_url );

		if ( Features::is_enabled( 'use-wp-horizon' ) ) {
			$calypso_env = 'horizon';
		}

		$color_scheme = get_user_option( 'admin_color', get_current_user_id() );
		if ( ! $color_scheme ) {
			// The default Core color schema is 'fresh'.
			$color_scheme = 'fresh';
		}

		return array(
			'success'      => ! $errors->has_errors(),
			'errors'       => $errors->get_error_messages(),
			'color_scheme' => $color_scheme,
			'url'          => add_query_arg(
				array(
					'from'        => $from,
					'calypso_env' => $calypso_env,
				),
				$authorization_url,
			),
		);
	}

	/**
	 * Return a locale string for wpcom.
	 *
	 * @return string
	 */
	private static function get_wpcom_locale() {
		// List of locales that should be used with region code.
		$locale_to_lang = array(
			'bre'   => 'br',
			'de_AT' => 'de-at',
			'de_CH' => 'de-ch',
			'de'    => 'de_formal',
			'el'    => 'el-po',
			'en_GB' => 'en-gb',
			'es_CL' => 'es-cl',
			'es_MX' => 'es-mx',
			'fr_BE' => 'fr-be',
			'fr_CA' => 'fr-ca',
			'nl_BE' => 'nl-be',
			'nl'    => 'nl_formal',
			'pt_BR' => 'pt-br',
			'sr'    => 'sr_latin',
			'zh_CN' => 'zh-cn',
			'zh_HK' => 'zh-hk',
			'zh_SG' => 'zh-sg',
			'zh_TW' => 'zh-tw',
		);

		$system_locale = get_locale();
		if ( isset( $locale_to_lang[ $system_locale ] ) ) {
			// Return the locale with region code if it's in the list.
			return $locale_to_lang[ $system_locale ];
		}

		// If the locale is not in the list, return the language code only.
		return explode( '_', $system_locale )[0];
	}
}
