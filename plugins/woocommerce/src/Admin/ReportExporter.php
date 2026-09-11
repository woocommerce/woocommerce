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
		$exporter = new ReportCSVExporter( $report_type, $report_args );
		$exporter->prepare_data_to_export();

		$total_rows  = $exporter->get_total_rows();
		$batch_size  = $exporter->get_limit();
		$num_batches = (int) ceil( $total_rows / $batch_size );

		// Create batches, like initial import. Each batch is told how many there are, so completion
		// does not depend on a row total that keeps moving while the export runs.
		$report_batch_args = array( $export_id, $report_type, $report_args, $num_batches );

		if ( 0 < $num_batches ) {
			// Before the batches, not after: with queueing disabled they run right here, and a
			// custom export id can be reused, so the previous run's 100 must not survive into this one.
			self::reset_export_percentage_complete( $report_type, $export_id );
			self::queue_batches( 1, $num_batches, 'export_report', $report_batch_args );

			if ( $send_email ) {
				$email_action_args = array( get_current_user_id(), $export_id, $report_type, $report_args );
				self::schedule_action( 'email_report_download_link', $email_action_args );
			}
		}

		return $total_rows;
	}

	/**
	 * Process a report export action.
	 *
	 * @param int    $page_number Page number for this action.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Report parameters, passed to data query.
	 * @param int    $total_batches Optional. How many batches the export was queued as. Exports queued
	 *                              before WooCommerce 11.2.0 run without it and measure progress by row.
	 * @return void
	 */
	public static function export_report( $page_number, $export_id, $report_type, $report_args, $total_batches = 0 ) {
		$report_args['page'] = $page_number;

		$exporter = new ReportCSVExporter( $report_type, $report_args );
		$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );
		if ( $total_batches > 0 ) {
			$exporter->set_total_batches( $total_batches );
		}
		$exporter->generate_file();

		self::update_export_percentage_complete( $report_type, $export_id, $exporter->get_percent_complete() );
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
	 * Never lowers a stored percentage. Batches can finish out of order, so a later-running earlier
	 * page must not take the export back from 100.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param int    $percentage Completion percentage.
	 * @return void
	 */
	public static function update_export_percentage_complete( $report_type, $export_id, $percentage ) {
		$exports_status = get_option( self::EXPORT_STATUS_OPTION, array() );
		$status_key     = self::get_status_key( $report_type, $export_id );

		if ( isset( $exports_status[ $status_key ] ) && $exports_status[ $status_key ] > $percentage ) {
			return;
		}

		$exports_status[ $status_key ] = $percentage;

		update_option( self::EXPORT_STATUS_OPTION, $exports_status );
	}

	/**
	 * Start a report export's completion percentage over at 0, whatever it held before.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return void
	 */
	private static function reset_export_percentage_complete( $report_type, $export_id ) {
		$exports_status = get_option( self::EXPORT_STATUS_OPTION, array() );

		$exports_status[ self::get_status_key( $report_type, $export_id ) ] = 0;

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
	 * Emails the download link once every batch of the export has run. While batches are still
	 * queued or running it reschedules itself, and when the export can no longer complete it logs
	 * why rather than leaving the merchant waiting for an email that never comes.
	 *
	 * @param int    $user_id User ID that requested the email.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Optional. Report parameters the export was queued with. Exports queued
	 *                            before WooCommerce 11.2.0 run without them.
	 * @return void
	 */
	public static function email_report_download_link( $user_id, $export_id, $report_type, $report_args = array() ) {
		$log_context = array( 'source' => 'report-csv-exporter' );

		if ( self::find_export_batch( $export_id, $report_type, array( 'pending', 'in-progress' ) ) ) {
			// Only found when actions are queued, so this cannot run inline and recurse.
			self::schedule_action( 'email_report_download_link', array( $user_id, $export_id, $report_type, $report_args ) );
			return;
		}

		// A batch cannot simply be re-run: batches append to one file and the first one truncates it.
		if ( self::find_export_batch( $export_id, $report_type, array( 'failed', 'canceled' ) ) ) {
			wc_get_logger()->error(
				sprintf( 'Not emailing the %1$s report export %2$s: a batch of it failed or was canceled, so the file is incomplete.', $report_type, $export_id ),
				$log_context
			);
			return;
		}

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );

		if ( ! $exporter->export_file_exists() ) {
			wc_get_logger()->error(
				sprintf( 'Not emailing the %1$s report export %2$s: every batch has run but the export file is missing or was never finished.', $report_type, $export_id ),
				$log_context
			);
			return;
		}

		self::update_export_percentage_complete( $report_type, $export_id, 100 );

		$download_url = self::get_download_url( $report_type, $export_id, $report_args );

		\WC_Emails::instance();
		$email = new ReportCSVEmail();
		$email->set_report_date_range( self::get_export_date_range_label( $report_args ) );

		if ( ! $email->trigger( $user_id, $report_type, $download_url ) ) {
			wc_get_logger()->error(
				sprintf( 'The %1$s report export %2$s is ready but its download link could not be emailed to user %3$d.', $report_type, $export_id, $user_id ),
				$log_context
			);
		}
	}

	/**
	 * Find a queued batch of an export that is in one of the given states.
	 *
	 * Looks at the batch actions and at the actions that queue them in chunks. Large exports queue
	 * their batches through the latter, so for a while no batch action exists yet.
	 *
	 * @param string   $export_id Unique ID for report (timestamp expected).
	 * @param string   $report_type Report type. E.g. 'customers'.
	 * @param string[] $statuses Action Scheduler statuses to look for.
	 * @return \ActionScheduler_Action|null A matching action, or null when there is none.
	 */
	private static function find_export_batch( $export_id, $report_type, $statuses ) {
		if ( self::is_action_scheduling_disabled() ) {
			return null;
		}

		global $wpdb;

		// Both kinds of action carry the export id followed by the report type. Matching that pair the
		// way Action Scheduler stores it keeps a short custom export id from matching other exports.
		// The search is a LIKE, and a custom id can hold "_", so escape it rather than match any character.
		$args_fragment = $wpdb->esc_like( trim( (string) wp_json_encode( array( $export_id, $report_type ) ), '[]' ) );

		/**
		 * The queue, typed here because the trait's docblock names the interface without its namespace.
		 *
		 * @var \WC_Queue_Interface $queue
		 */
		$queue = self::queue();

		foreach ( array( 'export_report', 'queue_batches' ) as $action_name ) {
			$found = $queue->search(
				array(
					'hook'     => self::get_action( $action_name ),
					'group'    => self::$group,
					'status'   => $statuses,
					'search'   => $args_fragment,
					'per_page' => 1,
				)
			);

			if ( $found ) {
				return current( $found );
			}
		}

		return null;
	}
}
