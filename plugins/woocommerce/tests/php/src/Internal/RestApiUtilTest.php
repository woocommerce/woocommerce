<?php

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\RestApiUtil;

/**
 * Tests for the RestApiUtil class.
 * @package Automattic\WooCommerce\Tests\Internal
 */
class RestApiUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox 'adjust_create_refund_request_parameters' adjust the 'reason' parameter to null if it's somehow empty.
	 *
	 * @testWith ["", null]
	 *           ["none", null]
	 *           ["foo", "foo"]
	 *
	 * @param string $input_reason The value of 'reason' to test.
	 * @param string $expected_output_reason The expected converted value of 'reason'.
	 */
	public function test_adjust_create_refund_request_parameters_adjusts_reason( $input_reason, $expected_output_reason ) {
		$request = new \WP_REST_Request();
		if ( 'none' !== $input_reason ) {
			$request['reason'] = $input_reason;
		}

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$this->assertEquals( $expected_output_reason, $request['reason'] );
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' adjust the 'api_refund' parameter if it's missing or non-boolean.
	 *
	 * @testWith ["none", true]
	 *           ["foobar", true]
	 *           [0, true]
	 *           [true, true]
	 *           [false, false]
	 *
	 * @param mixed $input_api_refund The value of 'api_refund' to test.
	 * @param bool  $expected_output_api_refund The expected adjusted value of 'api_refund'.
	 */
	public function test_adjust_create_refund_request_parameters_adjusts_api_refund( $input_api_refund, $expected_output_api_refund ) {
		$request = new \WP_REST_Request();
		if ( 'none' !== $input_api_refund ) {
			$request['api_refund'] = $input_api_refund;
		}

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$this->assertEquals( $expected_output_api_refund, $request['api_refund'] );
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' adjust the 'line items' array to be associative, and converts the 'quantity' parameters to 'qty'.
	 */
	public function test_adjust_create_refund_request_parameters_adjusts_line_items_and_their_quantities() {
		$line_items = array(
			array(
				'id'       => '1',
				'quantity' => 10,
				'qty'      => 20,
			),
			array(
				'id'       => '2',
				'quantity' => 30,
			),
			array(
				'id'  => '3',
				'qty' => 40,
			),
		);

		$request               = new \WP_REST_Request();
		$request['line_items'] = $line_items;

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$expected = array(
			'1' => array(
				'qty' => 20,
			),
			'2' => array(
				'qty' => 30,
			),
			'3' => array(
				'qty' => 40,
			),
		);

		$this->assertEquals( $expected, $request['line_items'] );
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' adjusts the line item taxes to be associative arrays.
	 */
	public function test_adjust_create_refund_request_parameters_adjusts_line_item_taxes() {
		$line_items = array(
			array(
				'id'         => '1',
				'refund_tax' => array(
					array(
						'id'           => '2',
						'refund_total' => 10,
					),
					array(
						'id'           => '3',
						'refund_total' => 20,
					),
				),
			),
		);

		$request               = new \WP_REST_Request();
		$request['line_items'] = $line_items;

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$expected = array(
			'1' => array(
				'refund_tax' => array(
					'2' => 10,
					'3' => 20,
				),
			),
		);

		$this->assertEquals( $expected, $request['line_items'] );
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' adjusts nothing if the input is already in internal format.
	 */
	public function test_adjust_create_refund_request_parameters_does_nothing_on_line_items_if_input_is_in_internal_format() {
		$line_items = array(
			'1' => array(
				'qty'        => 2,
				'refund_tax' => array(
					'2' => 10,
					'3' => 20,
				),
			),
		);

		$request               = new \WP_REST_Request();
		$request['line_items'] = $line_items;

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$this->assertEquals( $line_items, $request['line_items'] );
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' calculates 'amount' if it's missing and all the partial amounts data is valid.
	 */
	public function test_adjust_create_refund_request_parameters_calculates_amount_if_missing() {
		$line_items = array(
			'1' => array(
				'refund_total' => 10,
				'refund_tax'   => array(
					'2' => 20,
					'3' => 30,
				),
			),
			'2' => array(
				'refund_total' => 40,
			),
		);

		$request               = new \WP_REST_Request();
		$request['line_items'] = $line_items;

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$expected = strval( 10 + 20 + 30 + 40 );
		$this->assertEquals( $expected, $request['amount'] );
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' doesn't change 'amount' if it's already present.
	 */
	public function test_adjust_create_refund_request_parameters_does_not_change_amount_if_present() {
		$line_items = array(
			'1' => array(
				'refund_total' => 10,
				'refund_tax'   => array(
					'2' => 20,
					'3' => 30,
				),
			),
		);

		$request               = new \WP_REST_Request();
		$request['amount']     = '999';
		$request['line_items'] = $line_items;

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$this->assertEquals( '999', $request['amount'] );
	}

	/**
	 * Data provider for data_for_test_adjust_create_refund_request_parameters_does_not_change_amount_if_invalid_data.
	 *
	 * @return array Data for the tests.
	 */
	public function data_for_test_adjust_create_refund_request_parameters_does_not_change_amount_if_invalid_data() {
		return array(
			// No line items.
			array(
				array(),
			),

			// Non-array line items.
			array(
				'Foobar',
			),

			// Missing refund_total in line item.
			array(
				array(
					'1' => array(
						'refund_tax' => array(
							'2' => 20,
							'3' => 30,
						),
					),
					'2' => array(
						'refund_total' => 10,
					),
				),
			),

			// Non-numeric refund_total in line item.
			array(
				array(
					'1' => array(
						'refund_total' => 'foobar',
						'refund_tax'   => array(
							'2' => 20,
							'3' => 30,
						),
					),
					'2' => array(
						'refund_total' => 10,
					),
				),
			),

			// Non-numeric tax amount.
			array(
				'1' => array(
					'refund_total' => 10,
					'refund_tax'   => array(
						'2' => 'Foobar',
					),
				),
				'2' => array(
					'refund_total' => 10,
				),
			),
		);
	}

	/**
	 * @testdox 'adjust_create_refund_request_parameters' doesn't set 'amount' if some partial amounts data is missing or invalid.
	 *
	 * @dataProvider data_for_test_adjust_create_refund_request_parameters_does_not_change_amount_if_invalid_data
	 *
	 * @param array $line_items The line items array to test.
	 */
	public function test_adjust_create_refund_request_parameters_does_not_change_amount_if_invalid_data( $line_items ) {
		$request               = new \WP_REST_Request();
		$request['line_items'] = $line_items;

		RestApiUtil::adjust_create_refund_request_parameters( $request );

		$this->assertFalse( isset( $request['amount'] ) );
	}
}
