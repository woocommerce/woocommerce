<?php
/**
 * Test WC_Background_Process functionality
 *
 * A mock class for testing the WC_Background_Process functionality.
 *
 * @since 3.3
 */
class WC_Mock_Background_Process extends WC_Background_Process {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'wc_mock_background_process';
		parent::__construct();
	}

	/**
	 * Fires when the job should start.
	 *
	 * @return void
	 */
	public function dispatch() {
		parent::dispatch();
	}

	/**
	 * Return the identifier string.
	 *
	 * @return void
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Returns whether queue is empty or not.
	 *
	 * @return boolean
	 */
	public function is_queue_empty() {
		return parent::is_queue_empty();
	}

	/**
	 * Rerturns the batch data.
	 *
	 * @return void
	 */
	public function get_batch() {
		return parent::get_batch();
	}

	/**
	 * Code to execute for each item in the queue
	 *
	 * @param mixed $item Queue item to iterate over.
	 * @return bool
	 */
	protected function task( $item ) {
		// We sleep for 5 seconds to mimic a long running tast to complete some tests.
		sleep( 5 );
		update_option( $item['mock_key'], $item['mock_value'] );
		return false;
	}
}
