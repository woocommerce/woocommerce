<?php
/**
 * DynamicDecorator class file
 */

namespace Automattic\WooCommerce\Tests\Proxies;

use Automattic\WooCommerce\Testing\Tools\DynamicDecorator;
use Automattic\WooCommerce\Tests\Proxies\ExampleClasses\ClassWithReplaceableMembers;

/**
 * Tests for DynamicDecorator
 */
class DynamicDecoratorTest extends \WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var DynamicDecorator
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->sut = new DynamicDecorator( new ClassWithReplaceableMembers() );
	}

	/**
	 * @testdox A decorator with no replacements bypass all method invocations and property access to the decorated object.
	 *
	 * @return void
	 */
	public function test_decorator_with_no_actual_replacements() {
		$this->assertEquals( 'Initial value of $some_property', $this->sut->some_property );

		$this->sut->some_property = 'foobar';
		$this->assertEquals( 'foobar', $this->sut->some_property );

		$expected = '$some_method invoked with $a=1234, $b=foobar';
		$actual   = $this->sut->some_method( 1234, 'foobar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox A replacement callback for a method receives the decorator instance and then the parameters of the original method.
	 *
	 * @return void
	 */
	public function test_method_replacement() {
		$this->sut->register_method_replacement(
			'some_method',
			function( $decorator, $a, $b ) {
				return "Replacement method invoked with \$a=$a, \$b=$b, " . $decorator->decorated_object->some_other_property;
			}
		);

		$expected = 'Replacement method invoked with $a=1234, $b=foobar, hello';
		$actual   = $this->sut->some_method( 1234, 'foobar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox A property read can be replaced with a fixed value.
	 *
	 * @return void
	 */
	public function test_property_get_replacement_with_value() {
		$this->sut->register_property_get_replacement( 'some_property', 'replacement value' );

		$this->assertEquals( 'replacement value', $this->sut->some_property );
	}

	/**
	 * @testdox A property read can be replaced with a callback that receives the decorator instance as the only parameter.
	 *
	 * @return void
	 */
	public function test_property_get_replacement_with_callback() {
		$this->sut->register_property_get_replacement(
			'some_property',
			function( $decorator ) {
				return 'replacement value, ' . $decorator->decorated_object->some_other_property;
			}
		);

		$this->assertEquals( 'replacement value, hello', $this->sut->some_property );
	}

	/**
	 * @testdox A property write can be replaced with a callback that receives the decorator instance and the value being set as parameters.
	 *
	 * @return void
	 */
	public function test_property_set_replacement() {
		$this->sut->register_property_set_replacement(
			'some_property',
			function( $decorator, $value ) {
				$decorator->decorated_object->some_property = $decorator->decorated_object->some_other_property . ', ' . $value;
			}
		);

		$this->sut->some_property = 'foobar';

		$this->assertEquals( 'hello, foobar', $this->sut->some_property );
	}

	/**
	 * @testdox 'call_original_method' can be used from within a method replacement to call a method on the decorated object.
	 */
	public function test_call_original_method() {
		$replacement_invoked = false;

		$this->sut->register_method_replacement(
			'some_method',
			function( ...$args ) use ( &$replacement_invoked ) {
				$replacement_invoked = true;
				$decorator           = $args[0];

				return $decorator->call_original_method( 'some_method', $args );
			}
		);

		$expected = '$some_method invoked with $a=1234, $b=foobar';
		$actual   = $this->sut->some_method( 1234, 'foobar' );
		$this->assertEquals( $expected, $actual );
		$this->assertTrue( $replacement_invoked );
	}
}
