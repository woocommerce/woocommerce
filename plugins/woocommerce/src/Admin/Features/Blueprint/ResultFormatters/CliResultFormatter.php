<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use function WP_CLI\Utils\format_items;

class CliResultFormatter {
	/**
	 * @var StepProcessorResult[]
	 */
	private array $results;
	public function __construct(array $results) {
		$this->results = $results;
	}

	public function format($message_type = 'debug') {
		$header = array('Step Processor', 'Type', 'Message');
		$items = array();

		foreach ($this->results as $result) {
			$step_name = $result->get_step_name();
			foreach ($result->get_messages($message_type) as $message) {
				$items[] = array(
					'Step Processor' => $step_name,
					'Type' => $message['type'],
					'Message' => $message['message'],
				);
			}
		}

		format_items('table', $items, $header);
	}

	public function is_success() {
	    foreach ($this->results as $result) {
			$is_success = $result->is_success();
			if (!$is_success) {
				return false;
			}
	    }
		return true;
	}
}
