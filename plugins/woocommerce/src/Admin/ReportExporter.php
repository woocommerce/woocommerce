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
	 * Option prefix under which an export's outstanding batch count is kept.
	 *
	 * @since 11.3.0
	 */
	const EXPORT_PENDING_BATCHES_OPTION = 'woocommerce_admin_report_export_pending_batches';

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

		if ( $paths ) {
			foreach ( $paths as $path ) {
				if ( ! is_file( $path ) ) {
					continue;
				}

				$modified = filemtime( $path );
				if ( $modified && $modified < $expired_before ) {
					wp_delete_file( $path );
					self::delete_export_status( $path );
				}
			}
		}

		self::delete_shared_export_status();
	}

	/**
	 * Delete the option every export shared before 11.3.0, once none of the exports in it can still be downloaded.
	 *
	 * @return void
	 */
	private static function delete_shared_export_status() {
		$exports_status = get_option( self::EXPORT_STATUS_OPTION );

		if ( false === $exports_status ) {
			return;
		}

		if ( is_array( $exports_status ) ) {
			foreach ( array_keys( $exports_status ) as $status_key ) {
				$key_parts   = explode( ':', (string) $status_key, 2 );
				$report_type = $key_parts[0];
				$export_id   = isset( $key_parts[1] ) ? $key_parts[1] : '';
				$filename    = self::get_export_filename( $report_type, $export_id );

				$exporter = new ReportCSVExporter();
				$exporter->set_filename( $filename );

				$path = ReportCSVExporter::get_reports_directory() . $exporter->get_filename();
				if ( file_exists( $path ) ) {
					return;
				}
			}
		}

		delete_option( self::EXPORT_STATUS_OPTION );
	}

	/**
	 * Delete the stored progress of an export whose file has been deleted.
	 *
	 * @param string $path Path of the deleted export body, its `.headers` companion, or one of its part files.
	 * @return void
	 */
	private static function delete_export_status( $path ) {
		$filename = basename( $path );

		// A failed export leaves only its part files, so those have to delete its options too.
		if ( preg_match( '/^wc-(.+?)-report-export-(.+)\.csv(?:\.headers|\.part\d+)?$/', $filename, $matches ) ) {
			delete_option( self::get_status_option_name( $matches[1], $matches[2] ) );
			delete_option( self::get_pending_batches_option_name( $matches[1], $matches[2] ) );
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

		if ( 0 < $num_batches ) {
			$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );
			$exporter->delete_export_files();

			self::start_export_progress( $report_type, $export_id, $num_batches );

			// The email is scheduled by the batch that finishes last, since Action Scheduler runs the
			// batches in any order and that is the only point the export is known to be complete.
			$email_user_id = $send_email ? get_current_user_id() : 0;

			// Create batches, like initial import.
			$report_batch_args = array( $export_id, $report_type, $report_args, $email_user_id, $num_batches );

			self::queue_batches( 1, $num_batches, 'export_report', $report_batch_args );
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
	 * @param int    $email_user_id Optional. User to email the download link to once every batch has
	 *                              finished, or 0 for an export that was not asked to be emailed.
	 *                              Exports queued before WooCommerce 11.3.0 run without it.
	 * @param int    $num_batches Optional. Number of batches the export was queued in, which is how many
	 *                            part files the last batch joins. Exports queued before 11.3.0 run without it.
	 * @return void
	 */
	public static function export_report( $page_number, $export_id, $report_type, $report_args, $email_user_id = 0, $num_batches = 0 ) {
		$exporter = new ReportCSVExporter( $report_type, array_merge( $report_args, array( 'page' => $page_number ) ) );
		$exporter->set_filename( self::get_export_filename( $report_type, $export_id ) );

		// An export queued before 11.3.0 has no batch count, and its batches append to the export file.
		if ( null === self::get_pending_batches( $report_type, $export_id ) ) {
			$exporter->write_export_page();
		} else {
			$exporter->write_export_part( $page_number );
		}

		$remaining = self::record_finished_batch( $report_type, $export_id );

		if ( null === $remaining ) {
			// An export queued before 11.3.0 has no batch count, so it keeps reporting the position of
			// whichever page ran last and is emailed by the action queued alongside its batches. The last
			// page still writes the headers row file, as generate_file() did, so the emailed link works.
			$percent_complete = $exporter->get_percent_complete();

			if ( 100 === $percent_complete ) {
				$exporter->write_headers_row_file();
			}

			self::update_export_percentage_complete( $report_type, $export_id, $percent_complete );
			return;
		}

		// Below zero means the export was already claimed, and this batch ran again after that. Its
		// progress would read past 100 and report a finished export that may never have been written.
		if ( $remaining < 0 ) {
			return;
		}

		if ( ! self::claim_finished_export( $report_type, $export_id ) ) {
			self::update_export_percentage_complete( $report_type, $export_id, self::get_progress_percentage( $num_batches, $remaining ) );
			return;
		}

		if ( ! $exporter->assemble_export_file( $num_batches ) ) {
			wc_get_logger()->error(
				sprintf( 'Could not assemble the %1$s report export %2$s from its parts.', $report_type, $export_id ),
				array( 'source' => 'report-csv-exporter' )
			);
			return;
		}

		// Headers row file first: the status endpoint hands out the download link as soon as it reads 100.
		$exporter->write_headers_row_file();
		self::update_export_percentage_complete( $report_type, $export_id, 100 );

		if ( (int) $email_user_id > 0 ) {
			self::schedule_action( 'email_report_download_link', array( (int) $email_user_id, $export_id, $report_type, $report_args ) );
		}
	}

	/**
	 * Start tracking a new export, replacing anything left by an export with the same ID.
	 *
	 * @since 11.3.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param int    $num_batches Number of batches the export was queued in.
	 * @return void
	 */
	private static function start_export_progress( $report_type, $export_id, $num_batches ) {
		// Set directly: update_export_percentage_complete() only ever moves progress forwards.
		update_option( self::get_status_option_name( $report_type, $export_id ), 0, false );
		update_option( self::get_pending_batches_option_name( $report_type, $export_id ), $num_batches, false );
	}

	/**
	 * Record that one of an export's batches has finished.
	 *
	 * One UPDATE, because Action Scheduler can run batches at the same time on more than one runner
	 * and a read-modify-write on the option would lose whichever update landed in between.
	 *
	 * @since 11.3.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return int|null Batches still to finish, or null for an export queued before 11.3.0, which has no count.
	 */
	private static function record_finished_batch( $report_type, $export_id ) {
		global $wpdb;

		$option_name = self::get_pending_batches_option_name( $report_type, $export_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = option_value - 1 WHERE option_name = %s AND option_value > 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$option_name
			)
		);

		// The direct write leaves the option cache stale.
		wp_cache_delete( $option_name, 'options' );

		return self::get_pending_batches( $report_type, $export_id );
	}

	/**
	 * Get how many of an export's batches have still to finish.
	 *
	 * @since 11.3.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return int|null Batches still to finish, or null for an export queued before 11.3.0, which has no count.
	 */
	private static function get_pending_batches( $report_type, $export_id ) {
		$remaining = get_option( self::get_pending_batches_option_name( $report_type, $export_id ), null );

		return null === $remaining ? null : (int) $remaining;
	}

	/**
	 * Claim the job of finishing an export, once every one of its batches has finished.
	 *
	 * Only the update that moves the count from 0 to -1 changes a row, so exactly one batch ever
	 * claims an export however many of them finish at the same moment.
	 *
	 * @since 11.3.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return bool Whether this is the batch that finished the export.
	 */
	private static function claim_finished_export( $report_type, $export_id ) {
		global $wpdb;

		$option_name = self::get_pending_batches_option_name( $report_type, $export_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$claimed = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = '-1' WHERE option_name = %s AND option_value = '0'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$option_name
			)
		);

		// The direct write leaves the option cache stale.
		wp_cache_delete( $option_name, 'options' );

		return 1 === $claimed;
	}

	/**
	 * Work out how far an export has got from how many of its batches are left.
	 *
	 * @since 11.3.0
	 * @param int $num_batches Number of batches the export was queued in.
	 * @param int $remaining Batches still to finish.
	 * @return int Completion percentage.
	 */
	private static function get_progress_percentage( $num_batches, $remaining ) {
		$num_batches = (int) $num_batches;

		if ( $num_batches < 1 ) {
			return 0;
		}

		return (int) floor( ( ( $num_batches - $remaining ) / $num_batches ) * 100 );
	}

	/**
	 * Check whether every one of an export's batches has finished.
	 *
	 * @since 11.3.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return bool
	 */
	private static function export_is_complete( $report_type, $export_id ) {
		$remaining = self::get_pending_batches( $report_type, $export_id );

		if ( null !== $remaining ) {
			return $remaining < 1;
		}

		// An export queued before 11.3.0, or one whose count has already been cleaned up, only has the
		// progress percentage to go on.
		return 100 === self::get_export_percentage_complete( $report_type, $export_id );
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
	 * Get the name of the option an export's progress is stored under.
	 *
	 * The key is hashed so the name fits option_name whatever length the export ID has.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return string Option name.
	 */
	protected static function get_status_option_name( $report_type, $export_id ) {
		$status_key = self::get_status_key( $report_type, $export_id );

		return self::EXPORT_STATUS_OPTION . '_' . md5( $status_key );
	}

	/**
	 * Get the name of the option an export's outstanding batch count is stored under.
	 *
	 * @since 11.3.0
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return string Option name.
	 */
	protected static function get_pending_batches_option_name( $report_type, $export_id ) {
		$status_key = self::get_status_key( $report_type, $export_id );

		return self::EXPORT_PENDING_BATCHES_OPTION . '_' . md5( $status_key );
	}

	/**
	 * Update the completion percentage of a report export.
	 *
	 * Progress only ever moves forwards. Batches finish in any order and each one reports how far the
	 * export had got when it looked, so a lower percentage arriving late is stale, not a correction.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param int    $percentage Completion percentage.
	 * @return void
	 */
	public static function update_export_percentage_complete( $report_type, $export_id, $percentage ) {
		global $wpdb;

		$option_name = self::get_status_option_name( $report_type, $export_id );
		$percentage  = min( 100, max( 0, (int) $percentage ) );

		// One UPDATE, because batches run at the same time on more than one runner and a
		// read-modify-write would let one write back a percentage it read before another moved it up.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$moved_forwards = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND option_value < %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$percentage,
				$option_name,
				$percentage
			)
		);

		// The direct write leaves the option cache stale.
		wp_cache_delete( $option_name, 'options' );

		if ( $moved_forwards ) {
			return;
		}

		// No row to move: the export is either already this far along or has no row of its own, which
		// is the case for one queued before 11.3.0.
		$stored = self::get_export_percentage_complete( $report_type, $export_id );

		// Not autoloaded: a persistent object cache can write back a stale copy of the autoloaded options from another
		// request, which left the email action reading an old percentage and never sending the download link.
		update_option( $option_name, false === $stored ? $percentage : max( (int) $stored, $percentage ), false );
	}

	/**
	 * Get the completion percentage of a report export.
	 *
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @return bool|int Completion percentage, or false if export not found.
	 */
	public static function get_export_percentage_complete( $report_type, $export_id ) {
		$option_name = self::get_status_option_name( $report_type, $export_id );
		$percentage  = get_option( $option_name );

		if ( false === $percentage ) {
			// Exports queued before 11.3.0 report through the option every export shared.
			$exports_status = get_option( self::EXPORT_STATUS_OPTION );
			$status_key     = self::get_status_key( $report_type, $export_id );

			if ( ! is_array( $exports_status ) || ! isset( $exports_status[ $status_key ] ) ) {
				return false;
			}

			$percentage = $exports_status[ $status_key ];
		}

		return (int) $percentage;
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
	 * @return void
	 */
	public static function email_report_download_link( $user_id, $export_id, $report_type, $report_args = array() ) {
		if ( ! self::export_is_complete( $report_type, $export_id ) ) {
			// Say so rather than finishing quietly: the scheduler records this action as complete
			// either way, which is what made the missing email hard to account for.
			wc_get_logger()->warning(
				sprintf( 'Not emailing the %1$s report export %2$s: it never reported itself complete.', $report_type, $export_id ),
				array( 'source' => 'report-csv-exporter' )
			);

			return;
		}

		$download_url = self::get_download_url( $report_type, $export_id, $report_args );

		\WC_Emails::instance();
		$email = new ReportCSVEmail();
		$email->set_report_date_range( self::get_export_date_range_label( $report_args ) );
		$email->trigger( $user_id, $report_type, $download_url );
	}
}
