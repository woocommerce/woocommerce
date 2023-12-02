<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use WC_Log_Handler;

/**
 * LogHandlerFileV2 class.
 */
class LogHandlerFileV2 extends WC_Log_Handler {
	/**
	 * Instance of the FileController class.
	 *
	 * @var FileController
	 */
	private $file_controller;

	/**
	 * LogHandlerFileV2 class.
	 */
	public function __construct() {
		$this->file_controller = wc_get_container()->get( FileController::class );
	}

	/**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message   Log message.
	 * @param array  $context   {
	 *     Optional. Additional information for log handlers.
	 *
	 *     @type string $source Optional. Determines which log file to write to.
	 * }
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {
		if ( isset( $context['source'] ) && is_string( $context['source'] ) && strlen( $context['source'] ) >= 3 ) {
			$source = sanitize_title( trim( $context['source'] ) );
		} else {
			$source = $this->determine_source();
		}

		$entry = self::format_entry( $timestamp, $level, $message, $context );

		$written = $this->file_controller->write_to_file( $source, $entry );

		if ( $written ) {
			$this->file_controller->invalidate_cache();
		}

		return $written;
	}

	/**
	 * Figures out a source string to use for a log entry based on where the log method was called from.
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	protected function determine_source(): string {
		// Get the filename of the current logger class.
		$reflector    = new \ReflectionClass( wc_get_logger() );
		$logger_file  = $reflector->getFileName();
		$ignore_files = array( __FILE__, $logger_file );

		$source_roots = array(
			'mu-plugin' => trailingslashit( Constants::get_constant( 'WPMU_PLUGIN_DIR' ) ),
			'plugin'    => trailingslashit( Constants::get_constant( 'WP_PLUGIN_DIR' ) ),
			'theme'     => trailingslashit( get_theme_root() ),
		);
		$source       = '';

		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		foreach ( $backtrace as $frame ) {
			if ( ! isset( $frame['file'] ) || in_array( $frame['file'], $ignore_files, true ) ) {
				continue;
			}

			foreach ( $source_roots as $type => $path ) {
				if ( 0 === strpos( $frame['file'], $path ) ) {
					$relative_path = trim( substr( $frame['file'], strlen( $path ) ), DIRECTORY_SEPARATOR );

					if ( 'mu-plugin' === $type ) {
						$info = pathinfo( $relative_path );

						if ( '.' === $info['dirname'] ) {
							$source = "$type-" . $info['filename'];
						} else {
							$source = "$type-" . $info['dirname'];
						}

						break 2;
					}

					$segments = explode( DIRECTORY_SEPARATOR, $relative_path );
					if ( is_array( $segments ) ) {
						$source = "$type-" . reset( $segments );
					}

					break 2;
				}
			}
		}

		if ( ! $source ) {
			$source = 'log';
		}

		return sanitize_title( $source );
	}

	/**
	 * Delete all logs older than a specified timestamp.
	 *
	 * @param int $timestamp All files created before this timestamp will be deleted.
	 *
	 * @return int The number of files that were deleted.
	 */
	public function delete_logs_before_timestamp( int $timestamp = 0 ): int {
		if ( ! $timestamp ) {
			return 0;
		}

		$files = $this->file_controller->get_files( array(
			'date_filter' => 'created',
			'date_start'  => 1,
			'date_end'    => $timestamp,
		) );

		if ( is_wp_error( $files ) ) {
			return 0;
		}

		$file_ids = array_map(
			fn( $file ) => $file->get_file_id(),
			$files
		);

		$deleted = $this->file_controller->delete_files( $file_ids );

		/** This filter is documented in includes/class-wc-logger.php. */
		$retention_days = absint( apply_filters( 'woocommerce_logger_days_to_retain_logs', 30 ) );

		$this->handle(
			time(),
			'info',
			sprintf(
				'%s %s',
				sprintf(
					esc_html(
						// translators: %s is a number of log files.
						_n(
							'%s expired log file was deleted.',
							'%s expired log files were deleted.',
							$deleted,
							'woocommerce'
						)
					),
					number_format_i18n( $deleted )
				),
				sprintf(
					esc_html(
						// translators: %s is a number of days.
						_n(
							'The retention period for log files is %s day.',
							'The retention period for log files is %s days.',
							$retention_days,
							'woocommerce'
						)
					),
					number_format_i18n( $retention_days )
				)
			),
			array(
				'source' => 'wc_logger'
			)
		);

		return $deleted;
	}
}
