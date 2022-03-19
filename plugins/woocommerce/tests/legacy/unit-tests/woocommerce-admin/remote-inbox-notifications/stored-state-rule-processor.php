<?php
/**
 * Stored state rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\StoredStateRuleProcessor;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RuleEvaluator;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_StoredStateRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_StoredStateRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Empty $stored_state evaluates to false.
	 *
	 * @group fast
	 */
	public function test_empty_stored_state_evaluates_to_false() {
		$processor    = new StoredStateRuleProcessor();
		$rule         = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": true
			}'
		);
		$stored_state = new stdClass();

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( false, $result );
	}

	/**
	 * No matching data keys evaluates to false.
	 *
	 * @group fast
	 */
	public function test_no_matching_stored_state_keys_evaluates_to_false() {
		$processor                      = new StoredStateRuleProcessor();
		$rule                           = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": true
			}'
		);
		$stored_state                   = new stdClass();
		$stored_state->non_matching_key = 'test';

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( false, $result );
	}

	/**
	 * Unrecognized operator fails
	 *
	 * @group fast
	 */
	public function test_unrecognized_operator_fails() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "@@@",
				"value": true
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = true;

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( false, $result );
	}

	/**
	 * Matching data key and equality operator that fails evaluates to false.
	 *
	 * @group fast
	 */
	public function test_matching_stored_state_key_and_equality_op_that_fails_evaluates_to_false() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": true
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = false;

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( false, $result );
	}

	/**
	 * Matching data key and equality operator that succeeds evaluates to true.
	 *
	 * @group fast
	 */
	public function test_matching_stored_state_key_and_equality_op_that_succeeds_evaluates_to_true() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": true
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = true;

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( true, $result );
	}

	/**
	 * Equality operator works with strings in a failing case
	 *
	 * @group fast
	 */
	public function test_equality_op_works_with_strings_in_failing_case_evaluates_to_false() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": "yes there are"
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = 'no there is not';

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( false, $result );
	}

	/**
	 * Equality operator works with strings in a passing case
	 *
	 * @group fast
	 */
	public function test_equality_op_works_with_strings_in_passing_case_evaluates_to_true() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": "yes there are"
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = 'yes there are';

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( true, $result );
	}

	/**
	 * Equality operator works with integers
	 *
	 * @group fast
	 */
	public function test_equality_op_works_with_integers() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": 123
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = 123;

		$result = $processor->process( $rule, $stored_state );

		$this->assertEquals( true, $result );
	}

	/**
	 * Fails on different types
	 *
	 * @group fast
	 */
	public function test_equality_op_fails_on_different_types() {
		$processor                        = new StoredStateRuleProcessor();
		$rule                             = json_decode(
			'{
				"type": "stored_state",
				"index": "there_are_products",
				"operation": "=",
				"value": 123
			}'
		);
		$stored_state                     = new stdClass();
		$stored_state->there_are_products = 123.45;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( false, $result );
	}

	/**
	 * Fails on failing less than op
	 *
	 * @group fast
	 */
	public function test_less_than_op_fails() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": "<",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 120;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( false, $result );
	}

	/**
	 * Passes on passing less than op.
	 *
	 * @group fast
	 */
	public function test_less_than_op_passes() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": "<",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 80;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( true, $result );
	}

	/**
	 * Fails on failing greater than op
	 *
	 * @group fast
	 */
	public function test_greater_than_op_fails() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": ">",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 80;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( false, $result );
	}

	/**
	 * Passes on passing greater than op.
	 *
	 * @group fast
	 */
	public function test_greater_than_op_passes() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": ">",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 120;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( true, $result );
	}

	/**
	 * Fails on failing greater or equal than op
	 *
	 * @group fast
	 */
	public function test_greater_than_or_equal_op_fails() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": ">=",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 80;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( false, $result );
	}

	/**
	 * Passes on passing greater or equal than op.
	 *
	 * @group fast
	 */
	public function test_greater_than_or_equal_op_passes() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": ">=",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 100;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( true, $result );
	}

	/**
	 * Fails on failing less than or equal than op
	 *
	 * @group fast
	 */
	public function test_less_than_or_equal_op_fails() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": "<=",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 120;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( false, $result );
	}

	/**
	 * Passes on passing less than or equal than op.
	 *
	 * @group fast
	 */
	public function test_less_than_or_equal_op_passes() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": "<=",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 100;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( true, $result );
	}

	/**
	 * Fails on failing not equal than op
	 *
	 * @group fast
	 */
	public function test_not_equal_op_fails() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": "!=",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 100;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( false, $result );
	}

	/**
	 * Passes on passing not equal than op.
	 *
	 * @group fast
	 */
	public function test_not_equal_op_passes() {
		$processor                   = new StoredStateRuleProcessor();
		$rule                        = json_decode(
			'{
				"type": "stored_state",
				"index": "product_count",
				"operation": "!=",
				"value": 100
			}'
		);
		$stored_state                = new stdClass();
		$stored_state->product_count = 110;

		$result = $processor->process( $rule, $stored_state, true );

		$this->assertEquals( true, $result );
	}
}
