<?php
/**
 * FakeQueue class file.
 *
 * @package WooCommerce\Testing\Tools
 */

namespace Automattic\WooCommerce\Testing\Tools;

/**
 * Fake scheduled actions queue for unit tests, it just records all the method calls
 * in a publicly accessible $methods_called property.
 *
 * To use:
 *
 * 1. The production class must get an instance of the queue in this way:
 *
 * WC()->get_instance_of(\WC_Queue::class)
 *
 * 2. Add the following in the setUp() method of the unit tests class:
 *
 * $this->register_legacy_proxy_class_mocks([\WC_Queue::class => new FakeQueue()]);
 *
 * 3. Get the instance of the fake queue with $this->get_legacy_instance_of(\WC_Queue::class)
 *    and check its methods_called field as appropriate.
 */
class FakeQueue implements \WC_Queue_Interface {

	/**
	 * Records all the method calls to this instance.
	 *
	 * @var array
	 */
	private $methods_called = array();

	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public function add( $hook, $args = array(), $group = '' ) {
		// TODO: Implement add() method.
	}

	public function schedule_single( $timestamp, $hook, $args = array(), $group = '' ) {
		$this->add_to_methods_called(
			'schedule_single',
			$args,
			$group,
			array(
				'timestamp' => $timestamp,
				'hook'      => $hook,
			)
		);
	}

	public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
		// TODO: Implement schedule_recurring() method.
	}

	public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '' ) {
		// TODO: Implement schedule_cron() method.
	}

	public function cancel( $hook, $args = array(), $group = '' ) {
		// TODO: Implement cancel() method.
	}

	public function cancel_all( $hook, $args = array(), $group = '' ) {
		// TODO: Implement cancel_all() method.
	}

	public function get_next( $hook, $args = null, $group = '' ) {
		// TODO: Implement get_next() method.
	}

	public function search( $args = array(), $return_format = OBJECT ) {
		$result = array();
		foreach ( $this->methods_called as $method_called ) {
			if ( $method_called['args'] === $args['args'] && $method_called['hook'] === $args['hook'] ) {
				$result[] = $method_called;
			}
		}
		return $result;
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing

	/**
	 * Registers a method call for this instance.
	 *
	 * @param string $method Name of the invoked method.
	 * @param array  $args Arguments passed in '$args' to the method call.
	 * @param string $group Group name passed in '$group' to the method call.
	 * @param array  $extra_args Any extra information to store about the method call.
	 */
	private function add_to_methods_called( $method, $args, $group, $extra_args = array() ) {
		$value = array(
			'method' => $method,
			'args'   => $args,
			'group'  => $group,
		);

		$this->methods_called[] = array_merge( $value, $extra_args );
	}

	/**
	 * Get the data about the methods called so far.
	 *
	 * @return array The current value of $methods_called.
	 */
	public function get_methods_called() {
		return $this->methods_called;
	}

	/**
	 * Clears the collection of the methods called so far.
	 */
	public function clear_methods_called() {
		$this->methods_called = array();
	}

}
