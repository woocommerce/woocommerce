<?php

namespace Automattic\WooCommerce\Tests\Internal\Traits;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

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

		parent::setUp();
	}

	/**
	 * @testdox Public methods are still accessible in classes implementing the trait.
	 */
	public function test_public_methods_are_still_accessible() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public function public_return_one() {
				return 1;
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->assertEquals( 1, $sut->public_return_one() );
	}

	/**
	 * @testdox Private and protected methods are still inaccessible by default in classes implementing the trait.
	 *
	 * @testWith ["protected_return_two"]
	 *           ["private_return_three"]
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
		};
		//phpcs:enable Squiz.Commenting

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'Call to private method ' . get_class( $sut ) . '::' . $method_name );

		$sut->$method_name();
	}

	/**
	 * @testdox Calling non-existing methods still throws an error if there's no __call method in the parent class.
	 */
	public function test_non_existing_methods_still_throw_error_if_no_call_method_in_parent() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;
		};
		//phpcs:enable Squiz.Commenting

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'Call to undefined method ' . get_class( $sut ) . '::non_existing' );

		$sut->non_existing();
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

			protected function protected_return_two() {
				return 2;
			}

			private function private_return_three() {
				return 3;
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->assertEquals( 2, $sut->protected_return_two() );
		$this->assertEquals( 3, $sut->private_return_three() );
	}

	/**
	 * @testdox Trying to mark a non existing method as accessible with mark_method_as_accessible does nothing.
	 */
	public function test_accessibilizing_non_existing_method_does_nothing() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public function __construct() {
				$this->mark_method_as_accessible( 'non_existing' );
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->expectException( \Error::class );
		$this->expectExceptionMessage( 'Call to undefined method ' . get_class( $sut ) . '::non_existing' );

		$sut->non_existing();
	}

	/**
	 * @testWith [true]
	 *           [false]
	 *
	 * @testdox New add_action and add_filter methods can be used to register private and protected class methods as hook callbacks.
	 *
	 * @param bool $use_string_syntax True to set hooks passing the method name, false to use the standard [$this, 'method_name'] syntax.
	 */
	public function test_private_and_protected_hook_handler_methods_can_be_made_accessible( bool $use_string_syntax ) {
		//phpcs:disable Squiz.Commenting
		$sut = new class($use_string_syntax) {
			use AccessiblePrivateMethods;

			public $action_argument = null;

			public function __construct( bool $use_string_syntax ) {
				if ( $use_string_syntax ) {
					$this->add_action( 'action_handled_privately', 'handle_action' );
					$this->add_action( 'filter_handled_privately', 'handle_filter' );
				} else {
					$this->add_action( 'action_handled_privately', array( $this, 'handle_action' ) );
					$this->add_action( 'filter_handled_privately', array( $this, 'handle_filter' ) );
				}
			}

			private function handle_action( $argument ) {
				$this->action_argument = $argument;
			}

			private function handle_filter( $argument ) {
				return 'Filter argument: ' . $argument;
			}
		};
		//phpcs:enable Squiz.Commenting

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment

		do_action( 'action_handled_privately', 'foo' );
		$this->assertEquals( 'foo', $sut->action_argument );

		$filter_result = apply_filters( 'filter_handled_privately', 'bar' );
		$this->assertEquals( 'Filter argument: bar', $filter_result );

		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * @testdox Trying to make a public method accessible with mark_method_as_accessible does nothing.
	 */
	public function test_accessibilizing_public_method_does_nothing() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public $action_argument = null;

			public function __construct() {
				$this->add_action( 'action_handled_publicly', array( $this, 'handle_action_publicly' ) );
			}

			public function handle_action_publicly( $argument ) {
				$this->action_argument = $argument;
			}
		};
		//phpcs:enable Squiz.Commenting

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_handled_publicly', 'foo' );
		$this->assertEquals( 'foo', $sut->action_argument );
	}

	/**
	 * @testdox A hook attached to a private or protected method can be easily unhooked externally.
	 */
	public function test_unhooking_private_methods() {
		//phpcs:disable Squiz.Commenting
		$sut = new class() {
			use AccessiblePrivateMethods;

			public $action_argument = null;

			public function __construct() {
				$this->add_action( 'filter_handled_privately', array( $this, 'handle_filter' ) );
			}

			private function handle_filter( $argument ) {
				return 'Filter argument: ' . $argument;
			}
		};
		//phpcs:enable Squiz.Commenting

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment

		$filter_result = apply_filters( 'filter_handled_privately', 'bar' );
		$this->assertEquals( 'Filter argument: bar', $filter_result );

		remove_filter( 'filter_handled_privately', array( $sut, 'handle_filter' ) );

		$filter_result = apply_filters( 'filter_handled_privately', 'bar' );
		$this->assertEquals( 'bar', $filter_result );

		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
	}
}

//phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch
/**
 * Class used in the inherited __call method test.
 */
class BaseClass {
	//phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public function __call( $name, $arguments ) {
		if ( 'method_in_parent_class' === $name ) {
			return 'Argument: ' . $arguments[0];
		}
	}
}
//phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch
