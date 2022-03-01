<?php
/**
 * Handles storage and retrieval of a task list
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Task List class.
 */
class TaskList {
	/**
	 * Task traits.
	 */
	use TaskTraits;

	/**
	 * Option name hidden task lists.
	 */
	const HIDDEN_OPTION = 'woocommerce_task_list_hidden_lists';

	/**
	 * Option name of completed task lists.
	 */
	const COMPLETED_OPTION = 'woocommerce_task_list_completed_lists';

	/**
	 * ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * ID.
	 *
	 * @var string
	 */
	public $hidden_id = '';

	/**
	 * Title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Tasks.
	 *
	 * @var array
	 */
	public $tasks = array();

	/**
	 * Sort keys.
	 *
	 * @var array
	 */
	public $sort_by = array();

	/**
	 * Event prefix.
	 *
	 * @var string|null
	 */
	public $event_prefix = null;

	/**
	 * Constructor
	 *
	 * @param array $data Task list data.
	 */
	public function __construct( $data = array() ) {
		$defaults = array(
			'id'           => null,
			'hidden_id'    => null,
			'title'        => '',
			'tasks'        => array(),
			'sort_by'      => array(),
			'event_prefix' => null,
		);

		$data = wp_parse_args( $data, $defaults );

		$this->id           = $data['id'];
		$this->hidden_id    = $data['hidden_id'];
		$this->title        = $data['title'];
		$this->sort_by      = $data['sort_by'];
		$this->event_prefix = $data['event_prefix'];

		foreach ( $data['tasks'] as $task_name ) {
			$class = 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\\' . $task_name;
			$task  = new $class( $this );
			$this->add_task( $task );
		}
	}

	/**
	 * Check if the task list is hidden.
	 *
	 * @return bool
	 */
	public function is_hidden() {
		$hidden = get_option( self::HIDDEN_OPTION, array() );
		return in_array( $this->hidden_id ? $this->hidden_id : $this->id, $hidden, true );
	}

	/**
	 * Check if the task list is visible.
	 *
	 * @return bool
	 */
	public function is_visible() {
		return ! $this->is_hidden();
	}

	/**
	 * Hide the task list.
	 *
	 * @return bool
	 */
	public function hide() {
		if ( $this->is_hidden() ) {
			return;
		}

		$viewable_tasks  = $this->get_viewable_tasks();
		$completed_count = array_reduce(
			$viewable_tasks,
			function( $total, $task ) {
				return $task->is_complete() ? $total + 1 : $total;
			},
			0
		);

		$this->record_tracks_event(
			'completed',
			array(
				'action'                => 'remove_card',
				'completed_task_count'  => $completed_count,
				'incomplete_task_count' => count( $viewable_tasks ) - $completed_count,
			)
		);

		$hidden   = get_option( self::HIDDEN_OPTION, array() );
		$hidden[] = $this->hidden_id ? $this->hidden_id : $this->id;
		return update_option( self::HIDDEN_OPTION, array_unique( $hidden ) );
	}

	/**
	 * Undo hiding of the task list.
	 *
	 * @return bool
	 */
	public function unhide() {
		$hidden = get_option( self::HIDDEN_OPTION, array() );
		$hidden = array_diff( $hidden, array( $this->hidden_id ? $this->hidden_id : $this->id ) );
		return update_option( self::HIDDEN_OPTION, $hidden );
	}

	/**
	 * Check if all viewable tasks are complete.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$viewable_tasks = $this->get_viewable_tasks();

		return array_reduce(
			$viewable_tasks,
			function( $is_complete, $task ) {
				return ! $task->is_complete() ? false : $is_complete;
			},
			true
		);
	}

	/**
	 * Check if a task list has previously been marked as complete.
	 *
	 * @return bool
	 */
	public function has_previously_completed() {
		$complete = get_option( self::COMPLETED_OPTION, array() );
		return in_array( $this->get_list_id(), $complete, true );
	}

	/**
	 * Add task to the task list.
	 *
	 * @param Task $task Task class.
	 */
	public function add_task( $task ) {
		if ( ! is_subclass_of( $task, 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task' ) ) {
			return new \WP_Error(
				'woocommerce_task_list_invalid_task',
				__( 'Task is not a subclass of `Task`', 'woocommerce-admin' )
			);
		}
		if ( array_search( $task, $this->tasks ) ) {
			return;
		}

		$this->tasks[] = $task;
	}

	/**
	 * Get only visible tasks in list.
	 *
	 * @param string $task_id id of task.
	 * @return Task
	 */
	public function get_task( $task_id ) {
		return current(
			array_filter(
				$this->tasks,
				function( $task ) use ( $task_id ) {
					return $task->get_id() === $task_id;
				}
			)
		);
	}

	/**
	 * Get only visible tasks in list.
	 *
	 * @return array
	 */
	public function get_viewable_tasks() {
		return array_values(
			array_filter(
				$this->tasks,
				function( $task ) {
					return $task->can_view();
				}
			)
		);
	}

	/**
	 * Track list completion of viewable tasks.
	 */
	public function possibly_track_completion() {
		if ( ! $this->is_complete() ) {
			return;
		}

		if ( $this->has_previously_completed() ) {
			return;
		}

		$completed_lists   = get_option( self::COMPLETED_OPTION, array() );
		$completed_lists[] = $this->get_list_id();
		update_option( self::COMPLETED_OPTION, $completed_lists );
		$this->record_tracks_event( 'tasks_completed' );
	}

	/**
	 * Sorts the attached tasks array.
	 *
	 * @param array $sort_by list of columns with sort order.
	 * @return TaskList returns $this, for chaining.
	 */
	public function sort_tasks( $sort_by = array() ) {
		$sort_by = count( $sort_by ) > 0 ? $sort_by : $this->sort_by;
		if ( 0 !== count( $sort_by ) ) {
			usort(
				$this->tasks,
				function( $a, $b ) use ( $sort_by ) {
					return Task::sort( $a, $b, $sort_by );
				}
			);
		}
		return $this;
	}

	/**
	 * Prefix event for track event naming.
	 *
	 * @param string $event_name Event name.
	 * @return string
	 */
	public function prefix_event( $event_name ) {
		if ( null !== $this->event_prefix ) {
			return $this->event_prefix . $event_name;
		}
		return $this->get_list_id() . '_tasklist_' . $event_name;
	}

	/**
	 * Get the list for use in JSON.
	 *
	 * @return array
	 */
	public function get_json() {
		$this->possibly_track_completion();
		return array(
			'id'          => $this->get_list_id(),
			'title'       => $this->title,
			'isHidden'    => $this->is_hidden(),
			'isVisible'   => $this->is_visible(),
			'isComplete'  => $this->is_complete(),
			'tasks'       => array_map(
				function( $task ) {
					return $task->get_json();
				},
				$this->get_viewable_tasks()
			),
			'eventPrefix' => $this->prefix_event( '' ),
		);
	}
}
