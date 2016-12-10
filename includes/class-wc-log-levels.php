<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Standard log levels
 *
 * @class          WC_Log_Levels
 * @version        2.0.0
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
abstract class WC_Log_Levels {

	/**
	 * Log Levels
	 *
	 * Description of levels:
	 *     'emergency': System is unusable.
	 *     'alert': Action must be taken immediately.
	 *     'critical': Critical conditions.
	 *     'error': Error conditions.
	 *     'warning': Warning conditions.
	 *     'notice': Normal but significant condition.
	 *     'informational': Informational messages.
	 *     'debug': Debug-level messages.
	 *
	 * @see @link {https://tools.ietf.org/html/rfc5424}
	 */
	const DEBUG     = 'debug';
	const INFO      = 'info';
	const NOTICE    = 'notice';
	const WARNING   = 'warning';
	const ERROR     = 'error';
	const CRITICAL  = 'critical';
	const ALERT     = 'alert';
	const EMERGENCY = 'emergency';

	/**
	 * Level strings mapped to integer severity.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $level_severity = array(
		self::DEBUG     => 100,
		self::INFO      => 200,
		self::NOTICE    => 300,
		self::WARNING   => 400,
		self::ERROR     => 500,
		self::CRITICAL  => 600,
		self::ALERT     => 700,
		self::EMERGENCY => 800,
	);

	/**
	 * Validate a level string.
	 *
	 * @param string $level
	 * @return bool True if $level is a valid level.
	 */
	public static function is_valid_level( $level ) {
		return array_key_exists( $level, self::$level_severity );
	}

	/**
	 * Translate level string to integer.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return int 100 (debug) - 800 (emergency) or 0 if not recognized
	 */
	public static function get_level_severity( $level ) {
		if ( self::is_valid_level( $level ) ) {
			$severity = self::$level_severity[ $level ];
		} else {
			$severity = 0;
		}
		return $severity;
	}

}
