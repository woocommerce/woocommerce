<?php
/**
 * Implements abstract class for action scheduler based batch processing. Updates using this class must be tolerant of same record getting processed more than once.
 */

namespace Automattic\WooCommerce\Internal\BatchProcessing;

/**
 * Class BatchProcessor
 *
 * @package Automattic\WooCommerce\DataBase
 */
interface BatchProcessorInterface {

	/**
	 * A user friendly name for this process.
	 *
	 * @return string Name of the process.
	 */
	public function get_name() : string;

	/**
	 * A user friendly description for this process.
	 *
	 * @return string Description.
	 */
	public function get_description() : string;

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	public function process_batch( array $batch );

	/**
	 * Get total number of pending records that require processing.
	 *
	 * @return int Number of pending records.
	 */
	public function get_total_pending_count() : int;

	/**
	 * Returns the batch with records that needs to be processed for a given size.
	 *
	 * @param int $size Size of the batch.
	 *
	 * @return array Batch of records.
	 */
	public function get_next_batch_to_process( int $size ) : array;

	/**
	 * Default batch size to use.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size() : int;
}
