<?php

namespace Automattic\WooCommerce\Blueprint;

use InvalidArgumentException;

/**
 * A class returned by StepProcessor classes containing result of the process and messages.
 */
class StepProcessorResult {
	const MESSAGE_TYPES = array( 'error', 'info', 'debug' );

	/**
	 * Messages
	 *
	 * @var array $messages
	 */
	private array $messages = array();

	/**
	 * Indicate whether the process was success or not
	 *
	 * @var bool $success
	 */
	private bool $success;

	/**
	 * Step name
	 *
	 * @var string $step_name
	 */
	private string $step_name;

	/**
	 * Construct.
	 *
	 * @param bool   $success Indicate whether the process was success or not.
	 * @param string $step_name The name of the step.
	 */
	public function __construct( bool $success, string $step_name ) {
		$this->success   = $success;
		$this->step_name = $step_name;
	}

	/**
	 * Get messages.
	 *
	 * @param string $step_name The name of the step.
	 *
	 * @return void
	 */
	public function set_step_name( $step_name ) {
		$this->step_name = $step_name;
	}

	/**
	 * Create a new instance with $success = true.
	 *
	 * @param string $stp_name The name of the step.
	 *
	 * @return StepProcessorResult
	 */
	public static function success( string $stp_name ): self {
		return ( new self( true, $stp_name ) );
	}


	/**
	 * Add a new message.
	 *
	 * @param string $message message.
	 * @param string $type one of error, info.
	 *
	 * @throws InvalidArgumentException When incorrect type is given.
	 * @return void
	 */
	public function add_message( string $message, string $type = 'error' ) {
		if ( ! in_array( $type, self::MESSAGE_TYPES, true ) ) {
			// phpcs:ignore
			throw new InvalidArgumentException( "{$type} is not allowed. Type must be one of " . implode( ',', self::MESSAGE_TYPES ) );
		}

		$this->messages[] = compact( 'message', 'type' );
	}

	/**
	 * Add a new error message.
	 *
	 * @param string $message message.
	 *
	 * @return void
	 */
	public function add_error( string $message ) {
		$this->add_message( $message );
	}

	/**
	 * Add a new debug message.
	 *
	 * @param string $message message.
	 *
	 * @return void
	 */
	public function add_debug( string $message ) {
		$this->add_message( $message, 'debug' );
	}


	/**
	 * Add a new info message.
	 *
	 * @param string $message message.
	 *
	 * @return void
	 */
	public function add_info( string $message ) {
		$this->add_message( $message, 'info' );
	}

	/**
	 * Filter messages.
	 *
	 * @param string $type one of all, error, and info.
	 *
	 * @return array
	 */
	public function get_messages( string $type = 'all' ): array {
		if ( 'all' === $type ) {
			return $this->messages;
		}

		return array_filter(
			$this->messages,
			function ( $message ) use ( $type ) {
				return $type === $message['type'];
			}
		);
	}

	/**
	 * Check to see if the result was success.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return true === $this->success && 0 === count( $this->get_messages( 'error' ) );
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string The name of the step.
	 */
	public function get_step_name() {
		return $this->step_name;
	}
}
