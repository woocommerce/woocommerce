<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\ResultFormatters;

use Automattic\WooCommerce\Blueprint\ResultFormatters\JsonResultFormatter;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Unit tests for JsonResultFormatter class.
 */
class JsonResultFormatterTest extends TestCase {
	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test the format method.
	 *
	 * @return void
	 */
	public function test_format_all_message_types() {
		$mock_result1 = Mockery::mock( StepProcessorResult::class );
		$mock_result1->shouldReceive( 'get_step_name' )
					->andReturn( 'Step 1' );
		$mock_result1->shouldReceive( 'get_messages' )
					->with( 'all' )
					->andReturn(
						array(
							array(
								'type'    => 'info',
								'message' => 'Info message 1',
							),
							array(
								'type'    => 'error',
								'message' => 'Error message 1',
							),
						)
					);
		$mock_result1->shouldReceive( 'is_success' )
					->andReturn( true );

		$mock_result2 = Mockery::mock( StepProcessorResult::class );
		$mock_result2->shouldReceive( 'get_step_name' )
					->andReturn( 'Step 2' );
		$mock_result2->shouldReceive( 'get_messages' )
					->with( 'all' )
					->andReturn(
						array(
							array(
								'type'    => 'debug',
								'message' => 'Debug message 1',
							),
						)
					);
		$mock_result2->shouldReceive( 'is_success' )
					->andReturn( true );

		$results = array( $mock_result1, $mock_result2 );

		$formatter = new JsonResultFormatter( $results );

		$formatted = $formatter->format( 'all' );

		$expected = array(
			'is_success' => true,
			'messages'   => array(
				'info'  => array(
					array(
						'step'    => 'Step 1',
						'type'    => 'info',
						'message' => 'Info message 1',
					),
				),
				'error' => array(
					array(
						'step'    => 'Step 1',
						'type'    => 'error',
						'message' => 'Error message 1',
					),
				),
				'debug' => array(
					array(
						'step'    => 'Step 2',
						'type'    => 'debug',
						'message' => 'Debug message 1',
					),
				),
			),
		);

		$this->assertEquals( $expected, $formatted );
	}

	/**
	 * Test formatting with a specific message type.
	 *
	 * @return void
	 */
	public function test_format_specific_message_type() {
		$mock_result1 = Mockery::mock( StepProcessorResult::class );
		$mock_result1->shouldReceive( 'get_step_name' )
					->andReturn( 'Step 1' );
		$mock_result1->shouldReceive( 'get_messages' )
					->with( 'info' )
					->andReturn(
						array(
							array(
								'type'    => 'info',
								'message' => 'Info message 1',
							),
						)
					);
		$mock_result1->shouldReceive( 'is_success' )
					->andReturn( true );

		$mock_result2 = Mockery::mock( StepProcessorResult::class );
		$mock_result2->shouldReceive( 'get_step_name' )
					->andReturn( 'Step 2' );
		$mock_result2->shouldReceive( 'get_messages' )
					->with( 'info' )
					->andReturn( array() );
		$mock_result2->shouldReceive( 'is_success' )
					->andReturn( true );

		$results = array( $mock_result1, $mock_result2 );

		$formatter = new JsonResultFormatter( $results );

		$formatted = $formatter->format( 'info' );

		$expected = array(
			'is_success' => true,
			'messages'   => array(
				'info' => array(
					array(
						'step'    => 'Step 1',
						'type'    => 'info',
						'message' => 'Info message 1',
					),
				),
			),
		);

		$this->assertEquals( $expected, $formatted );
	}

	/**
	 * Test that is_success returns true when all results are successful.
	 *
	 * @return void
	 */
	public function test_is_success_returns_true() {
		$mock_result1 = Mockery::mock( StepProcessorResult::class );
		$mock_result1->shouldReceive( 'is_success' )
					->andReturn( true );

		$mock_result2 = Mockery::mock( StepProcessorResult::class );
		$mock_result2->shouldReceive( 'is_success' )
					->andReturn( true );

		$results = array( $mock_result1, $mock_result2 );

		$formatter = new JsonResultFormatter( $results );

		$this->assertTrue( $formatter->is_success() );
	}

	/**
	 * Test that is_success returns false when any result is not successful.
	 *
	 * @return void
	 */
	public function test_is_success_returns_false() {
		$mock_result1 = Mockery::mock( StepProcessorResult::class );
		$mock_result1->shouldReceive( 'is_success' )
					->andReturn( true );

		$mock_result2 = Mockery::mock( StepProcessorResult::class );
		$mock_result2->shouldReceive( 'is_success' )
					->andReturn( false );

		$results = array( $mock_result1, $mock_result2 );

		$formatter = new JsonResultFormatter( $results );

		$this->assertFalse( $formatter->is_success() );
	}
}
