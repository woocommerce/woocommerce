<?php
/**
 * ThemeSupport class file.
 *
 * @package WooCommerce\Theming
 */

namespace Automattic\WooCommerce\Theming;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utils\ArrayUtils;

/**
 * Provides methods for theme support.
 *
 * @package Automattic\WooCommerce\Theming
 */
class ThemeSupport {

	const DEFAULTS_KEY = '_defaults';

	/**
	 * The instance of LegacyProxy to use.
	 *
	 * @var LegacyProxy
	 */
	private $legacy_proxy;

	/**
	 * ThemeSupport constructor.
	 *
	 * @param LegacyProxy $legacy_proxy The instance of LegacyProxy to use.
	 */
	public function __construct( LegacyProxy $legacy_proxy ) {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Adds theme support options for the current theme.
	 *
	 * @param array $options The options to be added.
	 */
	public function add_options( $options ) {
		$this->legacy_proxy->call_function( 'add_theme_support', 'woocommerce', $options );
	}

	/**
	 * Adds default theme support options for the current theme.
	 *
	 * @param array $options The options to be added.
	 */
	public function add_default_options( $options ) {
		$default_options = $this->get_option( self::DEFAULTS_KEY, array() );
		$default_options = array_merge( $default_options, $options );
		$this->add_options( array( self::DEFAULTS_KEY => $default_options ) );
	}

	/**
	 * Gets "theme support" options from the current theme, if set.
	 *
	 * @param string $option_name Option name, possibly nested (key::subkey), to get specific value. Blank to get all the existing options as an array.
	 * @param mixed  $default_value Value to return if the specified option doesn't exist.
	 * @return mixed The retrieved option or the default value.
	 */
	public function get_option( $option_name = '', $default_value = null ) {
		$theme_support_options = $this->get_all_options();

		if ( ! $theme_support_options ) {
			return $default_value;
		}

		if ( $option_name ) {
			$value = ArrayUtils::get_nested_value( $theme_support_options, $option_name );
			if ( is_null( $value ) ) {
				$value = ArrayUtils::get_nested_value( $theme_support_options, self::DEFAULTS_KEY . '::' . $option_name, $default_value );
			}
			return $value;
		}

		return $theme_support_options;
	}

	/**
	 * Checks whether a given theme support option has been defined.
	 *
	 * @param string $option_name The (possibly nested) name of the option to check.
	 * @param bool   $include_defaults True to include the default values in the check, false otherwise.
	 *
	 * @return bool True if the specified theme support option has been defined, false otherwise.
	 */
	public function has_option( $option_name, $include_defaults = true ) {
		$theme_support_options = $this->get_all_options();

		if ( ! $theme_support_options ) {
			return false;
		}

		$value = ArrayUtils::get_nested_value( $theme_support_options, $option_name );
		if ( ! is_null( $value ) ) {
			return true;
		}

		if ( ! $include_defaults ) {
			return false;
		}
		$value = ArrayUtils::get_nested_value( $theme_support_options, self::DEFAULTS_KEY . '::' . $option_name );
		return ! is_null( $value );
	}

	/**
	 * Get all the defined theme support options for the 'woocommerce' feature.
	 *
	 * @return array An array with all the theme support options defined for the 'woocommerce' feature, or false if nothing has been defined for that feature.
	 */
	private function get_all_options() {
		$theme_support = $this->legacy_proxy->call_function( 'get_theme_support', 'woocommerce' );
		return is_array( $theme_support ) ? $theme_support[0] : false;
	}
}
