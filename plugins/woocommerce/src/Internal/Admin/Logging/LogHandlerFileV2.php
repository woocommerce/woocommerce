<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ File, FileController };
use WC_Log_Handler;

/**
 * LogHandlerFileV2 class.
 */
class LogHandlerFileV2 extends WC_Log_Handler {
	/**
	 * Maximum number of log files to delete in one loop iteration.
	 *
	 * @var int
	 */
	private const DELETE_BATCH_SIZE = 100;

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
		$context = (array) $context;

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

		if ( isset( $context['backtrace'] ) && true === filter_var( $context['backtrace'], FILTER_VALIDATE_BOOLEAN ) ) {
			$context['backtrace'] = static::get_backtrace();
		}

		$context_for_entry = $context;
		unset( $context_for_entry['source'] );

		if ( ! empty( $context_for_entry ) ) {
			// Keep the JSON flags in sync with the context re-encoding in PageController::format_line().
			$formatted_context = wp_json_encode( $context_for_entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			$message          .= " CONTEXT: $formatted_context";
		}

		$entry = "$time_string $level_string $message";

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
			if ( ! isset( $frame['file'] ) ) {
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
	 * Delete all logs from a specific source.
	 *
	 * @param string $source The source of the log entries.
	 * @param bool   $quiet  Whether to suppress the deletion message.
	 *
	 * @return int The number of files that were deleted.
	 */
	public function clear( string $source, bool $quiet = false ): int {
		$source = File::sanitize_source( $source );

		// Bail on an empty source: an empty value would match every file and,
		// combined with the batched deletion below, wipe out all log files.
		if ( '' === $source ) {
			return 0;
		}

		$deleted = 0;
		$skipped = 0;

		/*
		 * Fetch and delete in batches so that sources with more than the default
		 * per-page of log files don't leave files behind.
		 *
		 * Order by 'created' rather than the default 'modified'. Because paging
		 * advances $skipped past undeletable files, the offset is only reliable if
		 * get_files() returns a stable, strict total order across iterations. For a
		 * single source, created timestamps (plus rotation) are unique per file,
		 * whereas modified times can tie -- and on PHP < 8.0 usort() is not stable,
		 * so tied files could re-order between iterations and strand a deletable file.
		 */
		do {
			$files = $this->file_controller->get_files(
				array(
					'source'       => $source,
					'exact_source' => true,
					'orderby'      => 'created',
					'per_page'     => self::DELETE_BATCH_SIZE,
					'offset'       => $skipped,
				)
			);

			if ( is_wp_error( $files ) || ! is_array( $files ) ) {
				break;
			}

			$fetched_count = count( $files );
			if ( $fetched_count < 1 ) {
				break;
			}

			$file_ids = array_map(
				fn( $file ) => $file->get_file_id(),
				$files
			);

			$deleted_in_batch = $this->file_controller->delete_files( $file_ids );
			$deleted         += $deleted_in_batch;

			// Deleted files disappear from the directory, so only files that could
			// not be deleted need to be skipped. This avoids retrying a permanently
			// undeletable batch forever.
			$skipped += $fetched_count - $deleted_in_batch;
		} while ( self::DELETE_BATCH_SIZE === $fetched_count );

		if ( $deleted > 0 && ! $quiet ) {
			$this->handle(
				time(),
				'info',
				sprintf(
					esc_html(
						// translators: %1$s is a number of log files, %2$s is a slug-style name for a file.
						_n(
							'%1$s log file from source %2$s was deleted.',
							'%1$s log files from source %2$s were deleted.',
							$deleted,
							'woocommerce'
						)
					),
					number_format_i18n( $deleted ),
					sprintf(
						'<code>%s</code>',
						esc_html( $source )
					)
				),
				array(
					'source'    => 'wc_logger',
					'backtrace' => true,
				)
			);
		}

		return $deleted;
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

		$deleted = 0;
		$skipped = 0;

		/*
		 * Fetch and delete in batches so that sites with more than the default
		 * per-page of log files don't leave expired files behind.
		 *
		 * Order by 'created' so that paging past vetoed files stays reliable: the
		 * offset only lands correctly if get_files() returns a strict, stable total
		 * order across iterations, and (created, source, rotation) is unique per file
		 * whereas the default 'modified' order can tie -- which PHP < 8.0's unstable
		 * usort() could re-order between iterations, stranding an expired file.
		 */
		do {
			$files = $this->file_controller->get_files(
				array(
					'date_filter' => 'created',
					'date_start'  => 1,
					'date_end'    => $timestamp,
					'orderby'     => 'created',
					'per_page'    => self::DELETE_BATCH_SIZE,
					'offset'      => $skipped,
				)
			);

			if ( is_wp_error( $files ) || ! is_array( $files ) ) {
				break;
			}

			$fetched_count = count( $files );
			$files         = array_filter(
				$files,
				function ( $file ) use ( $timestamp ) {
					/**
					 * Allows preventing an expired log file from being deleted.
					 *
					 * @param bool $delete    True to delete the file.
					 * @param File $file      The log file object.
					 * @param int  $timestamp The expiration threshold.
					 *
					 * @since 8.7.0
					 */
					$delete = apply_filters( 'woocommerce_logger_delete_expired_file', true, $file, $timestamp );

					return boolval( $delete );
				}
			);

			$file_count       = count( $files );
			$vetoed_count     = $fetched_count - $file_count;
			$deleted_in_batch = 0;
			if ( $file_count > 0 ) {
				$file_ids = array_map(
					fn( $file ) => $file->get_file_id(),
					$files
				);

				$deleted_in_batch = $this->file_controller->delete_files( $file_ids );
				$deleted         += $deleted_in_batch;
			}

			// Deleted files disappear from the directory, so only vetoed files
			// need to be skipped. If no progress was made, skip the full page to
			// avoid retrying a permanently vetoed or undeletable batch forever.
			$skipped += $deleted_in_batch > 0 ? $vetoed_count : $fetched_count;
		} while ( self::DELETE_BATCH_SIZE === $fetched_count );

		if ( $deleted > 0 ) {
			$this->handle(
				time(),
				'info',
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
				array(
					'source' => 'wc_logger',
				)
			);
		}

		return $deleted;
	}
}
