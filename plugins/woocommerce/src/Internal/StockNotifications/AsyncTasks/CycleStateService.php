<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;

/**
 * The service for managing a product's send cycle state.
 */
class CycleStateService {

	/**
	 * State option prefix.
	 */
	public const STATE_OPTION_PREFIX = 'wc_stock_notifications_cycle_state_';

	/**
	 * The logger instance.
	 *
	 * @var \WC_Logger_Interface
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = \wc_get_logger();
	}

	/**
	 * Parse the cycle state for a product.
	 *
	 * @param int $product_id The product ID.
	 * @return array
	 * @throws \Exception If the cycle state is invalid.
	 */
	public function get_or_initialize_cycle_state( int $product_id ): array {

		if ( $product_id <= 0 ) {
			throw new \Exception( 'Product ID is required.' );
		}

		$default_state = array(
			'cycle_start_time' => time(),
			'product_ids'      => array( $product_id ),
			'total_count'      => 0,
			'skipped_count'    => 0,
			'sent_count'       => 0,
			'failed_count'     => 0,
			'duration'         => 0,
		);

		$cycle_state = $this->get_raw_cycle_state( $product_id );
		if ( empty( $cycle_state ) ) {
			return $default_state;
		}

		if ( array_diff_key( $default_state, $cycle_state ) || empty( $cycle_state['cycle_start_time'] ) || ! is_numeric( $cycle_state['cycle_start_time'] ) ) {
			throw new \Exception( 'Invalid cycle state.' );
		}

		$cycle_state = wp_parse_args( $cycle_state, $default_state );

		return $cycle_state;
	}

	/**
	 * Get the raw cycle state.
	 *
	 * @param int $product_id The product ID.
	 * @return array
	 */
	private function get_raw_cycle_state( int $product_id ): array {
		$cycle_state = get_option( $this->get_option_name( $product_id ), false );
		if ( ! is_array( $cycle_state ) ) {
			return array();
		}

		return $cycle_state;
	}

	/**
	 * Complete the cycle.
	 *
	 * @param int|string $product_id The product ID.
	 * @param array      $cycle_state The cycle state.
	 * @return void
	 */
	public function complete_cycle( int $product_id, array $cycle_state ): void {

		$cycle_state['duration'] = time() - $cycle_state['cycle_start_time'];

		$this->logger->info(
			sprintf( 'Completed cycle for product %d. Sent: %d, Skipped: %d, Failed: %d, Duration: %d seconds. Total notifications processed: %d', $product_id, $cycle_state['sent_count'], $cycle_state['skipped_count'], $cycle_state['failed_count'], $cycle_state['duration'], $cycle_state['total_count'] ),
			array( 'source' => 'wc-customer-stock-notifications' )
		);

		$this->save_cycle_state( $product_id, array() );
	}

	/**
	 * Save the cycle state.
	 *
	 * @param int   $product_id The product ID.
	 * @param array $cycle_state The cycle state.
	 * @return bool Whether the state was saved.
	 */
	public function save_cycle_state( int $product_id, array $cycle_state ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		$current_cycle_state = $this->get_raw_cycle_state( $product_id );
		if ( $current_cycle_state === $cycle_state ) {
			return false;
		}

		if ( empty( $cycle_state ) ) {
			$result = delete_option( $this->get_option_name( $product_id ) );
		} else {
			$result = update_option( $this->get_option_name( $product_id ), $cycle_state, false );
		}

		if ( ! $result ) {
			$this->logger->error( sprintf( 'Failed to save cycle state for product %d. Cycle state: %s', $product_id, wc_print_r( $cycle_state, true ) ), array( 'source' => 'wc-customer-stock-notifications' ) );
		}

		return $result;
	}

	/**
	 * Get the option name.
	 *
	 * @param int $product_id The product ID.
	 * @return string
	 */
	private function get_option_name( int $product_id ): string {
		if ( $product_id <= 0 ) {
			return '';
		}

		return self::STATE_OPTION_PREFIX . $product_id;
	}
}
