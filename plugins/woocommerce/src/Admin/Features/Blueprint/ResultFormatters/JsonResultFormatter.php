<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class JsonResultFormatter {
	/**
	 * @var StepProcessorResult[]
	 */
	private array $results;
	public function __construct(array $results) {
		$this->results = $results;
	}

	public function format($message_type = 'all') {
		$data = array(
			'is_success' => $this->is_success(),
			'messages' => array()
		);

		foreach ($this->results as $result) {
			$step_name = $result->get_step_name();
			foreach ($result->get_messages($message_type) as $message) {
				$data['messages'][$message['type']] = array(
					'step' => $step_name,
					'type' => $message['type'],
					'message' => $message['message'],
				);
			}
		}

		return $data;
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
