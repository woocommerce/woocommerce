<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductScopes as TestedProductScopes;

/**
 * Tests for the ProductScopes shared helper.
 */
class ProductScopes extends \WC_Unit_Test_Case {

	/**
	 * Reset the helper's static state between tests so it does not bleed.
	 */
	public function tearDown(): void {
		try {
			TestedProductScopes::reset();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox name_scope_element() gives different names to elements whose identifying values differ.
	 */
	public function test_name_scope_element_differs_by_values(): void {
		$first  = TestedProductScopes::name_scope_element( array( 1 ) );
		$second = TestedProductScopes::name_scope_element( array( 2 ) );

		$this->assertNotSame( $first, $second );
	}

	/**
	 * @testdox name_scope_element() appends an occurrence number only from the second call with the same values.
	 */
	public function test_name_scope_element_appends_occurrence_from_second_call(): void {
		$first  = TestedProductScopes::name_scope_element( array( 5 ) );
		$second = TestedProductScopes::name_scope_element( array( 5 ) );
		$third  = TestedProductScopes::name_scope_element( array( 5 ) );

		$this->assertNotSame( $first, $second );
		$this->assertNotSame( $second, $third );
		$this->assertSame( $first . '-2', $second, 'The second occurrence should carry the number 2 appended to the first occurrence\'s name.' );
		$this->assertSame( $first . '-3', $third, 'The third occurrence should carry the number 3 appended to the first occurrence\'s name.' );
	}

	/**
	 * @testdox enter_place() and leave_place() pair, restoring the previously open place.
	 */
	public function test_enter_and_leave_place_pair(): void {
		$this->assertSame( '', TestedProductScopes::current_place(), 'No place should be open at the start.' );

		$name = TestedProductScopes::enter_place( array( 10, '' ) );

		$this->assertSame( $name, TestedProductScopes::current_place() );

		$left = TestedProductScopes::leave_place();

		$this->assertSame( $name, $left, 'Leaving should return the name entering returned.' );
		$this->assertSame( '', TestedProductScopes::current_place(), 'No place should be open after leaving the only one.' );
	}

	/**
	 * @testdox leave_place() restores the outer place when leaving a nested one.
	 */
	public function test_leave_place_restores_outer_place(): void {
		$outer = TestedProductScopes::enter_place( array( 1, '' ) );
		$inner = TestedProductScopes::enter_place( array( 2, $outer ) );

		$this->assertSame( $inner, TestedProductScopes::current_place() );

		$left_inner = TestedProductScopes::leave_place();

		$this->assertSame( $inner, $left_inner );
		$this->assertSame( $outer, TestedProductScopes::current_place(), 'Leaving the inner place should restore the outer one.' );

		TestedProductScopes::leave_place();

		$this->assertSame( '', TestedProductScopes::current_place() );
	}

	/**
	 * @testdox leave_place() called with no place open reports the misuse, returns '' and leaves the stack as it found it.
	 */
	public function test_leave_place_without_open_place_reports_and_returns_empty(): void {
		$values = array( 42, '' );

		$first_name = TestedProductScopes::enter_place( $values );
		TestedProductScopes::leave_place();

		$this->setExpectedIncorrectUsage( 'leave_place' );

		$left = TestedProductScopes::leave_place();

		$this->assertSame( '', $left, 'An unpaired leave should return the page-level name.' );
		$this->assertSame( '', TestedProductScopes::current_place(), 'The unpaired call should leave no place open.' );

		$second_name = TestedProductScopes::enter_place( $values );
		TestedProductScopes::leave_place();

		$this->assertSame( $first_name . '-2', $second_name, 'The unpaired call should not have consumed or added an occurrence, so entering the same values again should return the same name a normal second occurrence would.' );
	}

	/**
	 * @testdox enter_place() names the same element differently at page level than nested in another place.
	 */
	public function test_place_nested_in_another_differs_from_page_level(): void {
		$page_level_name = TestedProductScopes::enter_place( array( 5, '' ) );
		TestedProductScopes::leave_place();

		$outer       = TestedProductScopes::enter_place( array( 1, '' ) );
		$nested_name = TestedProductScopes::enter_place( array( 5, $outer ) );

		$this->assertNotSame( $page_level_name, $nested_name );
	}

	/**
	 * @testdox enter_place() names the same element differently when entered in two different places.
	 */
	public function test_same_element_in_two_places_gets_different_names(): void {
		$place_a   = TestedProductScopes::enter_place( array( 10, '' ) );
		$name_in_a = TestedProductScopes::enter_place( array( 99, $place_a ) );
		TestedProductScopes::leave_place();
		TestedProductScopes::leave_place();

		$place_b   = TestedProductScopes::enter_place( array( 20, '' ) );
		$name_in_b = TestedProductScopes::enter_place( array( 99, $place_b ) );

		$this->assertNotSame( $name_in_a, $name_in_b );
	}

	/**
	 * @testdox name_form() only declares a scope from the second form for the same place and product.
	 */
	public function test_name_form_declares_only_from_second_occurrence(): void {
		$first_in_place = TestedProductScopes::name_form( array( '', 100 ) );
		$this->assertFalse( $first_in_place['declares'], 'The first form for a place and product should declare no scope.' );

		$second_in_place = TestedProductScopes::name_form( array( '', 100 ) );
		$this->assertTrue( $second_in_place['declares'], 'A second form for the same place and product should declare its own scope.' );
		$this->assertNotSame( $first_in_place['name'], $second_in_place['name'] );

		$other_product = TestedProductScopes::name_form( array( '', 200 ) );
		$this->assertFalse( $other_product['declares'], 'A form for a different product in the same place should declare no scope.' );

		$other_place = TestedProductScopes::name_form( array( 'some-place', 100 ) );
		$this->assertFalse( $other_place['declares'], 'A form for the same product in a different place should declare no scope.' );
	}

	/**
	 * @testdox get_scope_context() returns productId, variation and scopeName as its only keys.
	 */
	public function test_get_scope_context_holds_only_the_three_keys(): void {
		$context = TestedProductScopes::get_scope_context( 42, array(), 'wc-place-abcd1234' );

		$this->assertSame(
			array(
				'productId' => 42,
				'variation' => array(),
				'scopeName' => 'wc-place-abcd1234',
			),
			$context
		);
	}

	/**
	 * @testdox get_scope_context() carries a non-empty variation through unchanged.
	 */
	public function test_get_scope_context_carries_the_variation(): void {
		$variation = array(
			array(
				'attribute' => 'pa_color',
				'value'     => 'red',
			),
		);

		$context = TestedProductScopes::get_scope_context( 7, $variation, 'wc-form-1234abcd' );

		$this->assertSame( $variation, $context['variation'] );
	}

	/**
	 * @testdox The current-form surface returns the most recently set name and nothing once cleared.
	 */
	public function test_current_form_name_set_get_and_clear(): void {
		$this->assertSame( '', TestedProductScopes::get_current_form_name(), 'No form name should be set initially.' );

		TestedProductScopes::set_current_form_name( 'wc-form-first' );
		$this->assertSame( 'wc-form-first', TestedProductScopes::get_current_form_name() );

		TestedProductScopes::set_current_form_name( 'wc-form-second' );
		$this->assertSame( 'wc-form-second', TestedProductScopes::get_current_form_name(), 'Should return the most recently set name.' );

		TestedProductScopes::clear_current_form_name();
		$this->assertSame( '', TestedProductScopes::get_current_form_name() );
	}

	/**
	 * @testdox get_grouped_child_scope_name() names a child row from the current form's name while it renders.
	 */
	public function test_grouped_child_name_derives_from_current_form_name(): void {
		TestedProductScopes::set_current_form_name( 'wc-form-1234abcd' );

		$child_name = TestedProductScopes::get_grouped_child_scope_name( TestedProductScopes::get_current_form_name(), 55 );

		$this->assertSame( 'wc-form-1234abcd:55', $child_name );
	}

	/**
	 * @testdox get_grouped_child_scope_name() gives the child the same name a form and the child row would each compute.
	 */
	public function test_grouped_child_scope_name_is_deterministic_from_form_name_and_child_id(): void {
		$form = TestedProductScopes::name_form( array( '', 300 ) );

		$name_from_form_context = TestedProductScopes::get_grouped_child_scope_name( $form['name'], 42 );
		$name_from_child_row    = TestedProductScopes::get_grouped_child_scope_name( $form['name'], 42 );

		$this->assertSame( $name_from_form_context, $name_from_child_row );
	}

	/**
	 * @testdox reset() clears occurrence counts and open places so a key's first occurrence carries no number again.
	 */
	public function test_reset_clears_occurrence_counts_and_open_places(): void {
		$first  = TestedProductScopes::name_scope_element( array( 7 ) );
		$second = TestedProductScopes::name_scope_element( array( 7 ) );
		$this->assertNotSame( $first, $second, 'Sanity check: the second call should carry an occurrence number before reset.' );

		TestedProductScopes::enter_place( array( 8, '' ) );

		TestedProductScopes::reset();

		$this->assertSame( '', TestedProductScopes::current_place(), 'No place should be open after reset.' );

		$after_reset = TestedProductScopes::name_scope_element( array( 7 ) );
		$this->assertSame( $first, $after_reset, "After reset, the key's first occurrence should carry no number again." );
	}

	/**
	 * @testdox Replaying the same sequence of calls after a reset yields the same names in the same order.
	 */
	public function test_reset_makes_a_replayed_page_produce_identical_names(): void {
		$build_page = function (): array {
			$names   = array();
			$names[] = TestedProductScopes::enter_place( array( 1, '' ) );
			$names[] = TestedProductScopes::name_scope_element( array( 2 ) );
			$form    = TestedProductScopes::name_form( array( TestedProductScopes::current_place(), 3 ) );
			$names[] = $form['name'];
			$names[] = TestedProductScopes::leave_place();

			return $names;
		};

		$first_pass = $build_page();
		TestedProductScopes::reset();
		$second_pass = $build_page();

		$this->assertSame( $first_pass, $second_pass );
	}
}
