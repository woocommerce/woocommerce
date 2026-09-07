<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Controller;
use WC_Unit_Test_Case;

/**
 * Tests for the Orders report export methods.
 */
class ControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Controller
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Controller();
	}

	/**
	 * @testdox The customer name export column should be empty for an order with no customer record.
	 */
	public function test_customer_name_is_empty_when_the_order_has_no_customer_record(): void {
		$export_item = $this->sut->prepare_item_for_export( $this->get_item( array() ) );

		$this->assertSame( '', $export_item['customer_name'] );
	}

	/**
	 * @testdox The customer name export column should hold the name a guest order carries on its own.
	 */
	public function test_customer_name_is_read_from_a_partial_customer_record(): void {
		$export_item = $this->sut->prepare_item_for_export(
			$this->get_item(
				array(
					'first_name' => 'Ada',
					'last_name'  => 'Lovelace',
				)
			)
		);

		$this->assertSame( 'Ada Lovelace', $export_item['customer_name'] );
	}

	/**
	 * @testdox The customer name export column should hold a first name on its own.
	 */
	public function test_customer_name_holds_a_first_name_on_its_own(): void {
		$export_item = $this->sut->prepare_item_for_export( $this->get_item( array( 'first_name' => 'Ada' ) ) );

		$this->assertSame( 'Ada', $export_item['customer_name'] );
	}

	/**
	 * Build a report row carrying the given customer record.
	 *
	 * @param array $customer Customer record for the row.
	 * @return array
	 */
	private function get_item( array $customer ): array {
		return array(
			'date'            => '2026-09-04 10:00:00',
			'order_number'    => '123',
			'total_formatted' => '10.00',
			'status'          => 'completed',
			'customer_type'   => 'new',
			'num_items_sold'  => 1,
			'net_total'       => 10.00,
			'extended_info'   => array(
				'products'    => array(),
				'coupons'     => array(),
				'customer'    => $customer,
				'attribution' => array( 'origin' => 'Direct' ),
			),
		);
	}
}
