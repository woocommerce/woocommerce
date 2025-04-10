<?php

namespace Automattic\WooCommerce\Blueprint\Tests\stubs\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

class DummyStep extends Step {
	public static function get_step_name(): string {
		return 'dummy';
	}

	public static function get_schema( int $version = 1 ): array {
		return array(
			'type'       => 'object',
			'additionalProperties' => false,
			'properties' => array(
				'step' => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
			),
			'required'   => array( 'step' ),
		);
	}

	public function prepare_json_array(): array {
		return array(
			'step' => static::get_step_name(),
		);
	}
}
