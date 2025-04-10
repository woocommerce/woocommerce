<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\ResultFormatters;

use Automattic\WooCommerce\Blueprint\ResultFormatters\JsonResultFormatter;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use PHPUnit\Framework\TestCase;
use Mockery;

class JsonResultFormatterTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_format_all_message_types() {
		$mockResult1 = Mockery::mock(StepProcessorResult::class);
		$mockResult1->shouldReceive('get_step_name')
		            ->andReturn('Step 1');
		$mockResult1->shouldReceive('get_messages')
		            ->with('all')
		            ->andReturn([
			            ['type' => 'info', 'message' => 'Info message 1'],
			            ['type' => 'error', 'message' => 'Error message 1'],
		            ]);
		$mockResult1->shouldReceive('is_success')
		            ->andReturn(true);

		$mockResult2 = Mockery::mock(StepProcessorResult::class);
		$mockResult2->shouldReceive('get_step_name')
		            ->andReturn('Step 2');
		$mockResult2->shouldReceive('get_messages')
		            ->with('all')
		            ->andReturn([
			            ['type' => 'debug', 'message' => 'Debug message 1'],
		            ]);
		$mockResult2->shouldReceive('is_success')
		            ->andReturn(true);

		$results = [$mockResult1, $mockResult2];

		$formatter = new JsonResultFormatter($results);

		$formatted = $formatter->format('all');

		$expected = [
			'is_success' => true,
			'messages'   => [
				'info' => [
					['step' => 'Step 1', 'type' => 'info', 'message' => 'Info message 1'],
				],
				'error' => [
					['step' => 'Step 1', 'type' => 'error', 'message' => 'Error message 1'],
				],
				'debug' => [
					['step' => 'Step 2', 'type' => 'debug', 'message' => 'Debug message 1'],
				],
			],
		];

		$this->assertEquals($expected, $formatted);
	}

	public function test_format_specific_message_type() {
		$mockResult1 = Mockery::mock(StepProcessorResult::class);
		$mockResult1->shouldReceive('get_step_name')
		            ->andReturn('Step 1');
		$mockResult1->shouldReceive('get_messages')
		            ->with('info')
		            ->andReturn([
			            ['type' => 'info', 'message' => 'Info message 1'],
		            ]);
		$mockResult1->shouldReceive('is_success')
		            ->andReturn(true);

		$mockResult2 = Mockery::mock(StepProcessorResult::class);
		$mockResult2->shouldReceive('get_step_name')
		            ->andReturn('Step 2');
		$mockResult2->shouldReceive('get_messages')
		            ->with('info')
		            ->andReturn([]);
		$mockResult2->shouldReceive('is_success')
		            ->andReturn(true);

		$results = [$mockResult1, $mockResult2];

		$formatter = new JsonResultFormatter($results);

		$formatted = $formatter->format('info');

		$expected = [
			'is_success' => true,
			'messages'   => [
				'info' => [
					['step' => 'Step 1', 'type' => 'info', 'message' => 'Info message 1'],
				],
			],
		];

		$this->assertEquals($expected, $formatted);
	}

	public function test_is_success_returns_true() {
		$mockResult1 = Mockery::mock(StepProcessorResult::class);
		$mockResult1->shouldReceive('is_success')
		            ->andReturn(true);

		$mockResult2 = Mockery::mock(StepProcessorResult::class);
		$mockResult2->shouldReceive('is_success')
		            ->andReturn(true);

		$results = [$mockResult1, $mockResult2];

		$formatter = new JsonResultFormatter($results);

		$this->assertTrue($formatter->is_success());
	}

	public function test_is_success_returns_false() {
		$mockResult1 = Mockery::mock(StepProcessorResult::class);
		$mockResult1->shouldReceive('is_success')
		            ->andReturn(true);

		$mockResult2 = Mockery::mock(StepProcessorResult::class);
		$mockResult2->shouldReceive('is_success')
		            ->andReturn(false);

		$results = [$mockResult1, $mockResult2];

		$formatter = new JsonResultFormatter($results);

		$this->assertFalse($formatter->is_success());
	}
}
