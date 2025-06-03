<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Schemas;

use Mockery;
use Automattic\WooCommerce\Blueprint\Schemas\JsonSchema;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;

/**
 * Class JsonSchemaTest
 */
class JsonSchemaTest extends TestCase {
	/**
	 * Test getting steps from a schema.
	 *
	 * @return void
	 */
	public function test_get_steps() {
		$schema = new JsonSchema( $this->get_fixture_path( 'empty-steps.json' ) );
		$steps  = $schema->get_steps();
		$this->assertIsArray( $steps );
		$this->assertCount( 0, $steps );
	}

	/**
	 * Test getting a step from a schema.
	 *
	 * @return void
	 */
	public function test_get_step() {
		$name   = 'installPlugin';
		$schema = new JsonSchema( $this->get_fixture_path( 'with-install-plugin-step.json' ) );
		$steps  = $schema->get_step( $name );
		$this->assertIsArray( $steps );
		foreach ( $steps as $step ) {
			$this->assertEquals( $name, $step->step );
		}
	}

	/**
	 * Test getting a step from a schema that does not exist.
	 *
	 * @return void
	 */
	public function test_it_throws_invalid_argument_exception_with_invalid_json() {
		$this->expectException( \InvalidArgumentException::class );
		new JsonSchema( $this->get_fixture_path( 'invalid-json.json' ) );
	}

	/**
	 * Test that it throws an invalid argument exception with an invalid path.
	 *
	 * @return void
	 */
	public function test_it_throws_invalid_argument_exception_with_invalid_path() {
		$this->expectException( \InvalidArgumentException::class );
		new JsonSchema( $this->get_fixture_path( 'invalid-path.json' ) );
	}

	/**
	 * Test that it throws a runtime exception when the file is not readable.
	 *
	 * @return void
	 */
	public function test_it_throws_runtime_exception_when_file_is_not_readable() {
		$this->expectException( \RuntimeException::class );

		$mock = Mockery::mock( JsonSchema::class )->makePartial();
		$mock->shouldReceive( 'wp_filesystem_get_contents' )
			->once()
			->andReturn( false );

		$mock->__construct( $this->get_fixture_path( 'invalid-json.json' ) );
	}
}
