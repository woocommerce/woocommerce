<?php
/**
 * Action Queue Test Helper
 *
 * @package WooCommerce\Tests
 */

/**
 * WC_Admin_Test_Action_Queue class.
 */
class WC_Admin_Test_Action_Queue extends WC_Action_Queue {
	/**
	 * Actions queue.
	 *
	 * @var array
	 */
	public $actions = array();

	/**
	 * Override of WC_Action_Queue::schedule_single that just places
	 * actions into the $actions instance variable for future inspection.
	 *
	 * @param int    $timestamp Timestamp.
	 * @param string $hook Hook.
	 * @param array  $args Args.
	 * @param string $group Group.
	 * @return bool
	 */
	public function schedule_single( $timestamp, $hook, $args = array(), $group = '' ) {
		$this->actions[] = compact( 'timestamp', 'hook', 'args', 'group' );
		return true;
	}
}
