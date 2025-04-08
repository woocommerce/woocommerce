<?php

namespace Automattic\WooCommerce\Blueprint\ResultFormatters;

use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use function WP_CLI\Utils\format_items;

/**
 * Class CliResultFormatter
 */
class CliResultFormatter {
	/**
	 * The results to format.
	 *
	 * @var StepProcessorResult[]
	 */
	private array $results;

	/**
	 * CliResultFormatter constructor.
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
	 * @return void
	 */
	public function format( $message_type = 'debug' ) {
		$header = array( 'Step Processor', 'Type', 'Message' );
		$items  = array();

		foreach ( $this->results as $result ) {
			$step_name = $result->get_step_name();
			foreach ( $result->get_messages( $message_type ) as $message ) {
				$items[] = array(
					'Step Processor' => $step_name,
					'Type'           => $message['type'],
					'Message'        => $message['message'],
				);
			}
		}

		$format_items_exist = function_exists('format_items');

		if ( $format_items_exist ) {
			format_items( 'table', $items, $header );
		} else {
			throw new \Exception( 'WP CLI Utils not found' );
		}
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
