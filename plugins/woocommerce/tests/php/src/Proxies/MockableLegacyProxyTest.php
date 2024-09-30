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
	public function setUp(): void {
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
		$mock_factory = function ( $code, $message, $http_status_code = 400, $data = array() ) {
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
			array( 1234, function () {} ),
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
				'substr' => function ( $the_string, $start, $length ) {
					return "I'm returning substr of '$the_string' from $start with length $length";
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
			array( 1234, array( 'some_method' => function () {} ) ),
			array( 'SomeClassName', 1234 ),
			array( 'SomeClassName', array( 1234 => function () {} ) ),
			array( 'SomeClassName', array( 'the_method' => 1234 ) ),
		);
	}

	/**
	 * @testdox 'register_static_mocks' throws an exception if invalid parameters are supplied.
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
					'concat' => function ( ...$parts ) {
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
	 * @testdox 'get_global' works as in LegacyProxy if no global replacements are registered.
	 */
	public function test_get_global_works_as_regular_legacy_proxy_if_no_mocks_registered() {
		global $wpdb;

		$result = $this->sut->get_global( 'wpdb' );
		$this->assertEquals( $wpdb, $result );
	}

	/**
	 * @testdox 'get_global' can be used to register replacements for globals.
	 */
	public function test_register_global_mocks_can_be_used_to_replace_globals() {
		$replacement_wpdb = new \stdClass();

		$this->sut->register_global_mocks( array( 'wpdb' => $replacement_wpdb ) );

		$result = $this->sut->get_global( 'wpdb' );
		$this->assertEquals( $replacement_wpdb, $result );
	}

	/**
	 * @testdox 'register_global_mocks' throws if invalid parameters are supplied.
	 */
	public function test_register_global_mocks_throws_if_invalid_parameters_are_supplied() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'MockableLegacyProxy::register_global_mocks: $mocks must be an associative array of global_name => value.' );

		$this->sut->register_global_mocks( array( 1234 => new \stdClass() ) );
	}

	/**
	 * @testdox 'reset' can be used to revert the instance to its original state, in which nothing is mocked.
	 */
	public function test_reset_can_be_used_to_unregister_all_mocks() {
		$this->sut->register_class_mocks( array( \WC_Query::class => new \stdClass() ) );

		$this->sut->register_function_mocks(
			array(
                // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				'substr' => function ( $the_string, $start, $length ) {
					return null;
				},
			)
		);

		$this->sut->register_static_mocks(
			array(
				DependencyClass::class => array(
                    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
					'concat' => function ( ...$parts ) {
						return null;
					},
				),
			)
		);

		$this->sut->register_global_mocks( array( 'wpdb' => new \stdClass() ) );

		$this->sut->register_constant_mock( 'DB_USER', null );
		$this->sut->register_constant_mock( 'I_WISH_TO_NOT_EXIST', 'NOPE' );

		$this->sut->reset();

		$this->test_call_function_works_as_regular_legacy_proxy_if_no_mocks_registered();
		$this->test_call_static_works_as_regular_legacy_proxy_if_no_mocks_registered();
		$this->test_call_static_works_as_regular_legacy_proxy_if_no_mocks_registered();
		$this->test_get_global_works_as_regular_legacy_proxy_if_no_mocks_registered();
		$this->test_constant_is_defined_works_as_regular_legacy_proxy_if_no_mocks_registered( 'DB_USER', true );
		$this->test_constant_is_defined_works_as_regular_legacy_proxy_if_no_mocks_registered( 'I_WISH_TO_NOT_EXIST', false );
		$this->test_get_constant_value_returns_actual_value_for_existing_not_mocked_constant();
		$this->test_get_constant_value_returns_default_value_for_non_existing_and_not_mocked_constant();
	}

	/**
	 * @testdox 'constant_is_defined' works as in LegacyProxy if no constant mocks are registered.
	 *
	 * @testWith ["DB_USER", true]
	 *           ["I_WISH_TO_NOT_EXIST", false]
	 *
	 * @param string $constant_name Name of the constant to test.
	 * @param  bool   $expected_defined The constant is expected to be considered defined or not.
	 */
	public function test_constant_is_defined_works_as_regular_legacy_proxy_if_no_mocks_registered( string $constant_name, bool $expected_defined ) {
		$this->assertEquals( $expected_defined, $this->sut->constant_is_defined( $constant_name ) );
	}

	/**
	 * @testdox 'constant_is_defined' returns true for a constant that actually exists and hasn't been mocked.
	 */
	public function test_constant_is_defined_returns_true_for_actually_existing_not_mocked_constant() {
		$this->assertTrue( $this->sut->constant_is_defined( 'DB_USER' ) );
	}

	/**
	 * @testdox 'constant_is_defined' returns false for a constant that doesn't exist and hasn't been mocked.
	 */
	public function test_constant_is_defined_returns_false_for_non_existing_and_not_mocked_constant() {
		$this->assertFalse( $this->sut->constant_is_defined( 'I_DONT_EXIST' ) );
	}

	/**
	 * @testdox 'constant_is_defined' returns true for a constant that actually exists and has been mocked with a non-null value.
	 */
	public function test_constant_is_defined_returns_true_for_existing_and_mocked_constant() {
		$this->sut->register_constant_mock( 'DB_USER', 'myself' );
		$this->assertTrue( $this->sut->constant_is_defined( 'DB_USER' ) );
	}

	/**
	 * @testdox 'constant_is_defined' returns false for a constant that doesn't exist and has been mocked with a value of null.
	 */
	public function test_constant_is_defined_returns_false_for_existing_and_mocked_with_null_value_constant() {
		$this->sut->register_constant_mock( 'DB_USER', null );
		$this->assertFalse( $this->sut->constant_is_defined( 'DB_USER' ) );
	}

	/**
	 * @testdox 'constant_is_defined' returns true for a constant that doesn't exist but has been mocked with a non-null value.
	 */
	public function test_constant_is_defined_returns_true_for_not_existing_but_mocked_constant() {
		$this->sut->register_constant_mock( 'I_AM_MOCK', 'yes' );
		$this->assertTrue( $this->sut->constant_is_defined( 'I_AM_MOCK' ) );
	}

	/**
	 * @testdox 'constant_is_defined' returns false for a constant that doesn't exist but has been mocked with a value of null.
	 */
	public function test_constant_is_defined_returns_false_for_not_existing_and_mocked_constant_with_value_null() {
		$this->sut->register_constant_mock( 'I_AM_MOCK', null );
		$this->assertFalse( $this->sut->constant_is_defined( 'I_AM_MOCK' ) );
	}

	/**
	 * @testdox 'get_constant_value' returns the actual value of a constant that exists and hasn't been mocked.
	 */
	public function test_get_constant_value_returns_actual_value_for_existing_not_mocked_constant() {
		$this->assertEquals( DB_USER, $this->sut->get_constant_value( 'DB_USER' ) );
	}

	/**
	 * @testdox 'get_constant_value' returns the supplied default value of a constant that doesn't exist and hasn't been mocked.
	 */
	public function test_get_constant_value_returns_default_value_for_non_existing_and_not_mocked_constant() {
		$this->assertEquals( 'indeed', $this->sut->get_constant_value( 'I_DONT_EXIST', 'indeed' ) );
	}

	/**
	 * @testdox 'get_constant_value' returns the mocked value of a constant that exists and has been mocked.
	 */
	public function test_get_constant_value_returns_mocked_value_for_existing_mocked_constant() {
		$this->sut->register_constant_mock( 'DB_USER', 'myself' );
		$this->assertEquals( 'myself', $this->sut->get_constant_value( 'DB_USER' ) );
	}

	/**
	 * @testdox 'get_constant_value' returns the supplied default value of a constant that exists but has been mocked with a value of null.
	 */
	public function test_get_constant_value_returns_default_value_for_existing_constant_mocked_as_null() {
		$this->sut->register_constant_mock( 'DB_USER', null );
		$this->assertEquals( 'no_one', $this->sut->get_constant_value( 'DB_USER', 'no_one' ) );
	}

	/**
	 * @testdox 'get_constant_value' returns the mocked value of a constant that doesn't exist but has been mocked.
	 */
	public function test_get_constant_value_returns_mocked_value_for_non_existing_mocked_constant() {
		$this->sut->register_constant_mock( 'I_AM_MOCK', 'indeed' );
		$this->assertEquals( 'indeed', $this->sut->get_constant_value( 'I_AM_MOCK' ) );
	}

	/**
	 * @testdox 'get_constant_value' returns the supplied default value of a constant that doesn't exist and has been mocked with a value of null.
	 */
	public function test_get_constant_value_returns_default_value_for_non_existing_constant_mocked_as_null() {
		$this->sut->register_constant_mock( 'I_AM_MOCK', null );
		$this->assertEquals( 'nope', $this->sut->get_constant_value( 'I_AM_MOCK', 'nope' ) );
	}
}
