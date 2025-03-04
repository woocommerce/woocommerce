<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caching;

/**
 * An action that can be scheduled to run in the background.
 */
class BackgroundAction {

	/**
	 * ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Callback.
	 *
	 * @var callable
	 */
	private $callback;
	
	/**
	 * Interval in seconds.
	 *
	 * @var int
	 */
	private $interval_in_seconds;

	/**
	 * Create a new BackgroundAction instance.
	 *
	 * @var array {
	 *     An array of action arguments.
	 *
	 *     @type string   $hook                Hook name used to identify the action.
	 *     @type callable $callback            The callback function to run when the action is scheduled.
	 *     @type int      $interval_in_seconds How long before this cache should be refreshed.
	 * }
	 */
	public function __construct( $args ) {
		$this->validate_action_args( $args );

		$args = wp_parse_args(
			$args,
			array(
				'interval_in_seconds' => DAY_IN_SECONDS,
			)
		);

		$this->hook           = $args['hook'];
		$this->callback            = $args['callback'];
		$this->interval_in_seconds = $args['interval_in_seconds'];

		$this->init();
	}

	/**
	 * Initialize the action.
	 *
	 * @return void
	 */
	private function init() {
		add_action( $this->hook, array( $this, 'run_callback' ) );
	}

	/**
	 * Validate and throw errors on action args if required args are missing.
	 *
	 * @throws \InvalidArgumentException
	 */
	private function validate_action_args( $args ) {
		if ( ! isset( $args['hook'] ) ) {
			throw new \InvalidArgumentException( __( 'Background action must include a hook name argument', 'woocommerce' ) );
		}

		if ( ! isset( $args['callback' ] ) || ! is_callable( $args['callback'] ) ) {
			throw new \InvalidArgumentException( __( 'Background action must include a callable callback argument', 'woocommerce' ) );
		}
	}

	/**
	 * Get the ID of the action.
	 *
	 * @return string
	 */
	public function get_hook() {
		return $this->hook;
	}

	/**
	 * Get the interval of the action.
	 *
	 * @return int
	 */
	public function get_interval() {
		return $this->interval_in_seconds;
	}

	/**
	 * Run a background action.
	 *
	 * @return void
	 */
	public function run_callback() {
		call_user_func( $this->callback );
	}

	/**
	 * Schedule the action.
	 *
	 * @return void
	 */
	public function schedule() {
		as_schedule_recurring_action( time(), $this->get_interval(), $this->get_hook(), array(), BackgroundScheduler::BACKGROUND_GROUP_NAME );
	}
}
