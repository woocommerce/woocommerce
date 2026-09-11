<?php
/**
 * Handles reports CSV export.
 */

namespace Automattic\WooCommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\Schedulers\SchedulerTraits;
use Automattic\WooCommerce\Utilities\TimeUtil;

/**
 * ReportExporter Class.
 */
class ReportExporter {
	/**
	 * Slug to identify the scheduler.
	 *
	 * @var string
	 */
	public static $name = 'report_exporter';

	/**
	 * Scheduler traits.
	 */
	use SchedulerTraits {
		init as scheduler_init;
	}

	/**
	 * Export status option name.
	 */
	const EXPORT_STATUS_OPTION = 'woocommerce_admin_report_export_status';

	/**
	 * Export file download action.
	 */
	const DOWNLOAD_EXPORT_ACTION = 'woocommerce_admin_download_report_csv';

	/**
	 * How long a generated export stays available for download.
	 */
	const EXPORT_RETENTION_PERIOD = WEEK_IN_SECONDS;

	/**
	 * How long the email waits for pages that are still running before reporting the export as failed.
	 *
	 * @since 11.2.0
	 */
	const EXPORT_WAIT_LIMIT = HOUR_IN_SECONDS;

	/**
	 * Get all available scheduling actions.
	 * Used to determine action hook names and clear events.
	 *
	 * @return array
	 */
	public static function get_scheduler_actions() {
		return array(
			'export_report'              => 'woocommerce_admin_report_export',
			'email_report_download_link' => 'woocommerce_admin_email_report_download_link',
		);
	}

	/**
	 * Add action dependencies.
	 *
	 * @return array
	 */
	public static function get_dependencies() {
		return array(
			'email_report_download_link' => self::get_action( 'export_report' ),
		);
	}

	/**
	 * Hook in action methods.
	 */
	public static function init() {
		// Initialize scheduled action handlers.
		self::scheduler_init();

		self::init_export_files();
	}

	/**
	 * Hook in the handlers that serve and expire already generated exports.
	 *
	 * These stay registered when Analytics is switched off, so a link that was already emailed
	 * keeps working and old exports still get cleaned up, even though no new ones are generated.
	 *
	 * @internal
	 * @since 11.2.0
	 * @return void
	 */
	public static function init_export_files() {
		add_action( 'admin_init', array( __CLASS__, 'download_export_file' ) );
		add_action( 'wc_admin_daily', array( __CLASS__, 'delete_expired_exports' ) );
	}

	/**
	 * Delete generated exports that are past the retention period.
	 *
	 * @internal
	 * @since 11.2.0
	 * @return void
	 */
	public static function delete_expired_exports() {
		$expired_before = time() - self::EXPORT_RETENTION_PERIOD;

		// Both the report body and its `.headers` companion, but never the directory's
		// .htaccess and index.html guards.
		$paths = glob( ReportCSVExporter::get_reports_directory() . '*.csv*' );
		if ( ! $paths ) {
			return;
		}

		foreach ( $paths as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}

			$modified = filemtime( $path );
			if ( $modified && $modified < $expired_before ) {
				wp_delete_file( $path );
			}
		}
	}

	/**
	 * Queue up actions for a full report export.
	 *
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Report parameters, passed to data query.
	 * @param bool   $send_email Optional. Send an email when the export is complete.
	 * @return int Number of items to export.
	 */
	public static function queue_report_export( $export_id, $report_type, $report_args = array(), $send_email = false ) {
		$report_args = self::freeze_report_period( $report_type, $report_args );

		$exporter = new ReportCSVExporter( $report_type, $report_args );
		$exporter->prepare_data_to_export();

		$total_rows  = $exporter->get_total_rows();
		$batch_size  = $exporter->get_limit();
		$num_batches = (int) ceil( $total_rows / $batch_size );

		// Create batches, like initial import.
		$report_batch_args = array( $export_id, $report_type, $report_args );

		if ( 0 < $num_batches ) {
			// The body exists before any page runs, so the queue may run the pages in any order.
			$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );
			$exporter->create_export_file();

			self::queue_batches( 1, $num_batches, 'export_report', $report_batch_args );

			if ( $send_email ) {
				$email_action_args = array( get_current_user_id(), $export_id, $report_type, $report_args );
				self::schedule_action( 'email_report_download_link', $email_action_args );
			}
		}

		return $total_rows;
	}

	/**
	 * Cap the report period at the time of the request, so orders placed while the export runs are not in it.
	 *
	 * Each batch queries live data. With a period running into the future, new orders would shift the
	 * pages under the export, duplicating some rows, dropping others, and moving the row count the
	 * batches paginate by.
	 *
	 * Only an end the request sent is capped. Each report gives `before` its own meaning, and one it
	 * did not ask for is a new filter: Customers turns it into an order date, which drops every
	 * customer who never ordered.
	 *
	 * @internal
	 * @since 11.2.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Report parameters, passed to data query.
	 * @return array Report parameters with a `before` that was sent lowered to now, for reports that take one.
	 */
	public static function freeze_report_period( $report_type, $report_args ) {
		if ( ! is_array( $report_args ) || empty( $report_args['before'] ) || ! is_string( $report_args['before'] ) ) {
			return $report_args;
		}

		$controller = ReportCSVExporter::get_report_controller( $report_type );
		if ( ! $controller || ! isset( $controller->get_collection_params()['before'] ) ) {
			return $report_args;
		}

		$now = time();

		try {
			// Unspecified timezone means the local one, as the report data store reads it.
			$before = new \DateTime( $report_args['before'], new \DateTimeZone( wc_timezone_string() ) );
		} catch ( \Exception $e ) {
			// Leave an unreadable value for the report's own validation to reject.
			return $report_args;
		}

		if ( $before->getTimestamp() <= $now ) {
			return $report_args;
		}

		// Local wall time with its offset spelled out, so the data store reads it back as this instant
		// and the repeated hour when DST ends is not ambiguous. Not wp_date(): calendar plugins filter
		// it into dates the report then rejects. wp_timezone() also covers stores set to a manual offset.
		$report_args['before'] = ( new \DateTimeImmutable( '@' . $now ) )->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i:sP' );

		return $report_args;
	}

	/**
	 * Process a report export action.
	 *
	 * @param int    $page_number Page number for this action.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Report parameters, passed to data query.
	 * @return void
	 */
	public static function export_report( $page_number, $export_id, $report_type, $report_args ) {
		$report_args['page'] = $page_number;

		$exporter = new ReportCSVExporter( $report_type, $report_args );
		$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );
		$exporter->generate_file();

		// Progress is counted in queued pages, not rows. The row total is re-read from live data on every
		// batch, so an order changing status mid-export would otherwise leave the export short of 100.
		$progress = self::get_export_progress( $export_id );
		if ( null === $progress ) {
			self::update_export_percentage_complete( $report_type, $export_id, $exporter->get_percent_complete() );
			return;
		}

		// This page is still "in progress" in the queue while it runs, so count it as done here.
		$pages_done = $progress['complete'] + 1;
		if ( $pages_done >= $progress['pages'] && 0 === $progress['failed'] ) {
			self::finalize_export( $report_type, $export_id );
			return;
		}

		self::update_export_percentage_complete( $report_type, $export_id, (int) floor( $pages_done / $progress['pages'] * 100 ) );
	}

	/**
	 * Count this export's batch actions in the queue by outcome.
	 *
	 * The queue is the only record of how many pages were scheduled and which of them actually ran,
	 * so completion is read from it rather than from a percentage each batch computes for itself.
	 *
	 * A large export is queued through chunk actions that each schedule a range of pages. A chunk that
	 * has not run yet stands for pages the queue does not hold, so it counts as an unfinished page, and
	 * a chunk that failed as a failed one. Otherwise the pages queued so far could all be complete
	 * while whole ranges were still missing.
	 *
	 * @internal
	 * @since 11.2.0
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return array|null Counts keyed by `pages`, `complete`, `unfinished`, `failed`, plus the queue
	 *                    log messages of failed batches under `errors`. Null when the queue holds
	 *                    no batches for this export, e.g. when batches ran synchronously.
	 */
	public static function get_export_progress( $export_id ) {
		$outcomes = array(
			'complete'   => array( \ActionScheduler_Store::STATUS_COMPLETE ),
			'unfinished' => array( \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ),
			'failed'     => array( \ActionScheduler_Store::STATUS_FAILED, \ActionScheduler_Store::STATUS_CANCELED ),
		);
		$hooks    = array(
			self::get_action( 'export_report' ) => array_keys( $outcomes ),
			self::get_action( 'queue_batches' ) => array( 'unfinished', 'failed' ),
		);
		$progress = array(
			'pages'  => 0,
			'errors' => array(),
		);

		/**
		 * The queue.
		 *
		 * @var \WC_Queue_Interface $queue
		 */
		$queue = self::queue();

		foreach ( $outcomes as $outcome => $statuses ) {
			$progress[ $outcome ] = 0;

			foreach ( $hooks as $hook => $counted_outcomes ) {
				if ( ! in_array( $outcome, $counted_outcomes, true ) ) {
					continue;
				}

				// IDs only: an export can run to thousands of pages, and every page counts them again.
				$action_ids = $queue->search(
					array(
						'hook'     => $hook,
						'group'    => self::$group,
						'status'   => $statuses,
						// The quoted JSON form, so a longer export ID or an action ID cannot match.
						'search'   => '"' . $export_id . '"',
						'per_page' => -1,
					),
					'ids'
				);

				$progress[ $outcome ] += count( $action_ids );
				$progress['pages']    += count( $action_ids );

				if ( 'failed' === $outcome ) {
					foreach ( $action_ids as $action_id ) {
						// The failure is the last thing the queue logs for an action.
						$log_entries = \ActionScheduler::logger()->get_logs( $action_id );
						$log_entry   = end( $log_entries );
						if ( $log_entry ) {
							$progress['errors'][] = $log_entry->get_message();
						}
					}
				}
			}
		}

		return 0 === $progress['pages'] ? null : $progress;
	}

	/**
	 * Mark an export as complete once every queued page has been written.
	 *
	 * @internal
	 * @since 11.2.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return void
	 */
	public static function finalize_export( $report_type, $export_id ) {
		$exporter = new ReportCSVExporter( $report_type );
		$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );
		$exporter->write_headers_row_file();

		self::update_export_percentage_complete( $report_type, $export_id, 100 );
	}

	/**
	 * Generate a key to reference an export status.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return string Status key.
	 */
	protected static function get_status_key( $report_type, $export_id ) {
		return $report_type . ':' . $export_id;
	}

	/**
	 * Update the completion percentage of a report export.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param int    $percentage Completion percentage.
	 * @return void
	 */
	public static function update_export_percentage_complete( $report_type, $export_id, $percentage ) {
		$exports_status = get_option( self::EXPORT_STATUS_OPTION, array() );
		$status_key     = self::get_status_key( $report_type, $export_id );

		$exports_status[ $status_key ] = $percentage;

		update_option( self::EXPORT_STATUS_OPTION, $exports_status );
	}

	/**
	 * Get the completion percentage of a report export.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return bool|int Completion percentage, or false if export not found.
	 */
	public static function get_export_percentage_complete( $report_type, $export_id ) {
		$exports_status = get_option( self::EXPORT_STATUS_OPTION, array() );
		$status_key     = self::get_status_key( $report_type, $export_id );

		if ( isset( $exports_status[ $status_key ] ) ) {
			return $exports_status[ $status_key ];
		}

		return false;
	}

	/**
	 * Get the name a report export is stored under.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return string
	 */
	private static function get_export_filename( $report_type, $export_id ) {
		return "wc-{$report_type}-report-export-{$export_id}";
	}

	/**
	 * Get the URL a finished report export is downloaded from.
	 *
	 * @since 11.2.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param array  $report_args Optional. Report parameters the export was queued with. When they name
	 *                            a date range, the link carries it so the download is named after it.
	 * @return string
	 */
	public static function get_download_url( $report_type, $export_id, $report_args = array() ) {
		$query_args = array(
			'action'   => self::DOWNLOAD_EXPORT_ACTION,
			'filename' => self::get_export_filename( $report_type, $export_id ),
		);

		$date_range = self::get_export_date_range( $report_args );
		if ( $date_range ) {
			$query_args['date_range'] = $date_range['after'] . '-to-' . $date_range['before'];
		}

		return add_query_arg( $query_args, admin_url() );
	}

	/**
	 * Get the date range a report export covers.
	 *
	 * Reports are not all limited to a period. Stock, for one, has no date range at all.
	 *
	 * @since 11.2.0
	 * @param array $report_args Report parameters, passed to data query.
	 * @return string[] The range's `after` and `before` dates as `Y-m-d`, or an empty array when the report has no range.
	 */
	public static function get_export_date_range( $report_args ) {
		if ( ! is_array( $report_args ) ) {
			return array();
		}

		$date_range = array();

		foreach ( array( 'after', 'before' ) as $bound ) {
			// Report args arrive from a REST request, so they hold whatever the caller sent. Take the
			// date as written rather than converting it: the report reads these as store local time.
			// The shape alone is not enough, since a date like 2025-06-31 would roll over to July 1.
			if (
				empty( $report_args[ $bound ] ) ||
				! is_string( $report_args[ $bound ] ) ||
				! preg_match( '/^(\d{4}-\d{2}-\d{2})/', $report_args[ $bound ], $matches ) ||
				! TimeUtil::is_valid_date( $matches[1], 'Y-m-d' )
			) {
				return array();
			}

			$date_range[ $bound ] = $matches[1];
		}

		return $date_range;
	}

	/**
	 * Get the date range a report export covers, formatted for display.
	 *
	 * @since 11.2.0
	 * @param array $report_args Report parameters, passed to data query.
	 * @return string Date range in the site's date format, or an empty string when the report has no range.
	 */
	public static function get_export_date_range_label( $report_args ) {
		$date_range = self::get_export_date_range( $report_args );

		if ( ! $date_range ) {
			return '';
		}

		$after  = self::format_date_range_bound( $date_range['after'] );
		$before = self::format_date_range_bound( $date_range['before'] );

		if ( '' === $after || '' === $before ) {
			return '';
		}

		if ( $after === $before ) {
			return $after;
		}

		/* translators: 1: first day of the period a report covers, 2: last day of that period. */
		return sprintf( _x( '%1$s - %2$s', 'Report date range: from-to', 'woocommerce' ), $after, $before );
	}

	/**
	 * Format one end of a report's date range for display.
	 *
	 * @param string $date Date as `Y-m-d`.
	 * @return string The date in the store's date format, or an empty string when it cannot be read.
	 */
	private static function format_date_range_bound( $date ) {
		// Read in the store's own timezone, so the date reads back as the merchant picked it and a
		// date format that names the timezone names theirs rather than UTC. Midday is a safe anchor.
		$parsed = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date . ' 12:00:00', wp_timezone() );

		if ( false === $parsed ) {
			return '';
		}

		return (string) wp_date( wc_date_format(), $parsed->getTimestamp() );
	}

	/**
	 * Build the exporter a download request is asking for.
	 *
	 * A read-only download of a report the requesting user is already allowed to view, gated on
	 * the view_woocommerce_reports capability, so a nonce would only prevent nuisance CSRF. The
	 * action is compared verbatim against a fixed name, and set_filename() applies
	 * sanitize_file_name(), which keeps the path inside the reports directory. A nonce is not an
	 * option here either: nonces last 24 hours, and this link is emailed and kept for a week.
	 *
	 * @param array $request Unslashed request parameters, expected to be `$_GET`.
	 * @return ReportCSVExporter|null The exporter for the requested export, or null when the request asks for no export.
	 */
	private static function get_requested_export( $request ) {
		if (
			! is_array( $request ) ||
			! isset( $request['action'] ) ||
			self::DOWNLOAD_EXPORT_ACTION !== $request['action'] ||
			empty( $request['filename'] ) ||
			! is_string( $request['filename'] ) ||
			! current_user_can( 'view_woocommerce_reports' )
		) {
			return null;
		}

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $request['filename'] );

		// The stored name only identifies the export, so the emailed link carries the report's date
		// range to name the download after the period it covers. It never reaches the file path.
		if ( ! empty( $request['date_range'] ) && is_string( $request['date_range'] ) ) {
			$exporter->set_download_suffix( $request['date_range'] );
		}

		return $exporter;
	}

	/**
	 * Serve the export file.
	 */
	public static function download_export_file() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read in get_requested_export(), which documents why there is no nonce and validates every value it reads.
		$exporter = self::get_requested_export( wp_unslash( $_GET ) );

		if ( ! $exporter ) {
			return;
		}

		// Say so rather than serving an empty CSV: the exporter creates a blank file for a path
		// that no longer exists, which reads as a report with no results.
		if ( ! $exporter->export_file_exists() ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: %s: length of time an export is kept, e.g. "1 week". */
						__( 'This report export is no longer available. Exports are kept for %s, so please request a new one.', 'woocommerce' ),
						human_time_diff( 0, self::EXPORT_RETENTION_PERIOD )
					)
				),
				esc_html__( 'Report export unavailable', 'woocommerce' ),
				array( 'response' => 404 )
			);
		}

		$exporter->send_headers();
		$exporter->stream_export_file();
		exit;
	}

	/**
	 * Process a report export email action.
	 *
	 * @param int    $user_id User ID that requested the email.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Optional. Report parameters the export was queued with. Exports queued
	 *                            before WooCommerce 11.2.0 run without them.
	 * @param int    $wait_until  Optional. Time to stop waiting for pages that are still running. Set on the
	 *                            first re-queue, so an export the queue never finishes is reported instead
	 *                            of waited on forever.
	 * @return void
	 */
	public static function email_report_download_link( $user_id, $export_id, $report_type, $report_args = array(), $wait_until = 0 ) {
		$progress = self::get_export_progress( $export_id );

		\WC_Emails::instance();
		$email = new ReportCSVEmail();
		$email->set_report_date_range( self::get_export_date_range_label( $report_args ) );

		// A page can be claimed by another runner between the dependency check and this call, so
		// check again here rather than reporting on a file that is still being written.
		if ( null !== $progress && $progress['unfinished'] > 0 ) {
			$wait_until = (int) $wait_until;

			if ( 0 === $wait_until ) {
				$wait_until = time() + self::EXPORT_WAIT_LIMIT;
			}

			// A page can sit "in progress" for good when its runner died and the queue's cleanup is turned off.
			if ( time() > $wait_until ) {
				wc_get_logger()->error(
					sprintf(
						'%1$s report export %2$s gave up: %3$d of %4$d batches still had not finished after %5$s.',
						$report_type,
						$export_id,
						$progress['unfinished'],
						$progress['pages'],
						human_time_diff( 0, self::EXPORT_WAIT_LIMIT )
					),
					array( 'source' => 'report-csv-exporter' )
				);
				$email->trigger_failed( $user_id, $report_type );
				return;
			}

			/**
			 * The queue.
			 *
			 * @var \WC_Queue_Interface $queue
			 */
			$queue = self::queue();
			$queue->schedule_single(
				time() + 5,
				(string) self::get_action( 'email_report_download_link' ),
				array( $user_id, $export_id, $report_type, $report_args, $wait_until ),
				(string) self::$group
			);
			return;
		}

		if ( null !== $progress && $progress['failed'] > 0 ) {
			wc_get_logger()->error(
				sprintf(
					'%1$s report export %2$s failed: %3$d of %4$d batches did not complete. %5$s',
					$report_type,
					$export_id,
					$progress['failed'],
					$progress['pages'],
					implode( ' | ', $progress['errors'] )
				),
				array( 'source' => 'report-csv-exporter' )
			);
			$email->trigger_failed( $user_id, $report_type );
			return;
		}

		if ( null !== $progress ) {
			self::finalize_export( $report_type, $export_id );
		} elseif ( 100 !== self::get_export_percentage_complete( $report_type, $export_id ) ) {
			// Batches ran synchronously, so the only record is the percentage they left behind.
			wc_get_logger()->error(
				sprintf( '%1$s report export %2$s stopped at %3$s%%.', $report_type, $export_id, self::get_export_percentage_complete( $report_type, $export_id ) ),
				array( 'source' => 'report-csv-exporter' )
			);
			$email->trigger_failed( $user_id, $report_type );
			return;
		}

		$email->trigger( $user_id, $report_type, self::get_download_url( $report_type, $export_id, $report_args ) );
	}
}
