<?php

namespace Automattic\WooCommerce\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Class VersionUtil
 *
 * @since x.x.x
 */
class VersionUtil {

	/** @var LegacyProxy */
	protected $proxy;

	/**
	 * Init this class instance.
	 *
	 * @since x.x.x
	 *
	 * @param LegacyProxy $proxy
	 *
	 * @return void
	 */
	final public function init( LegacyProxy $proxy ) {
		$this->proxy = $proxy;
	}

	/**
	 * Check if the current WordPress version is at least the given version.
	 *
	 * @since x.x.x
	 *
	 * @param string $version                The version to check against.
	 * @param bool   $use_unmodified_version Whether to use the unmodified WordPress version from
	 *                                       the wp-includes/version.php file.
	 *
	 * @return bool
	 */
	public function wp_version_at_least( string $version, bool $use_unmodified_version = false ): bool {
		if ( $use_unmodified_version ) {
			$wp_version = $this->get_unmodified_wp_version();
		} else {
			$wp_version = $this->proxy->get_global( 'wp_version' );
		}

		return version_compare( $wp_version, $version, '>=' );
	}

	/**
	 * Get the unmodified WordPress version from the wp-includes/version.php file.
	 *
	 * @since x.x.x
	 * @return string
	 */
	private function get_unmodified_wp_version() {
		$abspath = Constants::get_constant( 'ABSPATH' );
		$wpinc   = Constants::get_constant( 'WPINC' );

		include "{$abspath}{$wpinc}/version.php";

		// Possibly strip the hyphen and any text after it from the version.
		$hyphen_position = strpos( $wp_version, '-' );
		if ( false !== $hyphen_position ) {
			$wp_version = substr( $wp_version, 0, $hyphen_position );
		}

		return $wp_version;
	}
}
