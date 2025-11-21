<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses\AnotherDummyCallbackClass;
use Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses\DummyCallbackClass;
use Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses\DummyCallbackClassWithDynamicProps;
use Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses\DummyInvokableClass;
use Automattic\WooCommerce\Utilities\CallbackUtil;

/**
 * Tests for CallbackUtil class.
 */
class CallbackUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `get_callback_signature` should return string function names unchanged.
	 */
	public function test_get_callback_signature_with_function_name() {
		$signature = CallbackUtil::get_callback_signature( 'my_function_name' );

		$this->assertEquals( 'my_function_name', $signature );
	}

	/**
	 * @testdox `get_callback_signature` should return class name and method for instance methods.
	 */
	public function test_get_callback_signature_with_instance_method() {
		$object   = new DummyCallbackClass();
		$callback = array( $object, 'my_method' );

		$signature = CallbackUtil::get_callback_signature( $callback );

		$this->assertEquals(
			DummyCallbackClass::class . '::my_method',
			$signature
		);
	}

	/**
	 * @testdox `get_callback_signature` should produce identical signatures for different instances of the same class.
	 */
	public function test_get_callback_signature_consistent_across_instances() {
		$object1    = new DummyCallbackClassWithDynamicProps();
		$callback1  = array( $object1, 'my_method' );
		$signature1 = CallbackUtil::get_callback_signature( $callback1 );

		$object2    = new DummyCallbackClassWithDynamicProps();
		$callback2  = array( $object2, 'my_method' );
		$signature2 = CallbackUtil::get_callback_signature( $callback2 );

		$this->assertNotEquals( $object1->random_value, $object2->random_value );

		$this->assertEquals( $signature1, $signature2 );

		$this->assertEquals(
			DummyCallbackClassWithDynamicProps::class . '::my_method',
			$signature1
		);
	}

	/**
	 * @testdox `get_callback_signature` should return class name and method for static method callbacks.
	 */
	public function test_get_callback_signature_with_static_method() {
		$callback = array( DummyCallbackClass::class, 'static_method' );

		$signature = CallbackUtil::get_callback_signature( $callback );

		$this->assertEquals(
			DummyCallbackClass::class . '::static_method',
			$signature
		);
	}

	/**
	 * @testdox `get_callback_signature` should identify closures uniquely with 'Closure@' prefix.
	 */
	public function test_get_callback_signature_different_closures() {
		$closure1 = function () {
			return 'test1';
		};

		$closure2 = function () {
			return 'test2';
		};

		$signature1 = CallbackUtil::get_callback_signature( $closure1 );
		$signature2 = CallbackUtil::get_callback_signature( $closure2 );

		// Different closures should have different signatures.
		$this->assertNotEquals( $signature1, $signature2 );
		$this->assertStringStartsWith( 'Closure@', $signature1 );
		$this->assertStringStartsWith( 'Closure@', $signature2 );
	}

	/**
	 * @testdox `get_callback_signature` should produce identical signatures for the same closure instance.
	 */
	public function test_get_callback_signature_same_closure_instance() {
		$closure = function () {
			return 'test';
		};

		$signature1 = CallbackUtil::get_callback_signature( $closure );
		$signature2 = CallbackUtil::get_callback_signature( $closure );

		// Same closure instance should produce identical signatures.
		$this->assertEquals( $signature1, $signature2 );
	}

	/**
	 * @testdox `get_callback_signature` should produce closure signatures with file path and line numbers.
	 */
	public function test_get_callback_signature_closure_format() {
		$closure = function () {
			return 'test';
		};

		$signature = CallbackUtil::get_callback_signature( $closure );

		$this->assertStringStartsWith( 'Closure@', $signature );
		$this->assertStringContainsString( __FILE__, $signature );
		$this->assertMatchesRegularExpression( '/:\d+-\d+$/', $signature );
	}

	/**
	 * @testdox `get_callback_signature` should produce consistent signatures for the same closure code across invocations.
	 */
	public function test_get_callback_signature_closure_consistency() {
		$get_closure = function () {
			return function () {
				return 'test';
			};
		};

		$closure1   = $get_closure();
		$closure2   = $get_closure();
		$signature1 = CallbackUtil::get_callback_signature( $closure1 );
		$signature2 = CallbackUtil::get_callback_signature( $closure2 );

		$this->assertEquals( $signature1, $signature2 );
	}

	/**
	 * @testdox `get_callback_signature` should return class name with __invoke for regular invokable objects.
	 */
	public function test_get_callback_signature_with_invokable_object() {
		$invokable = new DummyInvokableClass();

		$signature = CallbackUtil::get_callback_signature( $invokable );

		$this->assertEquals(
			DummyInvokableClass::class . '::__invoke',
			$signature
		);
	}

	/**
	 * @testdox `get_callback_signature` should return hashed class name with __invoke and location for anonymous invokable objects.
	 */
	public function test_get_callback_signature_with_anonymous_invokable_object() {
		$invokable = new class() {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __invoke() {
				return 'test';
			}
		};

		$signature = CallbackUtil::get_callback_signature( $invokable );

		$this->assertMatchesRegularExpression( '/^class@anonymous\[[a-f0-9]{32}\]::__invoke@/', $signature );
		$this->assertStringContainsString( 'CallbackUtilTest.php', $signature );
		$this->assertMatchesRegularExpression( '/:\d+-\d+$/', $signature );
	}

	/**
	 * @testdox `get_callback_signature` should fall back to serialization for unknown callback types.
	 */
	public function test_get_callback_signature_with_unknown_type() {
		$callback = 12345;

		$signature = CallbackUtil::get_callback_signature( $callback );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$this->assertEquals( serialize( $callback ), $signature );
	}

	/**
	 * @testdox `get_callback_signature` should produce different signatures for different methods on the same class.
	 */
	public function test_get_callback_signature_different_methods_same_class() {
		$object    = new DummyCallbackClass();
		$callback1 = array( $object, 'my_method' );
		$callback2 = array( $object, 'another_method' );

		$signature1 = CallbackUtil::get_callback_signature( $callback1 );
		$signature2 = CallbackUtil::get_callback_signature( $callback2 );

		$this->assertNotEquals( $signature1, $signature2 );

		$this->assertEquals(
			DummyCallbackClass::class . '::my_method',
			$signature1
		);

		$this->assertEquals(
			DummyCallbackClass::class . '::another_method',
			$signature2
		);
	}

	/**
	 * @testdox `get_callback_signature` should produce different signatures for the same method name on different classes.
	 */
	public function test_get_callback_signature_same_method_different_classes() {
		$object1   = new DummyCallbackClass();
		$object2   = new AnotherDummyCallbackClass();
		$callback1 = array( $object1, 'my_method' );
		$callback2 = array( $object2, 'my_method' );

		$signature1 = CallbackUtil::get_callback_signature( $callback1 );
		$signature2 = CallbackUtil::get_callback_signature( $callback2 );

		$this->assertNotEquals( $signature1, $signature2 );

		$this->assertEquals(
			DummyCallbackClass::class . '::my_method',
			$signature1
		);

		$this->assertEquals(
			AnotherDummyCallbackClass::class . '::my_method',
			$signature2
		);
	}

	/**
	 * @testdox `get_callback_signature` should fall back to serialization for invalid array callbacks with non-string method.
	 */
	public function test_get_callback_signature_with_invalid_array_non_string_method() {
		$object   = new DummyCallbackClass();
		$callback = array( $object, 123 );

		$signature = CallbackUtil::get_callback_signature( $callback );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$this->assertEquals( serialize( $callback ), $signature );
	}

	/**
	 * @testdox `get_callback_signature` should fall back to serialization for invalid array callbacks with non-object/non-string target.
	 */
	public function test_get_callback_signature_with_invalid_array_non_object_target() {
		$callback = array( 123, 'method' );

		$signature = CallbackUtil::get_callback_signature( $callback );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$this->assertEquals( serialize( $callback ), $signature );
	}

	/**
	 * @testdox `get_hook_callback_signatures` should return empty array for non-existent hook.
	 */
	public function test_get_hook_callback_signatures_with_non_existent_hook() {
		$signatures = CallbackUtil::get_hook_callback_signatures( 'non_existent_hook_12345' );

		$this->assertIsArray( $signatures );
		$this->assertEmpty( $signatures );
	}

	/**
	 * @testdox `get_hook_callback_signatures` should return signatures for all callbacks on a hook.
	 */
	public function test_get_hook_callback_signatures_with_multiple_callbacks() {
		$hook_name = 'test_hook_multiple_callbacks';
		$object    = new DummyCallbackClass();

		add_action( $hook_name, 'my_function', 10 );
		add_action( $hook_name, array( $object, 'my_method' ), 10 );
		add_action( $hook_name, array( DummyCallbackClass::class, 'static_method' ), 20 );

		$signatures = CallbackUtil::get_hook_callback_signatures( $hook_name );

		$this->assertArrayHasKey( 10, $signatures );
		$this->assertArrayHasKey( 20, $signatures );

		$this->assertCount( 2, $signatures[10] );
		$this->assertContains( 'my_function', $signatures[10] );
		$this->assertContains( 'Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses\DummyCallbackClass::my_method', $signatures[10] );

		$this->assertCount( 1, $signatures[20] );
		$this->assertContains( DummyCallbackClass::class . '::static_method', $signatures[20] );

		remove_all_actions( $hook_name );
	}

	/**
	 * @testdox `get_hook_callback_signatures` should handle closures correctly.
	 */
	public function test_get_hook_callback_signatures_with_closures() {
		$hook_name = 'test_hook_closures';

		$closure1 = function () {
			return 'test1';
		};

		$closure2 = function () {
			return 'test2';
		};

		add_action( $hook_name, $closure1, 10 );
		add_action( $hook_name, $closure2, 10 );

		$signatures = CallbackUtil::get_hook_callback_signatures( $hook_name );

		$this->assertArrayHasKey( 10, $signatures );
		$this->assertCount( 2, $signatures[10] );

		foreach ( $signatures[10] as $signature ) {
			$this->assertStringStartsWith( 'Closure@', $signature );
		}

		remove_all_actions( $hook_name );
	}

	/**
	 * @testdox `get_hook_callback_signatures` should handle invokable objects correctly.
	 */
	public function test_get_hook_callback_signatures_with_invokable() {
		$hook_name = 'test_hook_invokable';
		$invokable = new DummyInvokableClass();

		add_action( $hook_name, $invokable, 10 );

		$signatures = CallbackUtil::get_hook_callback_signatures( $hook_name );

		$this->assertArrayHasKey( 10, $signatures );

		$this->assertCount( 1, $signatures[10] );

		$this->assertEquals(
			DummyInvokableClass::class . '::__invoke',
			$signatures[10][0]
		);

		remove_all_actions( $hook_name );
	}

	/**
	 * @testdox `get_hook_callback_signatures` should produce consistent signatures across multiple instances.
	 */
	public function test_get_hook_callback_signatures_consistent_across_instances() {
		$hook_name = 'test_hook_consistent';

		$object1 = new DummyCallbackClassWithDynamicProps();
		add_action( $hook_name, array( $object1, 'my_method' ), 10 );
		$signatures1 = CallbackUtil::get_hook_callback_signatures( $hook_name );

		remove_all_actions( $hook_name );

		$object2 = new DummyCallbackClassWithDynamicProps();
		add_action( $hook_name, array( $object2, 'my_method' ), 10 );
		$signatures2 = CallbackUtil::get_hook_callback_signatures( $hook_name );

		$this->assertNotEquals( $object1->random_value, $object2->random_value );

		$this->assertEquals( $signatures1, $signatures2 );

		$this->assertEquals(
			DummyCallbackClassWithDynamicProps::class . '::my_method',
			$signatures1[10][0]
		);

		remove_all_actions( $hook_name );
	}

	/**
	 * @testdox `get_hook_callback_signatures` should return empty array when hook has no callbacks.
	 */
	public function test_get_hook_callback_signatures_with_empty_hook() {
		$hook_name = 'test_hook_empty';

		add_action( $hook_name, '__return_true', 10 );
		remove_all_actions( $hook_name );

		$signatures = CallbackUtil::get_hook_callback_signatures( $hook_name );

		$this->assertIsArray( $signatures );
		$this->assertEmpty( $signatures );
	}
}
