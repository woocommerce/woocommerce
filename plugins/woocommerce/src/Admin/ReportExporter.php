<?php
/**
 * Handles reports CSV export.
 */

namespace Automattic\WooCommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\Schedulers\SchedulerTraits;

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

		// Create batches, like initial import.
		$report_batch_args = array( $export_id, $report_type, $report_args );

		if ( 0 < $num_batches ) {
			self::queue_batches( 1, $num_batches, 'export_report', $report_batch_args );

			if ( $send_email ) {
				$email_action_args = array( get_current_user_id(), $export_id, $report_type );
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
	 * @return void
	 */
	public static function export_report( $page_number, $export_id, $report_type, $report_args ) {
		$report_args['page'] = $page_number;

		$exporter = new ReportCSVExporter( $report_type, $report_args );
		$exporter->set_filename( "wc-{$report_type}-report-export-{$export_id}" );
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
	 * Serve the export file.
	 */
	public static function download_export_file() {
		/*
		 * A read-only download of a report the requesting user is already allowed to view, gated on
		 * the view_woocommerce_reports capability, so a nonce would only prevent nuisance CSRF. The
		 * action is compared verbatim against a fixed name, and set_filename() applies
		 * sanitize_file_name(), which keeps the path inside the reports directory. A nonce is not an
		 * option here either: nonces last 24 hours, and this link is emailed and kept for a week.
		 */
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if (
			! isset( $_GET['action'] ) ||
			self::DOWNLOAD_EXPORT_ACTION !== wp_unslash( $_GET['action'] ) ||
			empty( $_GET['filename'] ) ||
			! is_string( $_GET['filename'] ) ||
			! current_user_can( 'view_woocommerce_reports' )
		) {
			return;
		}

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( wp_unslash( $_GET['filename'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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
	 * @return void
	 */
	public static function email_report_download_link( $user_id, $export_id, $report_type ) {
		$percent_complete = self::get_export_percentage_complete( $report_type, $export_id );

		if ( 100 === $percent_complete ) {
			$query_args   = array(
				'action'   => self::DOWNLOAD_EXPORT_ACTION,
				'filename' => "wc-{$report_type}-report-export-{$export_id}",
			);
			$download_url = add_query_arg( $query_args, admin_url() );

			\WC_Emails::instance();
			$email = new ReportCSVEmail();
			$email->trigger( $user_id, $report_type, $download_url );
		}
	}
}
