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
	 * Option name hidden task lists.
	 */
	const HIDDEN_OPTION = 'woocommerce_task_list_hidden_lists';

	/**
	 * Option name completed task lists.
	 */
	const COMPLETED_OPTION = 'woocommerce_task_list_completed_lists';

	/**
	 * ID.
	 *
	 * @var string
	 */
	public $id = '';

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
	 * Constructor
	 *
	 * @param array $data Task list data.
	 */
	public function __construct( $data = array() ) {
		$defaults = array(
			'id'    => null,
			'title' => '',
			'tasks' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$this->id    = $data['id'];
		$this->title = $data['title'];
		$this->tasks = $data['tasks'];
	}

	/**
	 * Prefix event for backwards compatibility with tracks event naming.
	 *
	 * @param string $event_name Event name.
	 * @return string
	 */
	public function prefix_event( $event_name ) {
		if ( 'setup' === $this->id ) {
			return $event_name;
		}

		return $this->id . '_' . $event_name;
	}

	/**
	 * Check if the task list is hidden.
	 *
	 * @return bool
	 */
	public function is_hidden() {
		$hidden = get_option( self::HIDDEN_OPTION, array() );
		return in_array( $this->id, $hidden, true );
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
				return $task->is_complete ? $total + 1 : $total;
			},
			0
		);

		wc_admin_record_tracks_event(
			$this->prefix_event( 'tasklist_completed' ),
			array(
				'action'                => 'remove_card',
				'completed_task_count'  => $completed_count,
				'incomplete_task_count' => count( $viewable_tasks ) - $completed_count,
			)
		);

		$hidden   = get_option( self::HIDDEN_OPTION, array() );
		$hidden[] = $this->id;
		return update_option( self::HIDDEN_OPTION, array_unique( $hidden ) );
	}

	/**
	 * Undo hiding of the task list.
	 *
	 * @return bool
	 */
	public function show() {
		$hidden = get_option( self::HIDDEN_OPTION, array() );
		$hidden = array_diff( $hidden, array( $this->id ) );
		return update_option( self::HIDDEN_OPTION, $hidden );
	}

	/**
	 * Check if the task list is complete.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$complete = get_option( self::COMPLETED_OPTION, array() );
		return in_array( $this->id, $complete, true );
	}

	/**
	 * Add task to the task list.
	 *
	 * @param array $args Task properties.
	 */
	public function add_task( $args ) {
		$this->tasks[] = new Task( $args );
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
					return $task->can_view;
				}
			)
		);
	}

	/**
	 * Get the list for use in JSON.
	 *
	 * @return array
	 */
	public function get_json() {
		return array(
			'id'         => $this->id,
			'title'      => $this->title,
			'isHidden'   => $this->is_hidden(),
			'isVisible'  => ! $this->is_hidden(),
			'isComplete' => $this->is_complete(),
			'tasks'      => array_map(
				function( $task ) {
					return $task->get_json();
				},
				$this->get_viewable_tasks()
			),

		);
	}

}
