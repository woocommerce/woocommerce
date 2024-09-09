<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Traits;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Tests for the AccessiblePrivateMethods class.
 */
class AccessiblePrivateMethodsTest extends \WC_Unit_Test_Case {
	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		remove_all_filters( 'filter_handled_privately' );
		remove_all_actions( 'action_handled_privately' );
		remove_all_actions( 'action_handled_publicly' );
		remove_all_filters( 'static_filter_handled_privately' );
		remove_all_actions( 'static_action_handled_privately' );
		remove_all_actions( 'static_action_handled_publicly' );

		parent::setUp();
	}

	/**
	 * @testdox Public instance and static methods are still accessible in classes implementing the trait.
	 */
	public function test_public_methods_are_still_accessible() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public function public_return_one() {
				return 1;
			}

			public static function public_static_return_ten() {
				return 10;
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->assertEquals( 1, $sut->public_return_one() );
		$this->assertEquals( 10, $sut::public_static_return_ten() );
	}

	/**
	 * @testdox Private and protected instance and static methods are still inaccessible by default in classes implementing the trait.
	 *
	 * @testWith ["protected_return_two"]
	 *           ["private_return_three"]
	 *           ["static_protected_return_twenty"]
	 *           ["static_private_return_thirty"]
	 *
	 * @param string $method_name The name of the method to try to call.
	 */
	public function test_private_and_protected_methods_are_still_inaccessible_by_default( string $method_name ) {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			protected function protected_return_two() {
				return 2;
			}

			private function private_return_three() {
				return 3;
			}

			protected static function static_protected_return_twenty() {
				return 20;
			}

			private static function static_private_return_thirty() {
				return 30;
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'Call to private method ' . get_class( $sut ) . '::' . $method_name );

		if ( StringUtil::starts_with( $method_name, 'static' ) ) {
			$sut::$method_name();
		} else {
			$sut->$method_name();
		}
	}

	/**
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $call_static_method True to call the non-existing method statically, false to call it in an object instance.
	 *
	 * @testdox Calling non-existing methods still throws an error if there's no __call or __callStatic method in the parent class.
	 */
	public function test_non_existing_methods_still_throw_error_if_no_call_method_in_parent( bool $call_static_method ) {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;
		};
		//phpcs:enable Squiz.Commenting

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'Call to undefined method ' . get_class( $sut ) . '::non_existing' );

		if ( $call_static_method ) {
			$sut::non_existing();
		} else {
			$sut->non_existing();
		}
	}

	/**
	 * @testdox Calling non-existing methods redirects to __call method in the parent class if available.
	 */
	public function test_non_existing_methods_redirect_to_parent_call_method_if_available() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() extends BaseClass {
			use AccessiblePrivateMethods;
		};
		//phpcs:enable Squiz.Commenting

		$result = $sut->method_in_parent_class( 'foo' );
		$this->assertEquals( 'Argument: foo', $result );
	}

	/**
	 * @testdox Calling static non-existing methods redirects to __call method in the parent class if available.
	 */
	public function test_static_non_existing_methods_redirect_to_parent_call_method_if_available() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() extends BaseClass {
			use AccessiblePrivateMethods;
		};
		//phpcs:enable Squiz.Commenting

		$result = $sut::static_method_in_parent_class( 'foo' );
		$this->assertEquals( 'Static argument: foo', $result );
	}

	/**
	 * @testdox Private and protected methods can be made accessible by calling mark_method_as_accessible.
	 */
	public function test_private_and_protected_methods_can_be_made_accessible() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public function __construct() {
				$this->mark_method_as_accessible( 'protected_return_two' );
				$this->mark_method_as_accessible( 'private_return_three' );
			}

			//phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag
			final public static function init() {
				self::mark_static_method_as_accessible( 'protected_return_twenty' );
				self::mark_static_method_as_accessible( 'private_return_thirty' );
			}

			protected function protected_return_two() {
				return 2;
			}

			private function private_return_three() {
				return 3;
			}

			protected static function protected_return_twenty() {
				return 20;
			}

			private static function private_return_thirty() {
				return 30;
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->assertEquals( 2, $sut->protected_return_two() );
		$this->assertEquals( 3, $sut->private_return_three() );

		$sut::init();

		$this->assertEquals( 20, $sut::protected_return_twenty() );
		$this->assertEquals( 30, $sut::private_return_thirty() );
	}

	/**
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $call_static_method True to call the non-existing method statically, false to call it in an object instance.
	 *
	 * @testdox Trying to mark a non existing method as accessible with mark_method_as_accessible does nothing.
	 */
	public function test_accessibilizing_non_existing_method_does_nothing( bool $call_static_method ) {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public function __construct() {
				$this->mark_method_as_accessible( 'non_existing' );
			}

			//phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag
			final public static function init() {
				self::mark_static_method_as_accessible( 'non_existing' );
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'Call to undefined method ' . get_class( $sut ) . '::non_existing' );

		if ( $call_static_method ) {
			$sut::non_existing();
		} else {
			$sut->non_existing();
		}
	}

	/**
	 * @testWith [true]
	 *           [false]
	 *
	 * @testdox New add_(static_)action and add_(static_)filter methods can be used to register private and protected class methods as hook callbacks.
	 */
	public function test_private_and_protected_hook_handler_methods_can_be_made_accessible() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public $action_argument = null;

			public static $static_action_argument = null;

			public function __construct() {
				self::add_action( 'action_handled_privately', array( $this, 'handle_action' ) );
				self::add_filter( 'filter_handled_privately', array( $this, 'handle_filter' ) );
			}

			//phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag
			final public static function init() {
				self::add_action( 'static_action_handled_privately', array( __CLASS__, 'handle_static_action' ) );
				self::add_filter( 'static_filter_handled_privately', array( __CLASS__, 'handle_static_filter' ) );
			}

			private function handle_action( $argument ) {
				$this->action_argument = $argument;
			}

			private function handle_filter( $argument ) {
				return 'Filter argument: ' . $argument;
			}

			private static function handle_static_action( $argument ) {
				self::$static_action_argument = $argument;
			}

			private static function handle_static_filter( $argument ) {
				return 'Static filter argument: ' . $argument;
			}
		};
		//phpcs:enable Squiz.Commenting

		$sut::init();

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment

		do_action( 'action_handled_privately', 'foo' );
		$this->assertEquals( 'foo', $sut->action_argument );

		$filter_result = apply_filters( 'filter_handled_privately', 'bar' );
		$this->assertEquals( 'Filter argument: bar', $filter_result );

		do_action( 'static_action_handled_privately', 'fizz' );
		$this->assertEquals( 'fizz', $sut::$static_action_argument );

		$filter_result = apply_filters( 'static_filter_handled_privately', 'buzz' );
		$this->assertEquals( 'Static filter argument: buzz', $filter_result );

		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * @testdox add_action and add_filter methods can be used to register public class methods as hook callbacks, although that's not needed.
	 */
	public function test_accessibilizing_public_method_does_nothing() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public $action_argument = null;

			public static $static_action_argument = null;

			public function __construct() {
				self::add_action( 'action_handled_publicly', array( $this, 'handle_action_publicly' ) );
			}

			//phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag
			final public static function init() {
				self::add_action( 'static_action_handled_publicly', array( __CLASS__, 'handle_static_action_publicly' ) );
			}

			public function handle_action_publicly( $argument ) {
				$this->action_argument = $argument;
			}

			public static function handle_static_action_publicly( $argument ) {
				self::$static_action_argument = $argument;
			}
		};
		//phpcs:enable Squiz.Commenting

		$sut::init();

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_handled_publicly', 'foo' );
		$this->assertEquals( 'foo', $sut->action_argument );

		do_action( 'static_action_handled_publicly', 'bar' );
		$this->assertEquals( 'bar', $sut::$static_action_argument );

		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * @testdox A hook attached to a private or protected method can be easily unhooked externally.
	 */
	public function test_unhooking_private_methods() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public $action_argument = null;

			public static $static_action_argument = null;

			public function __construct() {
				self::add_action( 'action_handled_privately', array( $this, 'handle_action' ) );
				self::add_filter( 'filter_handled_privately', array( $this, 'handle_filter' ) );
			}

			//phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag
			final public static function init() {
				self::add_action( 'static_action_handled_privately', array( __CLASS__, 'handle_static_action' ) );
				self::add_filter( 'static_filter_handled_privately', array( __CLASS__, 'handle_static_filter' ) );
			}

			private function handle_action( $argument ) {
				$this->action_argument = $argument;
			}

			private function handle_filter( $argument ) {
				return 'Filter argument: ' . $argument;
			}

			private static function handle_static_action( $argument ) {
				self::$static_action_argument = $argument;
			}

			private static function handle_static_filter( $argument ) {
				return 'Static filter argument: ' . $argument;
			}
		};
		//phpcs:enable Squiz.Commenting

		$sut::init();

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment

		$filter_result = apply_filters( 'filter_handled_privately', 'foo' );
		$this->assertEquals( 'Filter argument: foo', $filter_result );

		$filter_result = apply_filters( 'static_filter_handled_privately', 'bar' );
		$this->assertEquals( 'Static filter argument: bar', $filter_result );

		do_action( 'action_handled_privately', 'foo2' );
		$this->assertEquals( 'foo2', $sut->action_argument );

		do_action( 'static_action_handled_privately', 'bar2' );
		$this->assertEquals( 'bar2', $sut::$static_action_argument );

		remove_action( 'action_handled_privately', array( $sut, 'handle_action' ) );
		remove_filter( 'filter_handled_privately', array( $sut, 'handle_filter' ) );
		remove_action( 'static_action_handled_privately', array( get_class( $sut ), 'handle_static_action' ) );
		remove_filter( 'static_filter_handled_privately', array( get_class( $sut ), 'handle_static_filter' ) );

		$filter_result = apply_filters( 'filter_handled_privately', 'fizz' );
		$this->assertEquals( 'fizz', $filter_result );

		$filter_result = apply_filters( 'static_filter_handled_privately', 'buzz' );
		$this->assertEquals( 'buzz', $filter_result );

		do_action( 'action_handled_privately', 'fizz2' );
		$this->assertEquals( 'foo2', $sut->action_argument );

		do_action( 'static_action_handled_privately', 'buzz2' );
		$this->assertEquals( 'bar2', $sut::$static_action_argument );

		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * @testWith ["action"]
	 *           ["filter"]
	 *
	 * @testdox Trying to use 'add_action' or 'add_filter' statically throws an error hinting at the proper method names.
	 *
	 * @param string $action_or_filter 'action' or 'filter'.
	 * @return void
	 */
	public function test_instance_add_action_and_filter_methods_throw_error_with_hint_when_called_statically( $action_or_filter ) {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;
		};

		$method_name        = "add_{$action_or_filter}";
		$proper_method_name = "add_static_{$action_or_filter}";

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( get_class( $sut ) . '::' . "$method_name can't be called statically, did you mean '$proper_method_name'?" );

		$sut::$method_name( 'some_action', function() {} );
	}
}

//phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4
/**
 * Class used in the inherited __call method test.
 */
class BaseClass {
	//phpcs:disable Squiz.Commenting.FunctionComment.Missing
	public function __call( $name, $arguments ) {
		if ( 'method_in_parent_class' === $name ) {
			return 'Argument: ' . $arguments[0];
		}
	}

	public static function __callStatic( $name, $arguments ) {
		if ( 'static_method_in_parent_class' === $name ) {
			return 'Static argument: ' . $arguments[0];
		}
	}
	//phpcs:enable Squiz.Commenting.FunctionComment.Missing
}

//phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4
