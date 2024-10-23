<?php

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use WC_Tracks;

class RemovedDeprecated {
    /**
     * Handle deprecated method calls.
     *
     * @param string $class The class name.
     * @param string $name The name of the deprecated method.
     */
	private static function handle_deprecated_method_call( $class, $name ) {
		$logger = wc_get_logger();
		
		if ( $logger ) {
			$logger->warning(
				"$class is deprecated since 9.3 with no alternative. Please remove the call to $name."
			);
		}

		if ( class_exists( 'WC_Tracks' ) ) {
			WC_Tracks::record_event( 'deprecated_navigation_method_called' );
		}
	}

	/**
	 * Handle calls to deprecated methods.
	 *
	 * @param string $name The name of the deprecated method.
	 * @param array $arguments The arguments passed to the deprecated method.
	 */
	public function __call( $name, $arguments ) {
		self::handle_deprecated_method_call( get_called_class(), $name );
	}

	/**
	 * Handle static calls to deprecated methods.
	 *
	 * @param string $name The name of the deprecated method.
	 * @param array $arguments The arguments passed to the deprecated method.
	 */
	public static function __callStatic( $name, $arguments ) {
		self::handle_deprecated_method_call( get_called_class(), $name );
	}
}

