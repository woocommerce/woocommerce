<?php
/**
 * Implements abstract class for action scheduler based Updates. Updates using this class must be tolerant of same record getting processed more than once.
 */

namespace Automattic\WooCommerce\DataBase;

/**
 * Class WCActionUpdater
 *
 * @package Automattic\WooCommerce\DataBase
 */
abstract class WCActionUpdater {

	/**
	 * A name for this update.
	 *
	 * @return string
	 */
	abstract protected function get_update_name() : string;

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	abstract protected function process_for_batch( array $batch );

	/**
	 * Get total number of pending records that require update.
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
	abstract protected function get_batch_data( int $size, $last_processed ) : array;

	/**
	 * Default batch size to use.
	 *
	 * @return int Default batch size.
	 */
	abstract protected function get_default_batch_size() : int;

	/**
	 * Log an error if happens during migration processing.
	 *
	 * @param \Exception $error Exception object.
	 */
	abstract protected function log_error( \Exception $error ) : void;

	/**
	 * Process current batch.
	 *
	 * @return bool Whether rescheduling is needed and batch still has more data.
	 */
	public function process_batch() {
		$details    = $this->get_update_details();
		$time_start = microtime( true );
		$batch      = $this->get_batch_data( $details['current_batch_size'], $details['last_processed'] );
		if ( empty( $batch ) ) {
			return false;
		}
		try {
			$this->process_for_batch( $batch );
			$time_taken = microtime( true ) - $time_start;
			$this->update_progress_status( $batch, $time_taken );
		} catch ( \Exception $exception ) {
			$this->log_error( $exception );
			$this->update_progress_status( $batch, $time_taken, $exception );
		}
		return true;
	}

	/**
	 * Update the progress status after a batch has completed processing.
	 *
	 * @param array           $batch Batch that finished processing.
	 * @param float           $time_taken Time take by the batch to complete processing.
	 * @param \Exception|null $last_error Exception object in processing the batch, if there was one.
	 */
	protected function update_progress_status( $batch, float $time_taken, \Exception $last_error = null ) {
		$current_status                      = $this->get_update_details();
		$current_status['total_time_spent'] += $time_taken;
		$current_status['last_processed']    = null !== $last_error ?
			$this->get_last_processed_in_errored_batch( $last_error, $batch ) : end( $batch );
		$current_status['last_error']        = null !== $last_error ? $last_error->getMessage() : null;
		update_option( $this->get_update_option_name(), $current_status, false );
	}

	/**
	 * Get status for this update..
	 *
	 * @return mixed Update status.
	 */
	protected function get_update_details() {
		return get_option(
			$this->get_update_option_name(),
			array(
				'last_processed'     => null,
				'total_time_spent'   => 0,
				'current_batch_size' => $this->get_default_batch_size(),
				'last_error'         => null,
			)
		);
	}

	/**
	 * Returns last value processed in errored batch. Child class may want to overwrite this to return record from where to resume migration.
	 *
	 * @param \Exception $last_error Exception object of last error.
	 * @param array      $batch Array of records to process update for.
	 *
	 * @return mixed Last processed record.
	 */
	protected function get_last_processed_in_errored_batch( \Exception $last_error, array $batch ) {
		return end( $batch );
	}

	/**
	 * Returns name of update option.
	 *
	 * @return string
	 */
	private function get_update_option_name() {
		return "wc_update_{$this->get_update_name()}";
	}

	/**
	 * Executed by updatecontroller when update is complete. Child classes may want to do custom action here, such as showing notices if they want to.
	 */
	public function mark_update_complete() {
		// No op. Child classes may want to override this method is something needs to be done on update complete.
	}
}
