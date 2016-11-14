<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Log_Handler' ) ) {
	include_once( dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-log-handler.php' );
}

/**
 * Handles log entries by writing to a file.
 *
 * @class          WC_Log_Handler_File
 * @version        1.0.0
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
class WC_Log_Handler_File extends WC_Log_Handler {

	/**
	 * Stores open file handles.
	 *
	 * @var array
	 * @access private
	 */
	private $_handles = array();

	/**
	 * Destructor.
	 */
	public function __destruct() {
		foreach ( $this->_handles as $handle ) {
			if ( is_resource( $handle ) ) {
				fclose( $handle );
			}
		}
	}

	/**
	 * Handle a log entry.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message
	 * @param array $context {
	 *     Optional. Array with additional information
	 *
	 *     @type string $tag Optional. The tag will be used to determine which file an entry will be written to.
	 * }
	 *
	 * @return bool log entry should bubble to further loggers.
	 */
	public function handle( $level, $timestamp, $message, $context ) {

		if ( ! $this->should_handle( $level ) ) {
			return true;
		}

		if ( isset( $context['tag'] ) && $context['tag'] ) {
			$handle = $context['tag'];
		} else {
			$handle = 'log';
		}

		$entry = $this->format_entry( $level, $timestamp, $message, $context );

		// Bubble if add is NOT successful
		return ! $this->add( $entry, $handle );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * Attempt to ensure backwards compatibility for legacy WC_Logger::add calls
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param int $timestamp log entry timestamp
	 * @param string $message provided log message
	 * @param array $context {
	 *     Optional. Array with additional information
	 *
	 *     @type string $tag Optional. The tag will be used to determine which file an entry will be written to.
	 * }
	 *
	 * @return string Formatted log entry
	 */
	public function format_entry( $level, $timestamp, $message, $context ) {

		if ( isset( $context['_legacy'] ) && true === $context['_legacy'] ) {
			if ( isset( $context['tag'] ) && $context['tag'] ) {
				$handle = $context['tag'];
			} else {
				$handle = 'log';
			}
			$message = apply_filters( 'woocommerce_logger_add_message', $message, $handle );
			$time = date_i18n( 'm-d-Y @ H:i:s -' );
			$entry = "{$time} {$message}";
		} else {
			$entry = parent::format_entry( $level, $timestamp, $message, $context );
		}

		return $entry;
	}

	/**
	 * Open log file for writing.
	 *
	 * @param string $handle
	 * @param string $mode
	 * @return bool success
	 */
	protected function open( $handle, $mode = 'a' ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		if ( ! file_exists( wc_get_log_file_path( $handle ) ) ) {
			$temphandle = @fopen( wc_get_log_file_path( $handle ), 'w+' );
			@fclose( $temphandle );

			if ( defined( 'FS_CHMOD_FILE' ) ) {
				@chmod( wc_get_log_file_path( $handle ), FS_CHMOD_FILE );
			}
		}

		if ( $this->_handles[ $handle ] = @fopen( wc_get_log_file_path( $handle ), $mode ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Close a handle.
	 *
	 * @param string $handle
	 * @return bool success
	 */
	protected function close( $handle ) {
		$result = false;

		if ( is_resource( $this->_handles[ $handle ] ) ) {
			$result = fclose( $this->_handles[ $handle ] );
			unset( $this->_handles[ $handle ] );
		}

		return $result;
	}

	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $handle Log entry handle
	 * @param string $entry Log entry text
	 *
	 * @return bool True if write was successful.
	 */
	public function add( $entry, $handle ) {
		$result = false;

		if ( $this->open( $handle ) && is_resource( $this->_handles[ $handle ] ) ) {
			$result = fwrite( $this->_handles[ $handle ], $entry . "\n" );
		}

		return false !== $result;
	}

	/**
	 * Clear entries from chosen file.
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	public function clear( $handle ) {
		$result = false;

		// Close the file if it's already open.
		$this->close( $handle );

		/**
		 * $this->open( $handle, 'w' ) == Open the file for writing only. Place the file pointer at
		 * the beginning of the file, and truncate the file to zero length.
		 */
		if ( $this->open( $handle, 'w' ) && is_resource( $this->_handles[ $handle ] ) ) {
			$result = true;
		}

		do_action( 'woocommerce_log_clear', $handle );

		return $result;
	}

	/**
	 * Remove/delete the chosen file.
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	public function remove( $handle ) {
		$removed = false;
		$file    = wc_get_log_file_path( $handle );

		if ( is_file( $file ) && is_writable( $file ) ) {
			// Close first to be certain no processes keep it alive after it is unlinked.
			$this->close( $handle );
			$removed = unlink( $file );
		} elseif ( is_file( trailingslashit( WC_LOG_DIR ) . $handle . '.log' ) && is_writable( trailingslashit( WC_LOG_DIR ) . $handle . '.log' ) ) {
			$this->close( $handle );
			$removed = unlink( trailingslashit( WC_LOG_DIR ) . $handle . '.log' );
		}

		do_action( 'woocommerce_log_remove', $handle, $removed );

		return $removed;
	}
}
