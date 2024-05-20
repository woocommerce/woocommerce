<?php
/**
 * LegacyProxyTests class file
 */

namespace Automattic\WooCommerce\Tests\Proxies;

use Automattic\WooCommerce\Internal\DependencyManagement\Definition;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;
use Foo\Bar\ClassWithNonWooNamespace;

/**
 * Tests for LegacyProxy
 */
class LegacyProxyTest extends \WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var LegacyProxy
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->sut = new LegacyProxy();
	}

	/**
	 * @testdox 'get_instance_of' throws an exception when trying to use it to get an instance of a namespaced class if the namespace starts with 'Automattic\WooCommerce'.
	 */
	public function test_get_instance_of_throws_when_trying_to_get_a_woo_namespaced_class() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'The LegacyProxy class is not intended for getting instances of classes whose namespace starts with \'Automattic\\WooCommerce\', please use ' . Definition::INJECTION_METHOD . ' method injection or the instance of Automattic\WooCommerce\Vendor\Psr\Container\ContainerInterface for that.' );

		$this->sut->get_instance_of( DependencyClass::class );
	}

	/**
	 * @testdox 'get_instance_of' can be used to get an instance of a namespaced class if the namespace doesn't start with 'Automattic\WooCommerce'.
	 */
	public function test_get_instance_of_can_be_used_to_get_a_non_woo_namespaced_class() {
		$object = $this->sut->get_instance_of( ClassWithNonWooNamespace::class );
		$this->assertInstanceOf( ClassWithNonWooNamespace::class, $object );
	}

	/**
	 * @testdox 'get_instance_of' can be used to get an instance of a class by using its constructor and passing constructor arguments.
	 */
	public function test_get_instance_of_can_be_used_to_get_a_non_namespaced_class_with_constructor_parameters() {
		$instance = $this->sut->get_instance_of( \WC_Data_Exception::class, 1234, 'Error!', 432 );
		$this->assertInstanceOf( \WC_Data_Exception::class, $instance );
		$this->assertEquals( 1234, $instance->getErrorCode() );
		$this->assertEquals( 'Error!', $instance->getMessage() );
		$this->assertEquals( 432, $instance->getCode() );
	}

	/**
	 * @testdox 'get_instance_of' uses the 'instance' static method in classes that implement it, passing the supplied arguments.
	 */
	public function test_get_instance_of_class_with_instance_method_gets_an_instance_of_the_appropriate_class() {
		// ClassWithSingleton is in the root namespace and thus can't be autoloaded.
		require_once dirname( __DIR__ ) . '/Internal/DependencyManagement/ExampleClasses/ClassWithSingleton.php';

		$instance = $this->sut->get_instance_of( \ClassWithSingleton::class, 'foo', 'bar' );
		$this->assertSame( \ClassWithSingleton::$instance, $instance );
		$this->assertEquals( array( 'foo', 'bar' ), \ClassWithSingleton::$instance_args );
	}

	/**
	 * @testdox 'get_instance_of' uses the 'load' static method in classes that implement it, passing the supplied arguments.
	 */
	public function test_get_instance_of_class_with_load_method_gets_an_instance_of_the_appropriate_class() {
		// ClassWithLoadMethod is in the root namespace and thus can't be autoloaded.
		require_once dirname( __DIR__ ) . '/Internal/DependencyManagement/ExampleClasses/ClassWithLoadMethod.php';

		$loaded = $this->sut->get_instance_of( \ClassWithLoadMethod::class, 'foo', 'bar' );
		$this->assertSame( \ClassWithLoadMethod::$loaded, $loaded );
		$this->assertEquals( array( 'foo', 'bar' ), \ClassWithLoadMethod::$loaded_args );
	}

	/**
	 * @testdox 'get_instance_of' can be used to get an instance of a class implementing WC_Queue_Interface.
	 */
	public function test_get_instance_of_wc_queue_interface_gets_an_instance_of_the_appropriate_class() {
		$instance = $this->sut->get_instance_of( \WC_Queue_Interface::class, 34 );
		$this->assertInstanceOf( \WC_Action_Queue::class, $instance );
	}

	/**
	 * @testdox 'call_function' can be used to invoke any standalone function.
	 */
	public function test_call_function_can_be_used_to_invoke_functions() {
		$result = $this->sut->call_function( 'substr', 'foo bar fizz', 4, 3 );
		$this->assertEquals( 'bar', $result );
	}

	/**
	 * @testdox 'call_static' can be used to invoke any public static class method.
	 */
	public function test_call_static_can_be_used_to_invoke_public_static_methods() {
		$result = $this->sut->call_static( DependencyClass::class, 'concat', 'foo', 'bar', 'fizz' );
		$this->assertEquals( 'Parts: foo, bar, fizz', $result );
	}

	/**
	 * @testdox 'get_global' can be used to get the value of a global.
	 */
	public function get_global_can_be_used_to_get_the_value_of_a_global() {
		global $wpdb;

		$result = $this->sut->get_global( 'wpdb' );
		$this->assertEquals( $result, $wpdb );
	}
}
