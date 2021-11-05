<?php
/**
 * Handles storage and retrieval of task lists
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
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
	 * Boolean value to indicate if default tasks have been added.
	 *
	 * @var boolean
	 */
	protected static $default_tasks_loaded = false;

	/**
	 * Array of default tasks.
	 *
	 * @var array
	 */
	const DEFAULT_TASKS = array(
		'StoreDetails',
		'Purchase',
		'Products',
		'WooCommercePayments',
		'Payments',
		'Tax',
		'Shipping',
		'Marketing',
		'Appearance',
	);

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
	 * Initialize the task lists.
	 */
	public static function init() {
		self::init_default_lists();
		add_action( 'admin_init', array( __CLASS__, 'set_active_task' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'init_tasks' ) );
	}

	/**
	 * Initialize default lists.
	 */
	public static function init_default_lists() {
		self::add_list(
			array(
				'id'    => 'setup',
				'title' => __( 'Get ready to start selling', 'woocommerce-admin' ),
			)
		);

		self::add_list(
			array(
				'id'      => 'extended',
				'title'   => __( 'Things to do next', 'woocommerce-admin' ),
				'sort_by' => array(
					array(
						'key'   => 'is_complete',
						'order' => 'asc',
					),
					array(
						'key'   => 'level',
						'order' => 'asc',
					),
				),
			)
		);
	}

	/**
	 * Initialize tasks.
	 */
	public static function init_tasks() {
		foreach ( self::DEFAULT_TASKS as $task ) {
			$class = 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\\' . $task;
			if ( ! method_exists( $class, 'init' ) ) {
				continue;
			}
			$class::init();
		}
	}

	/**
	 * Temporarily store the active task to persist across page loads when neccessary.
	 * Most tasks do not need this.
	 */
	public static function set_active_task() {
		if ( ! isset( $_GET[ Task::ACTIVE_TASK_TRANSIENT ] ) ) { // phpcs:ignore csrf ok.
			return;
		}

		$task_id = sanitize_title_with_dashes( wp_unslash( $_GET[ Task::ACTIVE_TASK_TRANSIENT ] ) ); // phpcs:ignore csrf ok.

		$task = self::get_task( $task_id );

		if ( ! $task ) {
			return;
		}

		$task->set_active();
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
		if ( ! apply_filters( 'woocommerce_admin_onboarding_tasks_add_default_tasks', ! self::$default_tasks_loaded ) ) {
			return;
		}

		self::$default_tasks_loaded = true;

		foreach ( self::DEFAULT_TASKS as $task ) {
			$class = 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\\' . $task;
			self::add_task( 'setup', $class::get_task() );
		}
	}

	/**
	 * Add default extended task lists.
	 *
	 * @param array $extended_tasks list of extended tasks.
	 */
	public static function maybe_add_extended_tasks( $extended_tasks ) {
		$tasks = $extended_tasks ? $extended_tasks : array();

		foreach ( $tasks as $extended_task ) {
			self::add_task( $extended_task['list_id'], $extended_task );
		}
	}

	/**
	 * Get all task lists.
	 *
	 * @return array
	 */
	public static function get_lists() {
		return self::$lists;
	}

	/**
	 * Clear all task lists.
	 */
	public static function clear_lists() {
		self::$lists = array();
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
		$task_list = $task_list_id ? self::get_list( $task_list_id ) : null;

		if ( $task_list_id && ! $task_list ) {
			return null;
		}

		$tasks_to_search = $task_list ? $task_list->tasks : array_reduce(
			self::get_lists(),
			function ( $all, $curr ) {
				return array_merge( $all, $curr->tasks );
			},
			array()
		);

		foreach ( $tasks_to_search as $task ) {
			if ( $id === $task->id ) {
				return $task;
			}
		}

		return null;
	}
}
