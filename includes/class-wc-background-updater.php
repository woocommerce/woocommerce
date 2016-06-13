<?php
/**
 * Background Updater
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle DB
 * updates in the background.
 *
 * @class    WC_Background_Updater
 * @version  2.6.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'libraries/wp-async-request.php' );
include_once( 'libraries/wp-background-process.php' );

/**
 * WC_Background_Updater Class.
 */
class WC_Background_Updater extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'wc_updater';

	/**
	 * @var string
	 */
	protected $error = '';

	/**
	 * Dispatch updater.
	 *
	 * Updater will still run via cron job if this fails for any reason.
	 */
	public function dispatch() {
		$dispatched = parent::dispatch();

		if ( is_wp_error( $dispatched ) ) {
			$this->error = $dispatched->get_error_message();
			add_action( 'admin_notices', array( $this, 'dispatch_error' ) );
		}
	}

	/**
	 * Schedule event
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Error shown when the updater cannot dispatch.
	 */
	public function dispatch_error() {
		echo '<div class="error"><p>' . __( 'Unable to dispatch WooCommerce updater:', 'woocommerce' ) . ' ' . esc_html( $this->error ) . '</p></div>';
	}

	/**
	 * Is the updater running?
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
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
		if ( ! defined( 'WC_UPDATING' ) ) {
			define( 'WC_UPDATING', true );
		}

		$logger = new WC_Logger();

		include_once( 'wc-update-functions.php' );

		if ( is_callable( $callback ) ) {
			$logger->add( 'wc_db_updates', sprintf( 'Running %s callback', $callback ) );
			call_user_func( $callback );
			$logger->add( 'wc_db_updates', sprintf( 'Finished %s callback', $callback ) );
		} else {
			$logger->add( 'wc_db_updates', sprintf( 'Could not find %s callback', $callback ) );
		}

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		$logger = new WC_Logger();
		$logger->add( 'wc_db_updates', 'Data update complete' );
		WC_Install::update_db_version();
		parent::complete();
	}
}
