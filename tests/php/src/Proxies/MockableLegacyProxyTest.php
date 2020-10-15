<?php
/**
 * MockableLegacyProxyTests class file
 */

namespace Automattic\WooCommerce\Tests\Proxies;

use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;

/**
 * Tests for MockableLegacyProxy
 */
class MockableLegacyProxyTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var MockableLegacyProxy
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->sut = new MockableLegacyProxy();
	}

	/**
	 * @testdox 'get_instance_of' works as in LegacyProxy if no class mocks are registered.
	 */
	public function test_get_instance_of_works_as_regular_legacy_proxy_if_no_mock_registered() {
		$instance = $this->sut->get_instance_of( \WC_Data_Exception::class, 1234, 'Error!', 432 );
		$this->assertInstanceOf( \WC_Data_Exception::class, $instance );
		$this->assertEquals( 1234, $instance->getErrorCode() );
		$this->assertEquals( 'Error!', $instance->getMessage() );
		$this->assertEquals( 432, $instance->getCode() );
	}

	/**
	 * The data provider for test_register_class_mocks_throws_if_invalid_parameters_supplied.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_register_class_mocks_throws_if_invalid_parameters_supplied() {
		return array(
			array( 1234, new \stdClass() ),
			array( 'SomeClassName', 1234 ),
		);
	}

	/**
	 * @testdox 'register_class_mocks' throws an exception if an invalid parameter is supplied (not an array of class name => object or factory callback).
	 *
	 * @dataProvider data_provider_for_test_register_class_mocks_throws_if_invalid_parameters_supplied
	 *
	 * @param string $class_name The name of the class to mock.
	 * @param object $mock The mock.
	 */
	public function test_register_class_mocks_throws_if_invalid_parameters_supplied( $class_name, $mock ) {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'MockableLegacyProxy::register_class_mocks: $mocks must be an associative array of class_name => object or factory callback.' );

		$this->sut->register_class_mocks( array( $class_name => $mock ) );
	}

	/**
	 * @testdox 'register_class_mocks' can be used to return class mocks by passing fixed mock instances.
	 */
	public function test_register_class_mocks_can_be_used_so_that_get_instance_of_returns_a_fixed_instance_mock() {
		$mock = new \stdClass();
		$this->sut->register_class_mocks( array( \WC_Query::class => $mock ) );
		$this->assertSame( $mock, $this->sut->get_instance_of( \WC_Query::class ) );
	}

	/**
	 * @testdox 'register_class_mocks' can be used to return class mocks by passing mock factory callbacks.
	 */
	public function test_register_class_mocks_can_be_used_so_that_get_instance_of_uses_a_factory_function_to_return_the_instance() {
		$mock_factory = function( $code, $message, $http_status_code = 400, $data = array() ) {
			return "$code, $message, $http_status_code";
		};
		$this->sut->register_class_mocks( array( \WC_Data_Exception::class => $mock_factory ) );
		$this->assertEquals( '1234, Error!, 432', $this->sut->get_instance_of( \WC_Data_Exception::class, 1234, 'Error!', 432 ) );
	}

	/**
	 * @testdox 'call_function' works as in LegacyProxy if no function mocks are registered.
	 */
	public function test_call_function_works_as_regular_legacy_proxy_if_no_mocks_registered() {
		$result = $this->sut->call_function( 'substr', 'foo bar fizz', 4, 3 );
		$this->assertEquals( 'bar', $result );
	}

	/**
	 * The data provider for test_register_function_mocks_throws_if_invalid_parameters_supplied.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_register_function_mocks_throws_if_invalid_parameters_supplied() {
		return array(
			array( 1234, function() {} ),
			array( 'SomeClassName', 1234 ),
		);
	}

	/**
	 * @testdox 'register_function_mocks' throws an exception if an invalid parameter is supplied (not an array of function name => mock function).
	 *
	 * @dataProvider data_provider_for_test_register_function_mocks_throws_if_invalid_parameters_supplied
	 *
	 * @param string   $function_name The name of the function to mock.
	 * @param callable $mock The mock.
	 */
	public function test_register_function_mocks_throws_if_invalid_parameters_supplied( $function_name, $mock ) {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'MockableLegacyProxy::register_function_mocks: The supplied mocks array must have function names as keys and function replacement callbacks as values.' );

		$this->sut->register_function_mocks( array( $function_name => $mock ) );
	}

	/**
	 * @testdox 'register_function_mocks' can be used to register mocks for any function.
	 */
	public function test_register_function_mocks_can_be_used_so_that_call_function_calls_mock_functions() {
		$this->sut->register_function_mocks(
			array(
				'substr' => function( $string, $start, $length ) {
					return "I'm returning substr of '$string' from $start with length $length";
				},
			)
		);

		$expected = "I'm returning substr of 'foo bar fizz' from 4 with length 3";
		$result   = $this->sut->call_function( 'substr', 'foo bar fizz', 4, 3 );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'call_static' works as in LegacyProxy if no static method mocks are registered.
	 */
	public function test_call_static_works_as_regular_legacy_proxy_if_no_mocks_registered() {
		$result = $this->sut->call_static( DependencyClass::class, 'concat', 'foo', 'bar', 'fizz' );
		$this->assertEquals( 'Parts: foo, bar, fizz', $result );
	}

	/**
	 * The data provider for test_register_static_mocks_throws_if_invalid_parameters_supplied.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_register_static_mocks_throws_if_invalid_parameters_supplied() {
		return array(
			array( 1234, array( 'some_method' => function(){} ) ),
			array( 'SomeClassName', 1234 ),
			array( 'SomeClassName', array( 1234 => function(){} ) ),
			array( 'SomeClassName', array( 'the_method' => 1234 ) ),
		);
	}

	/**
	 * @testdox
	 *
	 * @dataProvider data_provider_for_test_register_function_mocks_throws_if_invalid_parameters_supplied
	 *
	 * @param string $class_name The name of the class whose static methods we want to mock.
	 * @param array  $mocks The mocks.
	 */
	public function test_register_static_mocks_throws_if_invalid_parameters_supplied( $class_name, $mocks ) {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'MockableLegacyProxy::register_static_mocks: $mocks must be an associative array of class name => associative array of method name => callable.' );

		$this->sut->register_static_mocks( array( $class_name => $mocks ) );
	}

	/**
	 * @testdox 'register_static_mocks' can be used to register mocks for any static method.
	 */
	public function test_register_static_mocks_can_be_used_so_that_call_function_calls_mock_functions() {
		$this->sut->register_static_mocks(
			array(
				DependencyClass::class => array(
					'concat' => function( ...$parts ) {
						return "I'm returning concat of these parts: " . join( ' ', $parts );
					},
				),
			)
		);

		$expected = "I'm returning concat of these parts: foo bar fizz";
		$result   = $this->sut->call_static( DependencyClass::class, 'concat', 'foo', 'bar', 'fizz' );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox 'reset' can be used to revert the instance to its original state, in which nothing is mocked.
	 */
	public function test_reset_can_be_used_to_unregister_all_mocks() {
		$this->sut->register_class_mocks( array( \WC_Query::class => new \stdClass() ) );

		$this->sut->register_function_mocks(
			array(
				'substr' => function( $string, $start, $length ) {
					return null;
				},
			)
		);

		$this->sut->register_static_mocks(
			array(
				DependencyClass::class => array(
					'concat' => function( ...$parts ) {
						return null;
					},
				),
			)
		);

		$this->sut->reset();

		$this->test_call_function_works_as_regular_legacy_proxy_if_no_mocks_registered();
		$this->test_call_static_works_as_regular_legacy_proxy_if_no_mocks_registered();
		$this->test_call_static_works_as_regular_legacy_proxy_if_no_mocks_registered();
	}
}
