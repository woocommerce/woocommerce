<?php

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Utilities\StringUtil;
use \Exception;

/**
 * This class is intended to be used with BatchProcessingController and converts verbose
 * 'coupon_data' metadata entries in coupon line items (corresponding to coupons applied to orders)
 * into simplified 'coupon_info' entries. See WC_Coupon::get_short_info.
 *
 * Additionally, this class manages the "Convert order coupon data" tool.
 */
class OrderCouponDataMigrator implements BatchProcessorInterface, RegisterHooksInterface {

	/**
	 * Register hooks for the class.
	 */
	public function register() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'handle_woocommerce_debug_tools' ), 999, 1 );
	}

	/**
	 * Get a user-friendly name for this processor.
	 *
	 * @return string Name of the processor.
	 */
	public function get_name(): string {
		return "Coupon line item 'coupon_data' to 'coupon_info' metadata migrator";
	}

	/**
	 * Get a user-friendly description for this processor.
	 *
	 * @return string Description of what this processor does.
	 */
	public function get_description(): string {
		return "Migrates verbose metadata about coupons applied to an order ('coupon_data' metadata key in coupon line items) to simplified metadata ('coupon_info' keys)";
	}

	/**
	 * Get the total number of pending items that require processing.
	 *
	 * @return int Number of items pending processing.
	 */
	public function get_total_pending_count(): int {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key=%s",
				'coupon_data'
			)
		);
	}

	/**
	 * Returns the next batch of items that need to be processed.
	 * A batch in this context is a list of 'meta_id' values from the wp_woocommerce_order_itemmeta table.
	 *
	 * @param int $size Maximum size of the batch to be returned.
	 *
	 * @return array Batch of items to process, containing $size or less items.
	 */
	public function get_next_batch_to_process( int $size ): array {
		global $wpdb;

		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key=%s ORDER BY meta_id ASC LIMIT %d",
				'coupon_data',
				$size
			)
		);

		return array_map( 'absint', $meta_ids );
	}

	/**
	 * Process data for the supplied batch. See the convert_item method.
	 *
	 * @throw \Exception Something went wrong while processing the batch.
	 *
	 * @param array $batch Batch to process, as returned by 'get_next_batch_to_process'.
	 */
	public function process_batch( array $batch ): void {
		global $wpdb;

		if ( empty( $batch ) ) {
			return;
		}

		$meta_ids = StringUtil::to_sql_list( $batch );

		$meta_ids_and_values = $wpdb->get_results(
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT meta_id,meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_id IN $meta_ids",
			ARRAY_N
		);

		foreach ( $meta_ids_and_values as $meta_id_and_value ) {
			try {
				$this->convert_item( (int) $meta_id_and_value[0], $meta_id_and_value[1] );
			} catch ( Exception $ex ) {
				wc_get_logger()->error( StringUtil::class_name_without_namespace( self::class ) . ": when converting meta row with id {$meta_id_and_value[0]}: {$ex->getMessage()}" );
			}
		}
	}

	/**
	 * Convert one verbose 'coupon_data' entry into a simplified 'coupon_info' entry.
	 *
	 * The existing database row is updated in place, both the 'meta_key' and the 'meta_value' columns.
	 *
	 * @param int    $meta_id Value of 'meta_id' of the row being converted.
	 * @param string $meta_value Value of 'meta_value' of the row being converted.
	 * @throws Exception Database error.
	 */
	private function convert_item( int $meta_id, string $meta_value ) {
		global $wpdb;

		//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$coupon_data = unserialize( $meta_value );

		$temp_coupon = new \WC_Coupon();
		$temp_coupon->set_props( $coupon_data );

		//phpcs:disable WordPress.DB.SlowDBQuery
		$wpdb->update(
			"{$wpdb->prefix}woocommerce_order_itemmeta",
			array(
				'meta_key'   => 'coupon_info',
				'meta_value' => $temp_coupon->get_short_info(),
			),
			array( 'meta_id' => $meta_id )
		);
		//phpcs:enable WordPress.DB.SlowDBQuery

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}
	}

	/**
	 * Default (preferred) batch size to pass to 'get_next_batch_to_process'.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size(): int {
		return 1000;
	}

	/**
	 * Add the tool to start or stop the background process that converts order coupon metadata entries.
	 *
	 * @param array $tools Old tools array.
	 * @return array Updated tools array.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function handle_woocommerce_debug_tools( array $tools ): array {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$pending_count   = $this->get_total_pending_count();

		if ( 0 === $pending_count ) {
			$tools['start_convert_order_coupon_data'] = array(
				'name'     => __( 'Start converting order coupon data to the simplified format', 'woocommerce' ),
				'button'   => __( 'Start converting', 'woocommerce' ),
				'disabled' => true,
				'desc'     => __( 'This will convert <code>coupon_data</code> order item meta entries to simplified <code>coupon_info</code> entries. The conversion will happen overtime in the background (via Action Scheduler). There are currently no entries to convert.', 'woocommerce' ),
			);
		} elseif ( $batch_processor->is_enqueued( self::class ) ) {
			$tools['stop_convert_order_coupon_data'] = array(
				'name'     => __( 'Stop converting order coupon data to the simplified format', 'woocommerce' ),
				'button'   => __( 'Stop converting', 'woocommerce' ),
				'desc'     =>
					/* translators: %d=count of entries pending conversion */
					sprintf( __( 'This will stop the background process that converts <code>coupon_data</code> order item meta entries to simplified <code>coupon_info</code> entries. There are currently %d entries that can be converted.', 'woocommerce' ), $pending_count ),
				'callback' => array( $this, 'dequeue' ),
			);
		} else {
			$tools['start_converting_order_coupon_data'] = array(
				'name'     => __( 'Convert order coupon data to the simplified format', 'woocommerce' ),
				'button'   => __( 'Start converting', 'woocommerce' ),
				'desc'     =>
					/* translators: %d=count of entries pending conversion */
					sprintf( __( 'This will convert <code>coupon_data</code> order item meta entries to simplified <code>coupon_info</code> entries. The conversion will happen overtime in the background (via Action Scheduler). There are currently %d entries that can be converted.', 'woocommerce' ), $pending_count ),
				'callback' => array( $this, 'enqueue' ),
			);
		}

		return $tools;
	}

	/**
	 * Start the background process for coupon data conversion.
	 *
	 * @return string Informative string to show after the tool is triggered in UI.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function enqueue(): string {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		if ( $batch_processor->is_enqueued( self::class ) ) {
			return __( 'Background process for coupon meta conversion already started, nothing done.', 'woocommerce' );
		}

		$batch_processor->enqueue_processor( self::class );
		return __( 'Background process for coupon meta conversion started', 'woocommerce' );
	}

	/**
	 * Stop the background process for coupon data conversion.
	 *
	 * @return string Informative string to show after the tool is triggered in UI.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function dequeue(): string {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		if ( ! $batch_processor->is_enqueued( self::class ) ) {
			return __( 'Background process for coupon meta conversion not started, nothing done.', 'woocommerce' );
		}

		$batch_processor->remove_processor( self::class );
		return __( 'Background process for coupon meta conversion stopped', 'woocommerce' );
	}
}
