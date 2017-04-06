<?php
/**
 * Background Emailer
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle emails
 * in the background.
 *
 * @class    WC_Background_Emailer
 * @version  3.0.1
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once( dirname( __FILE__ ) . '/libraries/wp-async-request.php' );
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once( dirname( __FILE__ ) . '/libraries/wp-background-process.php' );
}

/**
 * WC_Background_Emailer Class.
 */
class WC_Background_Emailer extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'wc_emailer';

	/**
	 * Initiate new background process
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'shutdown', array( $this, 'dispatch_queue' ) );
	}

	/**
	 * Schedule fallback event.
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param string $callback Update callback function
	 * @return mixed
	 */
	protected function task( $callback ) {
		if ( isset( $callback['filter'], $callback['args'] ) ) {
			WC_Emails::send_queued_transactional_email( $callback['filter'], $callback['args'] );
		}
		return false;
	}

	/**
	 * Save and run queue.
	 */
	public function dispatch_queue() {
		if ( ! empty( $this->data ) ) {
			$this->save()->dispatch();
		}
	}
}
