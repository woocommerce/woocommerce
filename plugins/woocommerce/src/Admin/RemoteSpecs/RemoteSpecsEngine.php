<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs;

/**
 * RemoteSpecsEngine class.
 */
abstract class RemoteSpecsEngine {
	/**
	 * Log errors.
	 *
	 * @param array $errors Array of errors from \Throwable interface.
	 */
	public static function log_errors( $errors = array() ) {
		if (
		true !== defined( 'WP_ENVIRONMENT_TYPE' ) ||
		! in_array( constant( 'WP_ENVIRONMENT_TYPE' ), array( 'development', 'local' ), true )
		) {
			return;
		}
		$logger         = wc_get_logger();
		$error_messages = array();

		foreach ( $errors as $error ) {
			if ( isset( $error ) && method_exists( $error, 'getMessage' ) ) {
				$error_messages[] = $error->getMessage();
			}
		}

		$logger->error(
			'Error while evaluating specs',
			array(
				'source' => 'remotespecsengine-errors',
				'class'  => static::class,
				'errors' => $error_messages,
			),
		);
	}
}
