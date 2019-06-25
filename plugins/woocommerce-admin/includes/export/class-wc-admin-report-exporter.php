<?php
/**
 * Handles reports CSV export.
 *
 * @package WooCommerce/Export
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Report_Exporter Class.
 */
class WC_Admin_Report_Exporter {
	/**
	 * Action hook for generating a report export.
	 */
	const REPORT_EXPORT_ACTION = 'wc-admin_report_export';

	/**
	 * Action scheduler group.
	 */
	const QUEUE_GROUP = 'wc-admin-data';

	/**
	 * Queue instance.
	 *
	 * @var WC_Queue_Interface
	 */
	protected static $queue = null;

	/**
	 * Get queue instance.
	 *
	 * @return WC_Queue_Interface
	 */
	public static function queue() {
		if ( is_null( self::$queue ) ) {
			self::$queue = WC()->queue();
		}

		return self::$queue;
	}

	/**
	 * Set queue instance.
	 *
	 * @param WC_Queue_Interface $queue Queue instance.
	 */
	public static function set_queue( $queue ) {
		self::$queue = $queue;
	}

	/**
	 * Hook in action methods.
	 */
	public static function init() {
		// Initialize scheduled action handlers.
		add_action( self::REPORT_EXPORT_ACTION, array( __CLASS__, 'report_export_action' ), 10, 4 );
	}

	/**
	 * Queue up actions for a full report export.
	 *
	 * @param int    $user_id User requesting export.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Report parameters, passed to data query.
	 * @return void
	 */
	public static function queue_report_export( $user_id, $export_id, $report_type, $report_args = array() ) {
		$exporter = new WC_Admin_Report_CSV_Exporter( $report_type, $report_args );
		$exporter->prepare_data_to_export();

		$total_rows  = $exporter->get_total_rows();
		$batch_size  = $exporter->get_limit();
		$num_batches = (int) ceil( $total_rows / $batch_size );
		$start_time  = time() + 5;

		// @todo - batch these batches, like initial import.
		for ( $batch = 1; $batch <= $num_batches; $batch++ ) {
			$report_batch_args = array_merge(
				$report_args,
				array(
					'page' => $batch,
				)
			);

			self::queue()->schedule_single(
				$action_timestamp,
				self::REPORT_EXPORT_ACTION,
				array( $user_id, $export_id, $report_type, $report_batch_args ),
				self::QUEUE_GROUP
			);
		}
	}

	/**
	 * Process a report export action.
	 *
	 * @param int    $user_id User requesting export.
	 * @param string $export_id Unique ID for report (timestamp expected).
	 * @param string $report_type Report type. E.g. 'customers'.
	 * @param array  $report_args Report parameters, passed to data query.
	 * @return void
	 */
	public static function report_export_action( $user_id, $export_id, $report_type, $report_args ) {
		$exporter = new WC_Admin_Report_CSV_Exporter( $report_type, $report_args );
		$exporter->set_filename( "wc-{$report_type}-report-export-{$user_id}-{$export_id}" );
		$exporter->generate_file();
	}
}

WC_Admin_Report_Exporter::init();
