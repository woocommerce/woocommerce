<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Proxies;

use Automattic\WooCommerce\Proxies\ActionsProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the ActionsProxy class.
 */
class ActionsProxyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ActionsProxy
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ActionsProxy();
	}

	/**
	 * @testdox 'did_action' returns the number of times an action hook has been fired.
	 */
	public function test_did_action_returns_fire_count(): void {
		$tag = 'wc_test_did_action_' . uniqid();

		$this->assertSame( 0, $this->sut->did_action( $tag ) );

		do_action( $tag );
		$this->assertSame( 1, $this->sut->did_action( $tag ) );

		do_action( $tag );
		$this->assertSame( 2, $this->sut->did_action( $tag ) );
	}

	/**
	 * @testdox 'apply_filters' returns the filtered value after all callbacks are applied.
	 */
	public function test_apply_filters_returns_filtered_value(): void {
		$tag = 'wc_test_apply_filters_' . uniqid();

		add_filter( $tag, fn( $v ) => $v . '_filtered' );

		$result = $this->sut->apply_filters( $tag, 'original' );

		$this->assertSame( 'original_filtered', $result );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'apply_filters' passes additional parameters to callbacks.
	 */
	public function test_apply_filters_passes_extra_parameters(): void {
		$tag = 'wc_test_apply_filters_params_' . uniqid();

		add_filter(
			$tag,
			function ( $value, $extra ) {
				return $value . '_' . $extra;
			},
			10,
			2
		);

		$result = $this->sut->apply_filters( $tag, 'value', 'extra' );

		$this->assertSame( 'value_extra', $result );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'apply_filters_ref_array' returns the filtered value when arguments are passed as an array.
	 */
	public function test_apply_filters_ref_array_returns_filtered_value(): void {
		$tag = 'wc_test_apply_filters_ref_array_' . uniqid();

		add_filter( $tag, fn( $v ) => $v * 2 );

		$result = $this->sut->apply_filters_ref_array( $tag, array( 5 ) );

		$this->assertSame( 10, $result );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'do_action' executes all callbacks registered to an action hook.
	 */
	public function test_do_action_fires_registered_callbacks(): void {
		$tag     = 'wc_test_do_action_' . uniqid();
		$invoked = 0;

		add_action( $tag, function () use ( &$invoked ) {
			$invoked++;
		} );

		$this->sut->do_action( $tag );

		$this->assertSame( 1, $invoked );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'do_action' passes additional arguments to callbacks.
	 */
	public function test_do_action_passes_arguments_to_callbacks(): void {
		$tag      = 'wc_test_do_action_args_' . uniqid();
		$received = null;

		add_action(
			$tag,
			function ( $arg ) use ( &$received ) {
				$received = $arg;
			}
		);

		$this->sut->do_action( $tag, 'hello' );

		$this->assertSame( 'hello', $received );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'do_action_ref_array' executes callbacks with arguments supplied as an array.
	 */
	public function test_do_action_ref_array_fires_callbacks_with_array_args(): void {
		$tag      = 'wc_test_do_action_ref_array_' . uniqid();
		$received = null;

		add_action(
			$tag,
			function ( $arg ) use ( &$received ) {
				$received = $arg;
			}
		);

		$this->sut->do_action_ref_array( $tag, array( 'from_array' ) );

		$this->assertSame( 'from_array', $received );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'add_action' registers a callback that is invoked when the action fires.
	 */
	public function test_add_action_registers_callback(): void {
		$tag     = 'wc_test_add_action_' . uniqid();
		$invoked = false;

		$this->sut->add_action( $tag, function () use ( &$invoked ) {
			$invoked = true;
		} );

		do_action( $tag );

		$this->assertTrue( $invoked );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'remove_action' deregisters a previously added callback.
	 */
	public function test_remove_action_deregisters_callback(): void {
		$tag      = 'wc_test_remove_action_' . uniqid();
		$invoked  = false;
		$callback = function () use ( &$invoked ) {
			$invoked = true;
		};

		add_action( $tag, $callback );
		$this->sut->remove_action( $tag, $callback );
		do_action( $tag );

		$this->assertFalse( $invoked );
	}

	/**
	 * @testdox 'has_action' returns false when no callbacks are registered for the hook.
	 */
	public function test_has_action_returns_false_when_no_callbacks(): void {
		$tag = 'wc_test_has_action_empty_' . uniqid();

		$this->assertFalse( $this->sut->has_action( $tag ) );
	}

	/**
	 * @testdox 'has_action' returns the priority when the given callback is registered.
	 */
	public function test_has_action_returns_priority_for_registered_callback(): void {
		$tag      = 'wc_test_has_action_' . uniqid();
		$callback = '__return_true';

		add_action( $tag, $callback, 15 );

		$this->assertSame( 15, $this->sut->has_action( $tag, $callback ) );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'add_filter' registers a callback that modifies the filtered value.
	 */
	public function test_add_filter_registers_callback(): void {
		$tag = 'wc_test_add_filter_' . uniqid();

		$this->sut->add_filter( $tag, fn( $v ) => $v + 10 );

		$result = apply_filters( $tag, 5 );

		$this->assertSame( 15, $result );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'remove_filter' deregisters a previously added filter callback.
	 */
	public function test_remove_filter_deregisters_callback(): void {
		$tag      = 'wc_test_remove_filter_' . uniqid();
		$callback = fn( $v ) => $v . '_modified';

		add_filter( $tag, $callback );
		$this->sut->remove_filter( $tag, $callback );

		$result = apply_filters( $tag, 'original' );

		$this->assertSame( 'original', $result );
	}

	/**
	 * @testdox 'has_filter' returns false when no callbacks are registered for the hook.
	 */
	public function test_has_filter_returns_false_when_no_callbacks(): void {
		$tag = 'wc_test_has_filter_empty_' . uniqid();

		$this->assertFalse( $this->sut->has_filter( $tag ) );
	}

	/**
	 * @testdox 'has_filter' returns the priority when the given callback is registered.
	 */
	public function test_has_filter_returns_priority_for_registered_callback(): void {
		$tag      = 'wc_test_has_filter_' . uniqid();
		$callback = '__return_true';

		add_filter( $tag, $callback, 20 );

		$this->assertSame( 20, $this->sut->has_filter( $tag, $callback ) );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'current_filter' returns the name of the hook currently being executed.
	 */
	public function test_current_filter_returns_current_hook_name(): void {
		$tag    = 'wc_test_current_filter_' . uniqid();
		$result = null;

		add_filter(
			$tag,
			function ( $v ) use ( &$result ) {
				$result = current_filter();
				return $v;
			}
		);

		apply_filters( $tag, true );

		$this->assertSame( $tag, $result );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'doing_action' returns true when the given action is currently being executed.
	 */
	public function test_doing_action_returns_true_inside_action(): void {
		$tag    = 'wc_test_doing_action_' . uniqid();
		$result = null;
		$sut    = $this->sut;

		add_action(
			$tag,
			function () use ( $tag, &$result, $sut ) {
				$result = $sut->doing_action( $tag );
			}
		);

		do_action( $tag );

		$this->assertTrue( $result );

		remove_all_filters( $tag );
	}

	/**
	 * @testdox 'doing_action' returns false when the given action is not currently being executed.
	 */
	public function test_doing_action_returns_false_outside_action(): void {
		$tag = 'wc_test_doing_action_outside_' . uniqid();

		$this->assertFalse( $this->sut->doing_action( $tag ) );
	}
}
