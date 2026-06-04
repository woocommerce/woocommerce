<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Logging;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Batch processor for deleting log entries of completed orders.
 * It only works when either HPOS is enabled or the orders data store is the old CPT-based one,
 * because otherwise the ability to query orders by meta key is not guaranteed.
 */
class OrderLogsDeletionProcessor implements BatchProcessorInterface {

	/**
	 * Constant representing the default size of the batches to process.
	 */
	public const DEFAULT_BATCH_SIZE = 1000;

	/**
	 * True if HPOS is enabled.
	 *
	 * @var bool
	 */
	private bool $hpos_in_use = false;

	/**
	 * True if HPOS is disabled and the orders data store in use is the old CPT one.
	 *
	 * @var bool
	 */
	private bool $cpt_in_use = false;

	/**
	 * The instance of LegacyProxy to use.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * The instance of OrderLogsCleanupHelper to use.
	 *
	 * @var OrderLogsCleanupHelper
	 */
	private OrderLogsCleanupHelper $order_logs_cleanup_helper;

	/**
	 * Initialize the instance.
	 * This is invoked by the dependency injection container.
	 *
	 * @param CustomOrdersTableController $hpos_controller The instance of CustomOrdersTableController to use.
	 * @param LegacyProxy                 $legacy_proxy The instance of LegacyProxy to use.
	 * @param OrderLogsCleanupHelper      $order_logs_cleanup_helper The instance of OrderLogsCleanupHelper to use.
	 *
	 * @internal
	 */
	final public function init( CustomOrdersTableController $hpos_controller, LegacyProxy $legacy_proxy, OrderLogsCleanupHelper $order_logs_cleanup_helper ) {
		$this->hpos_in_use = $hpos_controller->custom_orders_table_usage_is_enabled();
		if ( ! $this->hpos_in_use ) {
			$this->cpt_in_use = \WC_Order_Data_Store_CPT::class === \WC_Data_Store::load( 'order' )->get_current_class_name();
		}

		$this->legacy_proxy              = $legacy_proxy;
		$this->order_logs_cleanup_helper = $order_logs_cleanup_helper;
	}

	/**
	 * Get the name of the processor.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Order logs deletion process';
	}

	/**
	 * Get a description of the processor.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return 'Deletes debug logs of completed orders.';
	}

	/**
	 * Get the default batch size for this processor.
	 *
	 * @return int
	 */
	public function get_default_batch_size(): int {
		return self::DEFAULT_BATCH_SIZE;
	}

	/**
	 * Get the total count of entries pending processing.
	 *
	 * @return int
	 */
	public function get_total_pending_count(): int {
		if ( $this->hpos_in_use ) {
			return $this->get_total_pending_count_hpos();
		} elseif ( $this->cpt_in_use ) {
			return $this->get_total_pending_count_cpt();
		} else {
			$this->throw_doing_it_wrong( StringUtil::class_name_without_namespace( __CLASS__ ) . '::' . __FUNCTION__ );
			return 0;
		}
	}

	/**
	 * Get the total count of entries pending processing, HPOS version.
	 *
	 * @return int
	 */
	private function get_total_pending_count_hpos(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
                 FROM {$wpdb->prefix}wc_orders_meta
                 WHERE meta_key = %s",
				'_debug_log_source_pending_deletion'
			)
		);
	}

	/**
	 * Get the total count of entries pending processing, CPT datastore version.
	 *
	 * @return int
	 */
	private function get_total_pending_count_cpt(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = %s
                 AND p.post_type = %s",
				'_debug_log_source_pending_deletion',
				'shop_order'
			)
		);
	}

	/**
	 * Get the next batch of items to process.
	 * An item will be an associative array of 'order_id' and 'meta_value'.
	 *
	 * @param int $size Maximum size of the batch to return.
	 * @return array
	 */
	public function get_next_batch_to_process( int $size ): array {
		if ( $this->hpos_in_use ) {
			return $this->get_next_batch_to_process_hpos( $size );
		} elseif ( $this->cpt_in_use ) {
			return $this->get_next_batch_to_process_cpt( $size );
		} else {
			$this->throw_doing_it_wrong( StringUtil::class_name_without_namespace( __CLASS__ ) . '::' . __FUNCTION__ );
			return array();
		}
	}

	/**
	 * Get the next batch of items to process, HPOS version.
	 *
	 * @param int $size Maximum size of the batch to return.
	 * @return array
	 */
	private function get_next_batch_to_process_hpos( int $size ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT order_id, meta_value
                 FROM {$wpdb->prefix}wc_orders_meta
                 WHERE meta_key = %s
                 ORDER BY order_id
                 LIMIT %d",
				'_debug_log_source_pending_deletion',
				$size
			),
			ARRAY_A
		);
	}

	/**
	 * Get the next batch of items to process, CPT datastore version.
	 *
	 * @param int $size Maximum size of the batch to return.
	 * @return array
	 */
	private function get_next_batch_to_process_cpt( int $size ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID as order_id, pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = %s
                 AND p.post_type = 'shop_order'
                 ORDER BY p.ID
                 LIMIT %d",
				'_debug_log_source_pending_deletion',
				$size
			),
			ARRAY_A
		);
	}

	/**
	 * Process a batch of items.
	 * Items are expected to be in the format returned by get_next_batch_to_process.
	 *
	 * @param array $batch Batch of items to process.
	 * @throws \Exception Invalid input.
	 */
	public function process_batch( array $batch ): void {
		if ( empty( $batch ) ) {
			return;
		}

		if ( ! $this->hpos_in_use && ! $this->cpt_in_use ) {
			$this->throw_doing_it_wrong( StringUtil::class_name_without_namespace( __CLASS__ ) . '::' . __FUNCTION__ );
			return;
		}

		$items = array();
		foreach ( $batch as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['meta_value'] ) || ! isset( $item['order_id'] ) ) {
				throw new \Exception( "\$batch must be an array of arrays, each having a 'meta_value' key and an 'order_id' key" );
			}
			$items[ $item['order_id'] ] = $item['meta_value'];
		}

		$this->order_logs_cleanup_helper->clear_logs_and_delete_meta( $items );
	}

	/**
	 * Throw a "doing it wrong" error.
	 *
	 * @param string $function_name Class and function name to include in the error.
	 */
	private function throw_doing_it_wrong( string $function_name ) {
		$this->legacy_proxy->call_function(
			'wc_doing_it_wrong',
			$function_name,
			"This processor shouldn't be enqueued when the orders data store in use is neither the HPOS one nor the CPT one. Just delete the order debug logs directly.",
			'10.3.0'
		);
	}
}
