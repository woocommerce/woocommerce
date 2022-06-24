<?php
/**
 * Implements abstract class for action scheduler based batch processing. Updates using this class must be tolerant of same record getting processed more than once.
 */

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Class BatchProcessor
 *
 * @package Automattic\WooCommerce\DataBase
 */
abstract class BatchProcessor {

	/**
	 * A user friendly name for this process.
	 *
	 * @return string Name of the process.
	 */
	abstract protected function get_name() : string;

	/**
	 * A user friendly description for this process.
	 *
	 * @return string Description.
	 */
	abstract protected function get_description() : string;

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	abstract public function process_for_batch( array $batch );

	/**
	 * Get total number of pending records that require processing.
	 *
	 * @return int Number of pending records.
	 */
	abstract public function get_total_pending_count() : int;

	/**
	 * Returns the batch with records that needs to be processed for a given size.
	 *
	 * @param int   $size Size of the batch.
	 * @param mixed $last_processed Identifier of record that was last processed.
	 *
	 * @return array Batch of records.
	 */
	abstract public function get_batch_data( int $size, $last_processed ) : array;

	/**
	 * Default batch size to use.
	 *
	 * @return int Default batch size.
	 */
	abstract public function get_default_batch_size() : int;

	/**
	 * Executed by updatecontroller when update is complete. Child classes may want to do custom action here, such as showing notices if they want to.
	 */
	public function mark_process_complete() {
		// No op. Child classes may want to override this method is something needs to be done on update complete.
	}
}
