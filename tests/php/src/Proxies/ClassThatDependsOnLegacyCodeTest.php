<?php
/**
 * ClassThatDependsOnLegacyCodeTest class file
 */

namespace Automattic\WooCommerce\Tests\Proxies;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Tests\Proxies\ExampleClasses\ClassThatDependsOnLegacyCode;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;

/**
 * Tests for a class that depends on legacy code
 */
class ClassThatDependsOnLegacyCodeTest extends \WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var LegacyProxy
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$container = wc_get_container();
		$container->add( ClassThatDependsOnLegacyCode::class )->addArgument( LegacyProxy::class );
		$this->sut = $container->get( ClassThatDependsOnLegacyCode::class );
	}

	/**
	 * Legacy proxy's 'call_function' can be used from both an injected LegacyProxy and from 'WC()->call_function'
	 *
	 * @param string $method_to_use Method in the tested class to use.
	 *
	 * @testWith ["call_legacy_function_using_injected_proxy"]
	 *           ["call_legacy_function_using_woocommerce_class"]
	 */
	public function test_call_function_can_be_invoked_via_injected_legacy_proxy_and_woocommerce_object( $method_to_use ) {
		$this->assertEquals( 255, $this->sut->$method_to_use( 'hexdec', 'FF' ) );
	}

	/**
	 * Function mocks can be used from both an injected LegacyProxy and from 'WC()->call_function'
	 *
	 * @param string $method_to_use Method in the tested class to use.
	 *
	 * @testWith ["call_legacy_function_using_injected_proxy"]
	 *           ["call_legacy_function_using_woocommerce_class"]
	 */
	public function test_function_mocks_can_be_used_via_injected_legacy_proxy_and_woocommerce_object( $method_to_use ) {
		$this->register_legacy_proxy_function_mocks(
			array(
				'hexdec' => function( $hex_string ) {
					return "Mocked hexdec for $hex_string";
				},
			)
		);
		$this->assertEquals( 'Mocked hexdec for FF', $this->sut->$method_to_use( 'hexdec', 'FF' ) );
	}

	/**
	 * Legacy proxy's 'call_static' can be used from both an injected LegacyProxy and from 'WC()->call_function'
	 *
	 * @param string $method_to_use Method in the tested class to use.
	 *
	 * @testWith ["call_static_method_using_injected_proxy"]
	 *           ["call_static_method_using_woocommerce_class"]
	 */
	public function test_call_static_can_be_invoked_via_injected_legacy_proxy_and_woocommerce_object( $method_to_use ) {
		$result = $this->sut->$method_to_use( DependencyClass::class, 'concat', 'foo', 'bar', 'fizz' );
		$this->assertEquals( 'Parts: foo, bar, fizz', $result );
	}

	/**
	 * Static method mocks can be used from both an injected LegacyProxy and from 'WC()->call_function'
	 *
	 * @param string $method_to_use Method in the tested class to use.
	 *
	 * @testWith ["call_static_method_using_injected_proxy"]
	 *           ["call_static_method_using_woocommerce_class"]
	 */
	public function test_static_mocks_can_be_used_via_injected_legacy_proxy_and_woocommerce_object( $method_to_use ) {
		$this->register_legacy_proxy_static_mocks(
			array(
				DependencyClass::class => array(
					'concat' => function( ...$parts ) {
						return "I'm returning concat of these parts: " . join( ' ', $parts );
					},
				),
			)
		);

		$expected = "I'm returning concat of these parts: foo bar fizz";
		$result   = $this->sut->$method_to_use( DependencyClass::class, 'concat', 'foo', 'bar', 'fizz' );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Legacy proxy's 'get_instance_of' can be used from both an injected LegacyProxy and from 'WC()->call_function'
	 *
	 * @param string $method_to_use Method in the tested class to use.
	 *
	 * @testWith ["get_instance_of_using_injected_proxy"]
	 *           ["get_instance_of_using_woocommerce_class"]
	 */
	public function test_get_instance_of_can_be_used_via_injected_legacy_proxy_and_woocommerce_object( $method_to_use ) {
		$instance = $this->sut->$method_to_use( \WC_Queue_Interface::class, 34 );
		$this->assertInstanceOf( \WC_Action_Queue::class, $instance );
	}

	/**
	 * Legacy object mocks can be used from both an injected LegacyProxy and from 'WC()->call_function'
	 *
	 * @param string $method_to_use Method in the tested class to use.
	 *
	 * @testWith ["get_instance_of_using_injected_proxy"]
	 *           ["get_instance_of_using_woocommerce_class"]
	 */
	public function test_class_mocks_can_be_used_via_injected_legacy_proxy_and_woocommerce_object( $method_to_use ) {
		$mock = new \stdClass();
		$this->register_legacy_proxy_class_mocks( array( \WC_Query::class => $mock ) );
		$this->assertSame( $mock, $this->sut->$method_to_use( \WC_Query::class ) );
	}
}
