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
 * @package        WooCommerce/Classes/Log_Handlers
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
	private $handles = array();

	/**
	 * File size limit for log files in bytes.
	 *
	 * @var int
	 * @access private
	 */
	private $log_size_limit;

	/**
	 * Constructor for the logger.
	 *
	 * @param $args additional args. {
	 *     Optional. @see WC_Log_Handler::__construct.
	 *
	 *     @type int $log_size_limit Optional. Size limit for log files. Default 5mb.
	 * }
	 */
	public function __construct( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'log_size_limit' => 5 * 1024 * 1024,
		) );

		parent::__construct( $args );

		$this->log_size_limit = $args['log_size_limit'];
	}

	/**
	 * Destructor.
	 *
	 * Cleans up open file handles.
	 */
	public function __destruct() {
		foreach ( $this->handles as $handle ) {
			if ( is_resource( $handle ) ) {
				fclose( $handle );
			}
		}
	}

	/**
	 * Handle a log entry.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context {
	 *     Additional information for log handlers.
	 *
	 *     @type string $tag Optional. Determines log file to write to. Default 'log'.
	 * }
	 *
	 * @return bool Log entry should bubble to further loggers.
	 */
	public function handle( $timestamp, $level, $message, $context ) {

		if ( ! $this->should_handle( $level ) ) {
			return true;
		}

		if ( isset( $context['tag'] ) && $context['tag'] ) {
			$handle = $context['tag'];
		} else {
			$handle = 'log';
		}

		$entry = $this->format_entry( $timestamp, $level, $message, $context );

		// Bubble if add is NOT successful
		return ! $this->add( $entry, $handle );
	}

	/**
	 * Builds a log entry text from timestamp, level and message.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	public function format_entry( $timestamp, $level, $message, $context ) {

		if ( isset( $context['_legacy'] ) && true === $context['_legacy'] ) {
			if ( isset( $context['tag'] ) && $context['tag'] ) {
				$handle = $context['tag'];
			} else {
				$handle = 'log';
			}
			$message = apply_filters( 'woocommerce_logger_add_message', $message, $handle );
			$time = date_i18n( 'm-d-Y @ H:i:s' );
			$entry = "{$time} - {$message}";
		} else {
			$entry = parent::format_entry( $timestamp, $level, $message, $context );
		}

		return $entry;
	}

	/**
	 * Open log file for writing.
	 *
	 * @param string $handle Log handle.
	 * @param string $mode Optional. File mode. Default 'a'.
	 * @return bool Success.
	 */
	protected function open( $handle, $mode = 'a' ) {
		if ( $this->is_open( $handle ) ) {
			return true;
		}

		if ( ! file_exists( wc_get_log_file_path( $handle ) ) ) {
			$temphandle = @fopen( wc_get_log_file_path( $handle ), 'w+' );
			@fclose( $temphandle );

			if ( defined( 'FS_CHMOD_FILE' ) ) {
				@chmod( wc_get_log_file_path( $handle ), FS_CHMOD_FILE );
			}
		}

		if ( $this->handles[ $handle ] = @fopen( wc_get_log_file_path( $handle ), $mode ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a handle is open.
	 *
	 * @param string $handle Log handle.
	 * @return bool True if $handle is open.
	 */
	protected function is_open( $handle ) {
		return array_key_exists( $handle, $this->handles );
	}

	/**
	 * Close a handle.
	 *
	 * @param string $handle
	 * @return bool success
	 */
	protected function close( $handle ) {
		$result = false;

		if ( $this->is_open( $handle ) && is_resource( $this->handles[ $handle ] ) ) {
			$result = fclose( $this->handles[ $handle ] );
			unset( $this->handles[ $handle ] );
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

		if ( $this->should_rotate( $handle ) ) {
			$this->log_rotate( $handle );
		}

		if ( $this->open( $handle ) && is_resource( $this->handles[ $handle ] ) ) {
			$result = fwrite( $this->handles[ $handle ], $entry . PHP_EOL );
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
		if ( $this->open( $handle, 'w' ) && is_resource( $this->handles[ $handle ] ) ) {
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

	/**
	 * Check if log file should be rotated.
	 *
	 * Compares the size of the log file to determine whether it is over the size limit.
	 *
	 * @param string $handle Log handle
	 * @return bool True if if should be rotated.
	 */
	protected function should_rotate( $handle ) {
		$file = wc_get_log_file_path( $handle );
		if ( $this->is_open( $handle ) ) {
			$file_stat = fstat( $this->handles[ $handle ] );
			return $file_stat['size'] > $this->log_size_limit;
		} elseif ( file_exists( $file ) ) {
			return filesize( $file ) > $this->log_size_limit;
		} else {
			return false;
		}
	}

	/**
	 * Rotate log files.
	 *
	 * Logs are rotatated by prepending '.x' to the '.log' suffix.
	 * The current log plus 10 historical logs are maintained.
	 * For example:
	 *     base.9.log -> [ REMOVED ]
	 *     base.8.log -> base.9.log
	 *     ...
	 *     base.0.log -> base.1.log
	 *     base.log   -> base.0.log
	 *
	 * @param string $handle Log handle
	 */
	protected function log_rotate( $handle ) {
		for ( $i = 8; $i >= 0; $i-- ) {
			$this->increment_log_infix( $handle, $i );
		}
		$this->increment_log_infix( $handle );
	}

	/**
	 * Increment a log file suffix.
	 *
	 * @param string $handle Log handle
	 * @param null|int $number Optional. Default null. Log suffix number to be incremented.
	 * @return bool True if increment was successful, otherwise false.
	 */
	protected function increment_log_infix( $handle, $number = null ) {
		if ( null === $number ) {
			$suffix = '';
			$next_suffix = '.0';
		} else {
			$suffix = '.' . $number;
			$next_suffix = '.' . ($number + 1);
		}

		$rename_from = wc_get_log_file_path( "{$handle}{$suffix}" );
		$rename_to = wc_get_log_file_path( "{$handle}{$next_suffix}" );

		if ( $this->is_open( $rename_from ) ) {
			$this->close( $rename_from );
		}

		if ( is_writable( $rename_from ) ) {
			return rename( $rename_from, $rename_to );
		} else {
			return false;
		}

	}

}
