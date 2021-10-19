<?php
/**
 * Handles task related methods.
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

/**
 * Task class.
 */
class Task {
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
	 * Title.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * Action label.
	 *
	 * @var string
	 */
	public $action_label = '';

	/**
	 * Action URL.
	 *
	 * @var string|null
	 */
	public $action_url = null;

	/**
	 * Task completion.
	 *
	 * @var bool
	 */
	protected $is_complete = false;

	/**
	 * Viewing capability.
	 *
	 * @var bool
	 */
	protected $can_view = true;

	/**
	 * Time string.
	 *
	 * @var string|null
	 */
	public $time = null;

	/**
	 * Dismissability.
	 *
	 * @var bool
	 */
	protected $is_dismissable = false;

	/**
	 * Snoozeability.
	 *
	 * @var bool
	 */
	protected $is_snoozeable = false;

	/**
	 * Snoozeability.
	 *
	 * @var string|null
	 */
	public $snoozed_until = null;

	/**
	 * Name of the dismiss option.
	 *
	 * @var string
	 */
	const DISMISSED_OPTION = 'woocommerce_task_list_dismissed_tasks';

	/**
	 * Name of the snooze option.
	 *
	 * @var string
	 */
	const SNOOZED_OPTION = 'woocommerce_task_list_remind_me_later_tasks';

	/**
	 * Duration to milisecond mapping.
	 *
	 * @var string
	 */
	protected $duration_to_ms = array(
		'day'  => DAY_IN_SECONDS * 1000,
		'hour' => HOUR_IN_SECONDS * 1000,
		'week' => WEEK_IN_SECONDS * 1000,
	);

	/**
	 * Constructor
	 *
	 * @param array $data Task list data.
	 */
	public function __construct( $data = array() ) {
		$defaults = array(
			'id'             => null,
			'title'          => '',
			'content'        => '',
			'action_label'   => __( "Let's go", 'woocommerce' ),
			'action_url'     => null,
			'is_complete'    => false,
			'can_view'       => true,
			'time'           => null,
			'is_dismissable' => false,
			'is_snoozeable'  => false,
			'snoozed_until'  => null,
		);

		$data = wp_parse_args( $data, $defaults );

		$this->id             = (string) $data['id'];
		$this->title          = (string) $data['title'];
		$this->content        = (string) $data['content'];
		$this->action_label   = (string) $data['action_label'];
		$this->action_url     = (string) $data['action_url'];
		$this->is_complete    = (bool) $data['is_complete'];
		$this->can_view       = (bool) $data['can_view'];
		$this->time           = (string) $data['time'];
		$this->is_dismissable = (bool) $data['is_dismissable'];
		$this->is_snoozeable  = (bool) $data['is_snoozeable'];

		$snoozed_tasks = get_option( self::SNOOZED_OPTION, array() );
		if ( isset( $snoozed_tasks[ $this->id ] ) ) {
			$this->snoozed_until = $snoozed_tasks[ $this->id ];
		}
	}

	/**
	 * Bool for task dismissal.
	 *
	 * @return bool
	 */
	public function is_dismissed() {
		if ( ! $this->is_dismissable ) {
			return false;
		}

		$dismissed = get_option( self::DISMISSED_OPTION, array() );

		return in_array( $this->id, $dismissed, true );
	}

	/**
	 * Dismiss the task.
	 *
	 * @return bool
	 */
	public function dismiss() {
		if ( ! $this->is_dismissable ) {
			return false;
		}

		$dismissed   = get_option( self::DISMISSED_OPTION, array() );
		$dismissed[] = $this->id;
		$update      = update_option( self::DISMISSED_OPTION, array_unique( $dismissed ) );

		if ( $update ) {
			wc_admin_record_tracks_event( 'tasklist_dismiss_task', array( 'task_name' => $this->id ) );
		}

		return $update;
	}

	/**
	 * Undo task dismissal.
	 *
	 * @return bool
	 */
	public function undo_dismiss() {
		$dismissed = get_option( self::DISMISSED_OPTION, array() );
		$dismissed = array_diff( $dismissed, array( $this->id ) );
		$update    = update_option( self::DISMISSED_OPTION, $dismissed );

		if ( $update ) {
			wc_admin_record_tracks_event( 'tasklist_undo_dismiss_task', array( 'task_name' => $this->id ) );
		}

		return $update;
	}

	/**
	 * Bool for task snoozed.
	 *
	 * @return bool
	 */
	public function is_snoozed() {
		if ( ! $this->is_snoozeable ) {
			return false;
		}

		$snoozed = get_option( self::SNOOZED_OPTION, array() );

		return isset( $snoozed[ $this->id ] ) && $snoozed[ $this->id ] > ( time() * 1000 );
	}

	/**
	 * Snooze the task.
	 *
	 * @param string $duration Duration to snooze. day|hour|week.
	 * @return bool
	 */
	public function snooze( $duration = 'day' ) {
		if ( ! $this->is_snoozeable ) {
			return false;
		}

		$snoozed              = get_option( self::SNOOZED_OPTION, array() );
		$snoozed_until        = $this->duration_to_ms[ $duration ] + ( time() * 1000 );
		$snoozed[ $this->id ] = $snoozed_until;
		$update               = update_option( self::SNOOZED_OPTION, $snoozed );

		if ( $update ) {
			if ( $update ) {
				wc_admin_record_tracks_event( 'tasklist_remindmelater_task', array( 'task_name' => $this->id ) );
				$this->snoozed_until = $snoozed_until;
			}
		}

		return $update;
	}

	/**
	 * Undo task snooze.
	 *
	 * @return bool
	 */
	public function undo_snooze() {
		$snoozed = get_option( self::SNOOZED_OPTION, array() );
		unset( $snoozed[ $this->id ] );
		$update = update_option( self::SNOOZED_OPTION, $snoozed );

		if ( $update ) {
			wc_admin_record_tracks_event( 'tasklist_undo_remindmelater_task', array( 'task_name' => $this->id ) );
		}

		return $update;
	}

	/**
	 * Bool for task visibility.
	 *
	 * @return bool
	 */
	public function is_visible() {
		return $this->can_view && ! $this->is_snoozed() && ! $this->is_dismissed();
	}

	/**
	 * Get the task as JSON.
	 *
	 * @return array
	 */
	public function get_json() {
		return array(
			'id'            => $this->id,
			'title'         => $this->title,
			'content'       => $this->content,
			'actionLabel'   => $this->action_label,
			'actionUrl'     => $this->action_url,
			'isComplete'    => $this->is_complete,
			'isVisible'     => $this->is_visible(),
			'time'          => $this->time,
			'isDismissed'   => $this->is_dismissed(),
			'isDismissable' => $this->is_dismissable,
			'isSnoozed'     => $this->is_snoozed(),
			'isSnoozeable'  => $this->is_snoozeable,
			'snoozedUntil'  => $this->snoozed_until,
		);
	}

}
