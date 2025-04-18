<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use WC_Log_Levels;
use WC_Logger_Interface;

/**
 * Class Logger
 */
class Logger {
	use UseWPFunctions;

	/**
	 * WooCommerce logger class instance.
	 *
	 * @var WC_Logger_Interface
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = wc_get_logger();
	}

	/**
	 * Log a message as a debug log entry.
	 *
	 * @param string $message The message to log.
	 * @param string $level   The log level.
	 * @param array  $context The context of the log.
	 */
	public function log( string $message, string $level = WC_Log_Levels::DEBUG, $context = array() ) {
		$this->logger->log(
			$level,
			$message,
			array_merge(
				array(
					'source'  => 'wc-blueprint',
					'user_id' => $this->wp_get_current_user_id(),
				),
				$context
			)
		);
	}
}
