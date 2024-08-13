<?php

namespace Automattic\WooCommerce\Blueprint;

/**
 * Class JsonResultFormatter
 */
class JsonResultFormatter {
	/**
	 * The results to format.
	 *
	 * @var StepProcessorResult[]
	 */
	private array $results;

	/**
	 * JsonResultFormatter constructor.
	 *
	 * @param array $results The results to format.
	 */
	public function __construct( array $results ) {
		$this->results = $results;
	}

	/**
	 * Format the results.
	 *
	 * @param string $message_type The message type to format.
	 *
	 * @return array
	 */
	public function format( $message_type = 'all' ) {
		$data = array(
			'is_success' => $this->is_success(),
			'messages'   => array(),
		);

		foreach ( $this->results as $result ) {
			$step_name = $result->get_step_name();
			foreach ( $result->get_messages( $message_type ) as $message ) {
				if ( ! isset( $data['messages'][ $message['type'] ] ) ) {
					$data['messages'][ $message['type'] ] = array();
				}
				$data['messages'][ $message['type'] ][] = array(
					'step'    => $step_name,
					'type'    => $message['type'],
					'message' => $message['message'],
				);
			}
		}

		return $data;
	}

	/**
	 * Check if all results are successful.
	 *
	 * @return bool True if all results are successful, false otherwise.
	 */
	public function is_success() {
		foreach ( $this->results as $result ) {
			$is_success = $result->is_success();
			if ( ! $is_success ) {
				return false;
			}
		}
		return true;
	}
}
