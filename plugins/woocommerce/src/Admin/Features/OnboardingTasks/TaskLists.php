<?php
/**
 * Handles storage and retrieval of task lists
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\DeprecatedExtendedTask;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\Loader;

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
		'AdditionalPayments',
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
		add_action( 'init', array( __CLASS__, 'init_tasks' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'task_list_preloaded_settings' ), 20 );
	}

	/**
	 * Check if an experiment is the treatment or control.
	 *
	 * @param string $name Name prefix of experiment.
	 * @return bool
	 */
	public static function is_experiment_treatment( $name ) {
		$anon_id        = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : '';
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking' );
		$abtest         = new \WooCommerce\Admin\Experimental_Abtest(
			$anon_id,
			'woocommerce',
			$allow_tracking
		);

		$date = new \DateTime();
		$date->setTimeZone( new \DateTimeZone( 'UTC' ) );

		$experiment_name = sprintf(
			'%s_%s_%s',
			$name,
			$date->format( 'Y' ),
			$date->format( 'm' )
		);
		return $abtest->get_variation( $experiment_name ) === 'treatment';
	}

	/**
	 * Initialize default lists.
	 */
	public static function init_default_lists() {
		self::add_list(
			array(
				'id'           => 'setup',
				'title'        => __( 'Get ready to start selling', 'woocommerce' ),
				'tasks'        => array(
					'StoreDetails',
					'Purchase',
					'Products',
					'WooCommercePayments',
					'Payments',
					'Tax',
					'Shipping',
					'Marketing',
					'Appearance',
				),
				'event_prefix' => 'tasklist_',
				'visible'      => ! self::is_experiment_treatment( 'woocommerce_tasklist_setup_experiment_1' )
					&& ! self::is_experiment_treatment( 'woocommerce_tasklist_setup_experiment_2' ),
			)
		);

		self::add_list(
			array(
				'id'                      => 'setup_experiment_1',
				'hidden_id'               => 'setup',
				'title'                   => __( 'Get ready to start selling', 'woocommerce' ),
				'tasks'                   => array(
					'StoreDetails',
					'Products',
					'WooCommercePayments',
					'Payments',
					'Tax',
					'Shipping',
					'Marketing',
					'Appearance',
				),
				'display_progress_header' => true,
				'event_prefix'            => 'tasklist_',
				'options'                 => array(
					'use_completed_title' => true,
				),
				'visible'                 => self::is_experiment_treatment( 'woocommerce_tasklist_setup_experiment_1' ),
			)
		);

		self::add_list(
			array(
				'id'           => 'setup_experiment_2',
				'hidden_id'    => 'setup',
				'title'        => __( 'Get ready to start selling', 'woocommerce' ),
				'tasks'        => array(
					'StoreCreation',
					'StoreDetails',
					'Products',
					'WooCommercePayments',
					'Payments',
					'Tax',
					'Shipping',
					'Marketing',
					'Appearance',
				),
				'event_prefix' => 'tasklist_',
				'visible'      => self::is_experiment_treatment( 'woocommerce_tasklist_setup_experiment_2' )
					&& ! self::is_experiment_treatment( 'woocommerce_tasklist_setup_experiment_1' ),
				'options'      => array(
					'use_completed_title' => true,
				),
				'display_progress_header' => true,
				'sections'     => array(
					array(
						'id'          => 'basics',
						'title'       => __( 'Cover the basics', 'woocommerce' ),
						'description' => __( 'Make sure you’ve got everything you need to start selling—from business details to products.', 'woocommerce' ),
						'image'       => plugins_url(
							'/assets/images/task_list/basics-section-illustration.png',
							WC_ADMIN_PLUGIN_FILE
						),
						'task_names'  => array( 'StoreCreation', 'StoreDetails', 'Products', 'Payments', 'WooCommercePayments' ),
					),
					array(
						'id'          => 'sales',
						'title'       => __( 'Get ready to sell', 'woocommerce' ),
						'description' => __( 'Easily set up the backbone of your store’s operations and get ready to accept first orders.', 'woocommerce' ),
						'image'       => plugins_url(
							'/assets/images/task_list/sales-section-illustration.png',
							WC_ADMIN_PLUGIN_FILE
						),
						'task_names'  => array( 'Shipping', 'Tax' ),
					),
					array(
						'id'          => 'expand',
						'title'       => __( 'Customize & expand', 'woocommerce' ),
						'description' => __( 'Personalize your store’s design and grow your business by enabling new sales channels.', 'woocommerce' ),
						'image'       => plugins_url(
							'/assets/images/task_list/expand-section-illustration.png',
							WC_ADMIN_PLUGIN_FILE
						),
						'task_names'  => array( 'Appearance', 'Marketing' ),
					),
				),
			)
		);

		self::add_list(
			array(
				'id'      => 'extended',
				'title'   => __( 'Things to do next', 'woocommerce' ),
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
				'tasks'   => array(
					'AdditionalPayments',
				),
			)
		);
		self::add_list(
			array(
				'id'           => 'setup_two_column',
				'hidden_id'    => 'setup',
				'title'        => __( 'Get ready to start selling', 'woocommerce' ),
				'tasks'        => array(
					'Products',
					'WooCommercePayments',
					'Payments',
					'Tax',
					'Shipping',
					'Marketing',
					'Appearance',
				),
				'event_prefix' => 'tasklist_',
			)
		);
		self::add_list(
			array(
				'id'           => 'extended_two_column',
				'hidden_id'    => 'extended',
				'title'        => __( 'Things to do next', 'woocommerce' ),
				'sort_by'      => array(
					array(
						'key'   => 'is_complete',
						'order' => 'asc',
					),
					array(
						'key'   => 'level',
						'order' => 'asc',
					),
				),
				'tasks'        => array(
					'AdditionalPayments',
				),
				'event_prefix' => 'extended_tasklist_',
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
		if ( ! isset( $_GET[ Task::ACTIVE_TASK_TRANSIENT ] ) || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore csrf ok.
			return;
		}
		$referer = wp_get_referer();
		if ( ! $referer || 0 !== strpos( $referer, wc_admin_url() ) ) {
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
	 * @return WP_Error|TaskList
	 */
	public static function add_list( $args ) {
		if ( isset( self::$lists[ $args['id'] ] ) ) {
			return new \WP_Error(
				'woocommerce_task_list_exists',
				__( 'Task list ID already exists', 'woocommerce' )
			);
		}

		self::$lists[ $args['id'] ] = new TaskList( $args );
		return self::$lists[ $args['id'] ];
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
				__( 'Task list ID does not exist', 'woocommerce' )
			);
		}

		self::$lists[ $list_id ]->add_task( $args );
	}

	/**
	 * Add default extended task lists.
	 *
	 * @param array $extended_tasks list of extended tasks.
	 */
	public static function maybe_add_extended_tasks( $extended_tasks ) {
		$tasks = $extended_tasks ?? array();

		foreach ( self::$lists as $task_list ) {
			if ( 'extended' !== substr( $task_list->id, 0, 8 ) ) {
				continue;
			}
			foreach ( $tasks as $args ) {
				$task = new DeprecatedExtendedTask( $task_list, $args );
				$task_list->add_task( $task );
			}
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
	 * Get all task lists.
	 *
	 * @param array $ids list of task list ids.
	 * @return array
	 */
	public static function get_lists_by_ids( $ids ) {
		return array_filter(
			self::$lists,
			function( $list ) use ( $ids ) {
				return in_array( $list->get_list_id(), $ids, true );
			}
		);
	}

	/**
	 * Get all task list ids.
	 *
	 * @return array
	 */
	public static function get_list_ids() {
		return array_keys( self::$lists );
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
				return $task_list->is_visible();
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
		if ( isset( self::$lists[ $id ] ) ) {
			return self::$lists[ $id ];
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
			if ( $id === $task->get_id() ) {
				return $task;
			}
		}

		return null;
	}

	/**
	 * Add visible list ids to component settings.
	 *
	 * @param array $settings Component settings.
	 *
	 * @return array
	 */
	public static function task_list_preloaded_settings( $settings ) {
		$settings['visibleTaskListIds'] = array_keys( self::get_visible() );

		return $settings;
	}
}
