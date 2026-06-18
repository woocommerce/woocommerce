<?php
/**
 * WooPaymentsCanceledAuthorizationFeeRemediationService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use WC_Order;
use WC_Order_Refund;

/**
 * Native owner for WooPayments canceled-authorization fee remediation jobs.
 *
 * Between April 2023 and November 2025, canceled authorizations in WooPayments
 * could have transaction fee metadata and refund objects created. This service
 * preserves the standalone plugin's remediation hooks so in-flight background
 * jobs continue after native Core takes over.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCanceledAuthorizationFeeRemediationService implements RegisterHooksInterface {

	/**
	 * Option key for tracking remediation status.
	 *
	 * @var string
	 */
	const STATUS_OPTION_KEY = 'wcpay_fee_remediation_status';

	/**
	 * Option key for tracking last processed order ID.
	 *
	 * @var string
	 */
	const LAST_ORDER_ID_OPTION_KEY = 'wcpay_fee_remediation_last_order_id';

	/**
	 * Option key for tracking current batch size.
	 *
	 * @var string
	 */
	const BATCH_SIZE_OPTION_KEY = 'wcpay_fee_remediation_batch_size';

	/**
	 * Option key for tracking statistics.
	 *
	 * @var string
	 */
	const STATS_OPTION_KEY = 'wcpay_fee_remediation_stats';

	/**
	 * Preserved live remediation hook.
	 *
	 * @var string
	 */
	const ACTION_HOOK = 'wcpay_remediate_canceled_authorization_fees';

	/**
	 * Preserved dry-run remediation hook.
	 *
	 * @var string
	 */
	const DRY_RUN_ACTION_HOOK = 'wcpay_remediate_canceled_authorization_fees_dry_run';

	/**
	 * Preserved affected-orders check hook.
	 *
	 * @var string
	 */
	const CHECK_AFFECTED_ORDERS_HOOK = 'wcpay_check_affected_auth_fee_orders';

	/**
	 * Option key for tracking the affected-orders check state.
	 *
	 * @var string
	 */
	const CHECK_STATE_OPTION_KEY = 'wcpay_has_affected_auth_fee_orders';

	/**
	 * Option key for tracking dry-run mode.
	 *
	 * @var string
	 */
	const DRY_RUN_OPTION_KEY = 'wcpay_fee_remediation_dry_run';

	/**
	 * Legacy Action Scheduler group used by existing queued jobs.
	 *
	 * @var string
	 */
	const ACTION_SCHEDULER_GROUP_ID = 'woocommerce-payments';

	/**
	 * Starting batch size.
	 *
	 * @var int
	 */
	const INITIAL_BATCH_SIZE = 20;

	/**
	 * Minimum batch size.
	 *
	 * @var int
	 */
	const MIN_BATCH_SIZE = 10;

	/**
	 * Maximum batch size.
	 *
	 * @var int
	 */
	const MAX_BATCH_SIZE = 100;

	/**
	 * Target minimum execution time in seconds.
	 *
	 * @var int
	 */
	const TARGET_MIN_TIME = 5;

	/**
	 * Target maximum execution time in seconds.
	 *
	 * @var int
	 */
	const TARGET_MAX_TIME = 20;

	/**
	 * Bug introduction date.
	 *
	 * @var string
	 */
	const BUG_START_DATE = '2023-04-01';

	/**
	 * WooPayments canceled intent status stored on orders.
	 *
	 * @var string
	 */
	const CANCELED_INTENT_STATUS = 'canceled';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter Runtime owner arbiter.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter ): void {
		$this->arbiter = $arbiter;
	}

	/**
	 * Register preserved remediation queue consumers.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		add_action( self::ACTION_HOOK, array( $this, 'process_batch' ) );
		add_action( self::DRY_RUN_ACTION_HOOK, array( $this, 'process_batch_dry_run' ) );
		add_action( self::CHECK_AFFECTED_ORDERS_HOOK, array( $this, 'check_and_cache_affected_orders' ) );
	}

	/**
	 * Check if dry-run mode is enabled.
	 *
	 * @return bool True if dry-run mode is enabled.
	 */
	public function is_dry_run(): bool {
		return (bool) get_option( self::DRY_RUN_OPTION_KEY, false );
	}

	/**
	 * Check if remediation is complete.
	 *
	 * @return bool True if remediation is complete.
	 */
	public function is_complete(): bool {
		return 'completed' === get_option( self::STATUS_OPTION_KEY, '' );
	}

	/**
	 * Get current batch size.
	 *
	 * @return int Current batch size.
	 */
	public function get_batch_size(): int {
		return (int) get_option( self::BATCH_SIZE_OPTION_KEY, self::INITIAL_BATCH_SIZE );
	}

	/**
	 * Update batch size.
	 *
	 * @param int $size New batch size.
	 */
	public function update_batch_size( int $size ): void {
		$size = max( self::MIN_BATCH_SIZE, min( self::MAX_BATCH_SIZE, $size ) );
		update_option( self::BATCH_SIZE_OPTION_KEY, $size );
	}

	/**
	 * Get last processed order ID.
	 *
	 * @return int Last processed order ID.
	 */
	public function get_last_order_id(): int {
		return (int) get_option( self::LAST_ORDER_ID_OPTION_KEY, 0 );
	}

	/**
	 * Update last processed order ID.
	 *
	 * @param int $order_id Order ID.
	 */
	public function update_last_order_id( int $order_id ): void {
		update_option( self::LAST_ORDER_ID_OPTION_KEY, $order_id );
	}

	/**
	 * Get remediation statistics.
	 *
	 * @return array{processed:int,remediated:int,errors:int} Statistics.
	 */
	public function get_stats(): array {
		$stats = get_option( self::STATS_OPTION_KEY, array() );
		$stats = is_array( $stats ) ? $stats : array();

		return array(
			'processed'  => (int) ( $stats['processed'] ?? 0 ),
			'remediated' => (int) ( $stats['remediated'] ?? 0 ),
			'errors'     => (int) ( $stats['errors'] ?? 0 ),
		);
	}

	/**
	 * Increment a statistic counter.
	 *
	 * @param string $key Stat key to increment.
	 */
	public function increment_stat( string $key ): void {
		$stats = $this->get_stats();
		if ( isset( $stats[ $key ] ) ) {
			++$stats[ $key ];
			update_option( self::STATS_OPTION_KEY, $stats );
		}
	}

	/**
	 * Tell whether HPOS is enabled.
	 *
	 * @return bool True if HPOS is enabled.
	 */
	protected function is_hpos_enabled(): bool {
		return OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Get affected orders that need remediation.
	 *
	 * @param int $limit Number of orders to retrieve.
	 * @return WC_Order[] Array of order objects.
	 */
	public function get_affected_orders( int $limit ): array {
		$limit = absint( $limit );
		if ( 0 === $limit ) {
			return array();
		}

		if ( $this->is_hpos_enabled() ) {
			return $this->get_affected_orders_hpos( $limit );
		}

		return $this->get_affected_orders_cpt( $limit );
	}

	/**
	 * Adjust batch size based on execution time.
	 *
	 * @param float $execution_time Execution time in seconds.
	 */
	public function adjust_batch_size( float $execution_time ): void {
		$current_size = $this->get_batch_size();

		if ( $execution_time < self::TARGET_MIN_TIME ) {
			$this->update_batch_size( $current_size * 2 );
		} elseif ( $execution_time > self::TARGET_MAX_TIME ) {
			$this->update_batch_size( (int) ( $current_size / 2 ) );
		}
	}

	/**
	 * Process a live remediation batch.
	 */
	public function process_batch(): void {
		if ( $this->is_complete() ) {
			return;
		}

		$start_time = microtime( true );
		$batch_size = $this->get_batch_size();
		$orders     = $this->get_affected_orders( $batch_size );

		if ( empty( $orders ) ) {
			$this->mark_complete();
			$this->log_completion();
			$this->cleanup();
			return;
		}

		foreach ( $orders as $order ) {
			$this->increment_stat( 'processed' );

			if ( $this->remediate_order( $order ) ) {
				$this->increment_stat( 'remediated' );
				wc_get_logger()->info(
					sprintf( 'Remediated order %d', $order->get_id() ),
					array( 'source' => 'wcpay-fee-remediation' )
				);
				$this->update_last_order_id( $order->get_id() );
			} else {
				$this->increment_stat( 'errors' );
				wc_get_logger()->warning(
					sprintf( 'Stopping WooPayments canceled-authorization remediation batch after order %d failed. The order will be retried in the next batch.', $order->get_id() ),
					array( 'source' => 'wcpay-fee-remediation' )
				);
				break;
			}
		}

		$execution_time = microtime( true ) - $start_time;
		$this->adjust_batch_size( $execution_time );

		wc_get_logger()->info(
			sprintf(
				'Processed batch of %d orders in %.2f seconds. New batch size: %d',
				count( $orders ),
				$execution_time,
				$this->get_batch_size()
			),
			array( 'source' => 'wcpay-fee-remediation' )
		);

		$this->schedule_next_batch();
	}

	/**
	 * Process a dry-run remediation batch.
	 */
	public function process_batch_dry_run(): void {
		if ( $this->is_complete() && ! $this->is_dry_run() ) {
			return;
		}

		$orders = $this->get_affected_orders( $this->get_batch_size() );

		if ( empty( $orders ) ) {
			$this->log_completion_dry_run();
			$this->cleanup_dry_run();
			return;
		}

		foreach ( $orders as $order ) {
			$this->increment_stat( 'processed' );

			if ( $this->remediate_order( $order, true ) ) {
				$this->increment_stat( 'remediated' );
			} else {
				$this->increment_stat( 'errors' );
			}

			$this->update_last_order_id( $order->get_id() );
		}

		wc_get_logger()->info(
			sprintf( '[DRY RUN] Processed batch of %d orders.', count( $orders ) ),
			array( 'source' => 'wcpay-fee-remediation' )
		);

		$this->schedule_next_batch_dry_run();
	}

	/**
	 * Remediate a single order.
	 *
	 * @param WC_Order $order   Order to remediate.
	 * @param bool     $dry_run Whether to log only without mutating data.
	 * @return bool True on success, false on failure.
	 */
	public function remediate_order( WC_Order $order, bool $dry_run = false ): bool {
		try {
			$fee                 = $order->get_meta( '_wcpay_transaction_fee', true );
			$net                 = $order->get_meta( '_wcpay_net', true );
			$wcpay_refunds       = $this->get_wcpay_refunds( $order->get_refunds() );
			$wcpay_refund_count  = count( $wcpay_refunds );
			$wcpay_refund_total  = 0.0;
			$wcpay_refund_ids    = array();
			$would_change_status = 'refunded' === $order->get_status();
			$changes             = array();

			foreach ( $wcpay_refunds as $refund ) {
				$wcpay_refund_total += abs( (float) $refund->get_amount() );
				$wcpay_refund_ids[]  = $refund->get_id();
			}

			if ( $would_change_status ) {
				$changes[] = 'Changed order status from "Refunded" to "Cancelled"';
			}

			if ( $wcpay_refund_count > 0 ) {
				$changes[] = sprintf(
					'Deleted %d WooPayments refund object%s (IDs: %s) totaling %s',
					$wcpay_refund_count,
					$wcpay_refund_count > 1 ? 's' : '',
					implode( ', ', $wcpay_refund_ids ),
					wc_price( $wcpay_refund_total, array( 'currency' => $order->get_currency() ) )
				);
			}

			if ( ! empty( $fee ) ) {
				$changes[] = sprintf( 'Removed transaction fee: %s', wc_price( $fee, array( 'currency' => $order->get_currency() ) ) );
			}

			if ( ! empty( $net ) ) {
				$changes[] = sprintf( 'Removed net amount: %s', wc_price( $net, array( 'currency' => $order->get_currency() ) ) );
			}

			if ( $dry_run ) {
				if ( ! empty( $changes ) ) {
					wc_get_logger()->info(
						sprintf(
							'[DRY RUN] Order %d would be remediated: %s',
							$order->get_id(),
							wp_strip_all_tags( implode( '; ', $changes ) )
						),
						array( 'source' => 'wcpay-fee-remediation' )
					);
				}

				return true;
			}

			$parent_order_id = $order->get_id();
			foreach ( $wcpay_refunds as $refund ) {
				$refund_id = $refund->get_id();
				if ( true !== $refund->delete( true ) ) {
					return false;
				}

				$this->delete_refund_order_stats( $refund_id );

				/**
				 * Fires after a refund is deleted during WooPayments canceled-authorization fee remediation.
				 *
				 * @since 11.0.0
				 *
				 * @param int $refund_id       Deleted refund ID.
				 * @param int $parent_order_id Parent order ID.
				 */
				do_action( 'woocommerce_refund_deleted', $refund_id, $parent_order_id );
			}

			$order->delete_meta_data( '_wcpay_transaction_fee' );
			$order->delete_meta_data( '_wcpay_net' );
			$order->delete_meta_data( '_wcpay_refund_id' );
			$order->delete_meta_data( '_wcpay_refund_status' );

			if ( $would_change_status ) {
				$order->set_status( 'cancelled', '', false );
			}

			$note_parts = array( 'Removed incorrect data from canceled authorization:' );
			foreach ( $changes as $change ) {
				$note_parts[] = '- ' . $change;
			}
			$note_parts[] = '';
			$note_parts[] = 'These records were incorrectly created for an authorization that was never captured.';
			$note_parts[] = 'No actual payment or refund occurred.';

			$order->add_order_note( implode( "\n", $note_parts ) );
			$order->save();

			$this->sync_order_stats( $order->get_id() );

			return true;
		} catch ( Exception $exception ) {
			wc_get_logger()->error(
				sprintf( 'Failed to remediate order %d: %s', $order->get_id(), $exception->getMessage() ),
				array( 'source' => 'wcpay-fee-remediation' )
			);
			return false;
		}
	}

	/**
	 * Schedule remediation to run in the background.
	 */
	public function schedule_remediation(): void {
		$this->mark_running();
		$this->disable_dry_run();
		$this->schedule_job( self::ACTION_HOOK, time() + 10 );
	}

	/**
	 * Schedule dry run to preview what would be remediated.
	 */
	public function schedule_dry_run(): void {
		$this->mark_running();
		$this->enable_dry_run();
		$this->schedule_job( self::DRY_RUN_ACTION_HOOK, time() + 10 );
	}

	/**
	 * Check if there are any orders that need remediation.
	 *
	 * @return bool True if there are affected orders.
	 */
	public function has_affected_orders(): bool {
		return ! empty( $this->get_affected_orders( 1 ) );
	}

	/**
	 * Tell whether cutover can safely hand off canceled-authorization fee remediation.
	 *
	 * @return bool True when there is no affected data or Action Scheduler can run the migration.
	 */
	public function can_schedule_cutover_remediation(): bool {
		if ( $this->is_complete() || ! $this->has_affected_orders() ) {
			return true;
		}

		return $this->is_action_scheduler_available();
	}

	/**
	 * Ensure native owns a live remediation job after WooPayments plugin cutover.
	 *
	 * @return string Scheduling status: completed, not_needed, unavailable, already_scheduled, or scheduled.
	 */
	public function ensure_scheduled(): string {
		if ( $this->is_complete() ) {
			return 'completed';
		}

		if ( $this->is_dry_run() || $this->has_scheduled_job( self::DRY_RUN_ACTION_HOOK ) ) {
			$this->cancel_scheduled_jobs( self::DRY_RUN_ACTION_HOOK );
			$this->reset_live_processing_state();
		}

		if ( ! $this->has_affected_orders() ) {
			update_option( self::CHECK_STATE_OPTION_KEY, 'no_affected_orders', true );
			return 'not_needed';
		}

		update_option( self::CHECK_STATE_OPTION_KEY, 'has_affected_orders', true );

		if ( ! $this->is_action_scheduler_available() ) {
			wc_get_logger()->error(
				'WooPayments canceled-authorization fee remediation is required after cutover, but Action Scheduler is unavailable.',
				array( 'source' => 'wcpay-fee-remediation' )
			);
			return 'unavailable';
		}

		if ( $this->has_scheduled_job( self::ACTION_HOOK ) ) {
			return 'already_scheduled';
		}

		$this->disable_dry_run();
		if ( ! $this->schedule_job( self::ACTION_HOOK, time() + 10 ) ) {
			return 'unavailable';
		}

		$this->mark_running();
		return 'scheduled';
	}

	/**
	 * Run the affected-orders query and cache the result.
	 */
	public function check_and_cache_affected_orders(): void {
		update_option(
			self::CHECK_STATE_OPTION_KEY,
			$this->has_affected_orders() ? 'has_affected_orders' : 'no_affected_orders',
			true
		);
	}

	/**
	 * Mark remediation as complete.
	 */
	private function mark_complete(): void {
		update_option( self::STATUS_OPTION_KEY, 'completed' );
	}

	/**
	 * Mark remediation as running.
	 */
	private function mark_running(): void {
		update_option( self::STATUS_OPTION_KEY, 'running' );
	}

	/**
	 * Enable dry-run mode.
	 */
	private function enable_dry_run(): void {
		update_option( self::DRY_RUN_OPTION_KEY, true );
	}

	/**
	 * Disable dry-run mode.
	 */
	private function disable_dry_run(): void {
		delete_option( self::DRY_RUN_OPTION_KEY );
	}

	/**
	 * Clean up temporary processing options.
	 */
	private function cleanup(): void {
		delete_option( self::LAST_ORDER_ID_OPTION_KEY );
		delete_option( self::BATCH_SIZE_OPTION_KEY );
		delete_option( self::DRY_RUN_OPTION_KEY );
	}

	/**
	 * Clean up after dry run completes.
	 */
	private function cleanup_dry_run(): void {
		delete_option( self::LAST_ORDER_ID_OPTION_KEY );
		delete_option( self::BATCH_SIZE_OPTION_KEY );
		delete_option( self::DRY_RUN_OPTION_KEY );
		delete_option( self::STATUS_OPTION_KEY );
		delete_option( self::STATS_OPTION_KEY );
	}

	/**
	 * Get affected orders using HPOS tables.
	 *
	 * @param int $limit Number of orders to retrieve.
	 * @return WC_Order[] Array of order objects.
	 */
	private function get_affected_orders_hpos( int $limit ): array {
		global $wpdb;

		$last_order_id = $this->get_last_order_id();
		$orders_table  = $wpdb->prefix . 'wc_orders';
		$meta_table    = $wpdb->prefix . 'wc_orders_meta';

		$sql = "SELECT orders.id
			FROM {$orders_table} orders
			INNER JOIN {$meta_table} status_meta ON orders.id = status_meta.order_id AND status_meta.meta_key = '_intention_status' AND status_meta.meta_value = %s
			LEFT JOIN {$meta_table} fees_meta ON orders.id = fees_meta.order_id AND fees_meta.meta_key = '_wcpay_transaction_fee'
			WHERE orders.type = 'shop_order'
				AND orders.date_created_gmt >= %s
				AND (
					orders.status = 'wc-refunded'
					OR (
						orders.status = 'wc-cancelled'
						AND fees_meta.order_id IS NOT NULL
					)
				)";

		$params = array( self::CANCELED_INTENT_STATUS, self::BUG_START_DATE );

		if ( $last_order_id > 0 ) {
			$sql     .= ' AND orders.id > %d';
			$params[] = $last_order_id;
		}

		$sql     .= ' ORDER BY orders.id ASC LIMIT %d';
		$params[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$order_ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

		return $this->convert_ids_to_orders( $order_ids );
	}

	/**
	 * Get affected orders using CPT tables.
	 *
	 * @param int $limit Number of orders to retrieve.
	 * @return WC_Order[] Array of order objects.
	 */
	private function get_affected_orders_cpt( int $limit ): array {
		global $wpdb;

		$last_order_id = $this->get_last_order_id();

		$sql = "SELECT orders.ID
			FROM {$wpdb->posts} orders
			INNER JOIN {$wpdb->postmeta} status_meta ON orders.ID = status_meta.post_id AND status_meta.meta_key = '_intention_status' AND status_meta.meta_value = %s
			LEFT JOIN {$wpdb->postmeta} fees_meta ON orders.ID = fees_meta.post_id AND fees_meta.meta_key = '_wcpay_transaction_fee'
			WHERE orders.post_type IN ('shop_order', 'shop_order_placeholder')
				AND orders.post_date >= %s
				AND (
					orders.post_status = 'wc-refunded'
					OR (
						orders.post_status = 'wc-cancelled'
						AND fees_meta.post_id IS NOT NULL
					)
				)";

		$params = array( self::CANCELED_INTENT_STATUS, self::BUG_START_DATE );

		if ( $last_order_id > 0 ) {
			$sql     .= ' AND orders.ID > %d';
			$params[] = $last_order_id;
		}

		$sql     .= ' ORDER BY orders.ID ASC LIMIT %d';
		$params[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$order_ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

		if ( ! empty( $order_ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( array_map( 'absint', $order_ids ) );
		}

		return $this->convert_ids_to_orders( $order_ids );
	}

	/**
	 * Convert order IDs to order objects.
	 *
	 * @param array<int|string> $order_ids Order IDs.
	 * @return WC_Order[] Array of order objects.
	 */
	private function convert_ids_to_orders( array $order_ids ): array {
		$orders = array();
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$orders[] = $order;
			}
		}

		return $orders;
	}

	/**
	 * Filter refunds to only include WooPayments-created refunds.
	 *
	 * @param WC_Order_Refund[] $refunds Refund objects.
	 * @return WC_Order_Refund[] WooPayments-created refunds.
	 */
	private function get_wcpay_refunds( array $refunds ): array {
		return array_values(
			array_filter(
				$refunds,
				static function ( WC_Order_Refund $refund ): bool {
					return '' !== (string) $refund->get_meta( '_wcpay_refund_id', true );
				}
			)
		);
	}

	/**
	 * Sync order stats to WooCommerce Analytics.
	 *
	 * @param int $order_id Order ID.
	 */
	protected function sync_order_stats( int $order_id ): void {
		if ( ! class_exists( 'Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore' ) ) {
			return;
		}

		try {
			\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $order_id );
		} catch ( Exception $exception ) {
			wc_get_logger()->warning(
				sprintf( 'Failed to sync order %d to analytics: %s', $order_id, $exception->getMessage() ),
				array( 'source' => 'wcpay-fee-remediation' )
			);
		}
	}

	/**
	 * Delete order stats from WooCommerce Analytics.
	 *
	 * @param int $order_id Refund ID.
	 */
	protected function delete_refund_order_stats( int $order_id ): void {
		if ( ! class_exists( 'Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore' ) ) {
			return;
		}

		global $wpdb;

		try {
			$table_name = \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::get_db_table_name();
			$result     = $wpdb->delete( $table_name, array( 'order_id' => $order_id ), array( '%d' ) );
			if ( false === $result ) {
				wc_get_logger()->warning(
					sprintf( 'Failed to delete stats for refund %d: Database delete failed.', $order_id ),
					array( 'source' => 'wcpay-fee-remediation' )
				);
				return;
			}

			/**
			 * Fires when the refund stats row is deleted during WooPayments canceled-authorization fee remediation.
			 *
			 * @since 11.0.0
			 *
			 * @param int $order_id    Deleted refund ID.
			 * @param int $customer_id Customer ID. Always 0 because the refund object has already been deleted.
			 */
			do_action( 'woocommerce_analytics_delete_order_stats', $order_id, 0 );
			if ( class_exists( ReportsCache::class ) ) {
				ReportsCache::invalidate();
			}
			wc_get_logger()->info(
				sprintf( 'Deleted stats row for refund %d', $order_id ),
				array( 'source' => 'wcpay-fee-remediation' )
			);
		} catch ( Exception $exception ) {
			wc_get_logger()->warning(
				sprintf( 'Failed to delete stats for refund %d: %s', $order_id, $exception->getMessage() ),
				array( 'source' => 'wcpay-fee-remediation' )
			);
		}
	}

	/**
	 * Schedule the next live batch.
	 */
	private function schedule_next_batch(): void {
		$this->schedule_job( self::ACTION_HOOK, time() + MINUTE_IN_SECONDS );
	}

	/**
	 * Schedule the next dry-run batch.
	 */
	private function schedule_next_batch_dry_run(): void {
		$this->schedule_job( self::DRY_RUN_ACTION_HOOK, time() + MINUTE_IN_SECONDS );
	}

	/**
	 * Schedule a remediation action in the preserved legacy group.
	 *
	 * @param string $hook      Hook name.
	 * @param int    $timestamp Scheduled timestamp.
	 * @return bool True when the action was scheduled.
	 */
	private function schedule_job( string $hook, int $timestamp ): bool {
		if ( ! $this->is_action_scheduler_available() ) {
			wc_get_logger()->warning(
				sprintf( 'Action Scheduler is not available. Cannot schedule %s.', $hook ),
				array( 'source' => 'wcpay-fee-remediation' )
			);
			return false;
		}

		$action_id = as_schedule_single_action( $timestamp, $hook, array(), self::ACTION_SCHEDULER_GROUP_ID );
		if ( empty( $action_id ) ) {
			wc_get_logger()->error(
				sprintf( 'Action Scheduler failed to schedule %s for WooPayments canceled-authorization fee remediation.', $hook ),
				array( 'source' => 'wcpay-fee-remediation' )
			);
			return false;
		}

		return true;
	}

	/**
	 * Tell whether Action Scheduler queue functions are available.
	 *
	 * @return bool True when native can schedule and inspect remediation jobs.
	 */
	protected function is_action_scheduler_available(): bool {
		return function_exists( 'as_schedule_single_action' ) && function_exists( 'as_has_scheduled_action' );
	}

	/**
	 * Tell whether a remediation action is already scheduled.
	 *
	 * @param string $hook Hook name.
	 * @return bool True when an action is already scheduled.
	 */
	private function has_scheduled_job( string $hook ): bool {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return false;
		}

		return false !== as_has_scheduled_action( $hook, array(), self::ACTION_SCHEDULER_GROUP_ID );
	}

	/**
	 * Cancel scheduled remediation jobs for a hook.
	 *
	 * @param string $hook Hook name.
	 */
	private function cancel_scheduled_jobs( string $hook ): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $hook, array(), self::ACTION_SCHEDULER_GROUP_ID );
		}
	}

	/**
	 * Reset dry-run processing state before cutover starts live remediation.
	 */
	private function reset_live_processing_state(): void {
		delete_option( self::LAST_ORDER_ID_OPTION_KEY );
		delete_option( self::BATCH_SIZE_OPTION_KEY );
		delete_option( self::STATS_OPTION_KEY );
		delete_option( self::DRY_RUN_OPTION_KEY );
	}

	/**
	 * Log live remediation completion.
	 */
	private function log_completion(): void {
		$stats = $this->get_stats();
		wc_get_logger()->info(
			sprintf(
				'Remediation complete. Processed: %d, Remediated: %d, Errors: %d',
				$stats['processed'],
				$stats['remediated'],
				$stats['errors']
			),
			array( 'source' => 'wcpay-fee-remediation' )
		);
	}

	/**
	 * Log dry-run completion.
	 */
	private function log_completion_dry_run(): void {
		$stats = $this->get_stats();
		wc_get_logger()->info(
			sprintf(
				'[DRY RUN] Complete. Found %d orders that would be remediated. No changes were made. Check the WooCommerce logs for details on each order.',
				$stats['remediated']
			),
			array( 'source' => 'wcpay-fee-remediation' )
		);
	}
}
