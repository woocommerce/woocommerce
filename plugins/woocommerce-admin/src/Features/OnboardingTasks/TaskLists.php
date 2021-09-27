<?php
/**
 * Handles storage and retrieval of task lists
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Appearance;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Marketing;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Payments;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Purchase;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Shipping;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\StoreDetails;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Tax;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;
use Automattic\WooCommerce\Admin\Loader;

/**
 * Task Lists class.
 */
class TaskLists {
	/**
	 * Class instance.
	 *
	 * @var TaskLists instance
	 */
	protected static $instance = null;

	/**
	 * An array of all registered lists.
	 *
	 * @var array
	 */
	protected static $lists = array();

	/**
	 * Get class instance.
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Add a task list.
	 *
	 * @param array $args Task list properties.
	 * @return WP_Error|Task
	 */
	public static function add_list( $args ) {
		if ( isset( self::$lists[ $args['id'] ] ) ) {
			return new \WP_Error(
				'woocommerce_task_list_exists',
				__( 'Task list ID already exists', 'woocommerce-admin' )
			);
		}

		self::$lists[ $args['id'] ] = new TaskList( $args );
	}

	/**
	 * Add task to a given task list.
	 *
	 * @param string $list_id List ID to add the task to.
	 * @param array  $args Task properties.
	 * @return WP_Error|Task
	 */
	public static function add_task( $list_id, $args ) {
		if ( ! isset( self::$lists[ $list_id ] ) ) {
			return new \WP_Error(
				'woocommerce_task_list_invalid_list',
				__( 'Task list ID does not exist', 'woocommerce-admin' )
			);
		}

		self::$lists[ $list_id ]->add_task( $args );
	}

	/**
	 * Add default task lists.
	 */
	public static function maybe_add_default_tasks() {
		$added = isset( self::$lists['setup'] );

		if ( ! apply_filters( 'woocommerce_admin_onboarding_tasks_add_default_tasks', ! $added ) ) {
			return;
		}

		self::add_list(
			array(
				'id'    => 'setup',
				'title' => __( 'Get ready to start selling', 'woocommerce-admin' ),
			)
		);

		self::add_task( 'setup', StoreDetails::get_task() );
		self::add_task( 'setup', Purchase::get_task() );
		self::add_task( 'setup', Products::get_task() );
		self::add_task( 'setup', WooCommercePayments::get_task() );
		self::add_task( 'setup', Payments::get_task() );
		self::add_task( 'setup', Tax::get_task() );
		self::add_task( 'setup', Shipping::get_task() );
		self::add_task( 'setup', Marketing::get_task() );
		self::add_task( 'setup', Appearance::get_task() );
	}

	/**
	 * Get all task lists.
	 *
	 * @return array
	 */
	public static function get_lists() {
		self::maybe_add_default_tasks();
		return self::$lists;
	}

	/**
	 * Get visible task lists.
	 */
	public static function get_visible() {
		return array_filter(
			self::get_lists(),
			function ( $task_list ) {
				return ! $task_list->is_hidden();
			}
		);
	}


	/**
	 * Retrieve a task list by ID.
	 *
	 * @param String $id Task list ID.
	 *
	 * @return TaskList|null
	 */
	public static function get_list( $id ) {
		foreach ( self::get_lists() as $task_list ) {
			if ( $task_list->id === $id ) {
				return $task_list;
			}
		}

		return null;
	}

	/**
	 * Retrieve single task.
	 *
	 * @param String $id Task ID.
	 * @param String $task_list_id Task list ID.
	 *
	 * @return Object
	 */
	public static function get_task( $id, $task_list_id = null ) {
		$task_list = $task_list_id ? self::get_task_list_by_id( $task_list_id ) : null;

		if ( $task_list_id && ! $task_list ) {
			return null;
		}

		$tasks_to_search = $task_list ? $task_list['tasks'] : array_reduce(
			self::get_lists(),
			function ( $all, $curr ) {
				return array_merge( $all, $curr['tasks'] );
			},
			array()
		);

		foreach ( $tasks_to_search as $task ) {
			if ( $id === $task['id'] ) {
				return $task;
			}
		}

		return null;
	}
}
