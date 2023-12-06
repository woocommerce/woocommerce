<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use WC_Log_Handler_File;

/**
 * LogHandlerFileV2 class.
 */
class LogHandlerFileV2 extends WC_Log_Handler_File {
	/**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context {
	 *      Additional information for log handlers.
	 *
	 *     @type string $source Optional. Determines log file to write to. Default 'log'.
	 *     @type bool $_legacy Optional. Default false. True to use outdated log format
	 *         originally used in deprecated WC_Logger::add calls.
	 * }
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {
		$written = parent::handle( $timestamp, $level, $message, $context );

		if ( $written ) {
			wc_get_container()->get( FileController::class )->invalidate_cache();
		}

		return $written;
	}
}
