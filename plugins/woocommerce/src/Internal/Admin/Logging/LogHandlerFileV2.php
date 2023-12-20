<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
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
	 *     Optional. Additional information for log handlers. Any data can be added here, but there are some array
	 *     keys that have special behavior.
	 *
	 *     @type string $source    Determines which log file to write to. Must be at least 3 characters in length.
	 *     @type bool   $backtrace True to include a backtrace that shows where the logging function got called.
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

		$entry = static::format_entry( $timestamp, $level, $message, $context );

		$written = $this->file_controller->write_to_file( $source, $entry, $timestamp );

		if ( $written ) {
			$this->file_controller->invalidate_cache();
		}

		return $written;
	}

	/**
	 * Builds a log entry text from level, timestamp, and message.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message   Log message.
	 * @param array  $context   Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	protected static function format_entry( $timestamp, $level, $message, $context ) {
		$time_string  = static::format_time( $timestamp );
		$level_string = strtoupper( $level );

		// Remove line breaks so the whole entry is on one line in the file.
		$formatted_message = str_replace( PHP_EOL, ' ', $message );

		unset( $context['source'] );
		if ( ! empty( $context ) ) {
			if ( isset( $context['backtrace'] ) && true === filter_var( $context['backtrace'], FILTER_VALIDATE_BOOLEAN ) ) {
				$context['backtrace'] = static::get_backtrace();
			}

			$formatted_context  = wp_json_encode( $context );
			$formatted_message .= " CONTEXT: $formatted_context";
		}

		$entry = "$time_string $level_string $formatted_message";

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This filter is documented in includes/abstracts/abstract-wc-log-handler.php */
		return apply_filters(
			'woocommerce_format_log_entry',
			$entry,
			array(
				'timestamp' => $timestamp,
				'level'     => $level,
				'message'   => $message,
				'context'   => $context,
			)
		);
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Figures out a source string to use for a log entry based on where the log method was called from.
	 *
	 * @return string
	 */
	protected function determine_source(): string {
		$source_roots = array(
			'mu-plugin' => trailingslashit( Constants::get_constant( 'WPMU_PLUGIN_DIR' ) ),
			'plugin'    => trailingslashit( Constants::get_constant( 'WP_PLUGIN_DIR' ) ),
			'theme'     => trailingslashit( get_theme_root() ),
		);

		$source    = '';
		$backtrace = static::get_backtrace();

		foreach ( $backtrace as $frame ) {
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

		$files = $this->file_controller->get_files(
			array(
				'date_filter' => 'created',
				'date_start'  => 1,
				'date_end'    => $timestamp,
			)
		);

		if ( is_wp_error( $files ) ) {
			return 0;
		}

		$file_ids = array_map(
			fn( $file ) => $file->get_file_id(),
			$files
		);

		$deleted = $this->file_controller->delete_files( $file_ids );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This filter is documented in includes/class-wc-logger.php. */
		$retention_days = absint( apply_filters( 'woocommerce_logger_days_to_retain_logs', 30 ) );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment

		if ( $deleted > 0 ) {
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
					'source' => 'wc_logger',
				)
			);
		}

		return $deleted;
	}
}
