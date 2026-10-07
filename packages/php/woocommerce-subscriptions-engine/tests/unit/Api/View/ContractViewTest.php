<?php
/**
 * Unit tests for the ContractView DTO.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Api\View;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView
 */
class ContractViewTest extends TestCase {

	/**
	 * A complete stored contract row.
	 *
	 * @return array<string, mixed>
	 */
	private function row(): array {
		return array(
			'id'                   => 10,
			'status'               => 'active',
			'customer_id'          => 1,
			'currency'             => 'USD',
			'selling_plan_id'      => 2,
			'origin_order_id'      => 3,
			'extension_slug'       => 'acme-subs',
			'payment_method'       => 'dummy',
			'payment_method_title' => 'Dummy',
			'payment_token_id'     => 4,
			'start_gmt'            => '2026-01-01 00:00:00',
			'next_payment_gmt'     => '2026-02-01 00:00:00',
			'last_payment_gmt'     => '2026-01-01 00:00:00',
			'last_attempt_gmt'     => '2026-01-01 00:00:01',
			'trial_end_gmt'        => '2026-01-08 00:00:00',
			'end_gmt'              => '2027-01-01 00:00:00',
			'billing_total'        => '20.00',
			'discount_total'       => '1.00',
			'shipping_total'       => '5.00',
			'tax_total'            => '2.50',
			'schedule_source'      => Contract::SCHEDULE_SOURCE_GATEWAY,
		);
	}

	public function test_every_getter_reads_the_stored_row(): void {
		$view = ContractView::from_contract( Contract::from_storage( $this->row() ), false );

		$this->assertSame( 10, $view->get_id() );
		$this->assertSame( 'active', $view->get_status() );
		$this->assertSame( 'acme-subs', $view->get_extension_slug() );
		$this->assertSame( 1, $view->get_customer_id() );
		$this->assertSame( 'USD', $view->get_currency() );
		$this->assertSame( 2, $view->get_selling_plan_id() );
		$this->assertSame( 3, $view->get_origin_order_id() );
		$this->assertSame( 'dummy', $view->get_payment_method() );
		$this->assertSame( 'Dummy', $view->get_payment_method_title() );
		$this->assertSame( 4, $view->get_payment_token_id() );
		$this->assertSame( '2026-01-01 00:00:00', $view->get_start_gmt() );
		$this->assertSame( '2026-02-01 00:00:00', $view->get_next_payment_gmt() );
		$this->assertSame( '2026-01-01 00:00:00', $view->get_last_payment_gmt() );
		$this->assertSame( '2026-01-01 00:00:01', $view->get_last_attempt_gmt() );
		$this->assertSame( '2026-01-08 00:00:00', $view->get_trial_end_gmt() );
		$this->assertSame( '2027-01-01 00:00:00', $view->get_end_gmt() );
		$this->assertSame( '20.00000000', $view->get_billing_total() );
		$this->assertSame( '1.00000000', $view->get_discount_total() );
		$this->assertSame( '5.00000000', $view->get_shipping_total() );
		$this->assertSame( '2.50000000', $view->get_tax_total() );
		$this->assertSame( Contract::SCHEDULE_SOURCE_GATEWAY, $view->get_schedule_source() );
	}

	public function test_optional_fields_read_as_null(): void {
		$view = ContractView::from_contract( Contract::from_storage( array( 'id' => 5 ) ), true );

		$this->assertNull( $view->get_extension_slug() );
		$this->assertNull( $view->get_customer_id() );
		$this->assertNull( $view->get_currency() );
		$this->assertNull( $view->get_selling_plan_id() );
		$this->assertNull( $view->get_payment_method() );
		$this->assertNull( $view->get_start_gmt() );
		$this->assertNull( $view->get_plan_snapshot() );
	}

	public function test_children_are_null_when_not_loaded(): void {
		$view = ContractView::from_contract( Contract::from_storage( $this->row(), null, array( array( 'item_name' => 'Tea' ) ) ), false );

		$this->assertNull( $view->get_items() );
		$this->assertNull( $view->get_addresses() );
	}

	public function test_children_are_projected_onto_the_write_shape(): void {
		$items     = array(
			array(
				'id'           => '7',
				'contract_id'  => '42',
				'item_name'    => 'Tea',
				'item_type'    => 'line_item',
				'product_id'   => '12',
				'variation_id' => null,
				'quantity'     => '2.0000',
				'subtotal'     => '10.00000000',
				'total'        => '9.00000000',
				'taxes'        => '{"total":{"1":"0.90"}}',
			),
		);
		$addresses = array(
			'billing' => array(
				'id'           => '3',
				'contract_id'  => '42',
				'address_type' => 'billing',
				'city'         => 'Lisbon',
			),
		);

		$loaded = ContractView::from_contract( Contract::from_storage( $this->row(), null, $items, $addresses ), true );
		$empty  = ContractView::from_contract( Contract::from_storage( $this->row() ), true );

		$this->assertSame(
			array(
				array(
					'item_name'    => 'Tea',
					'item_type'    => 'line_item',
					'product_id'   => 12,
					'variation_id' => null,
					'quantity'     => '2.0000',
					'subtotal'     => '10.00000000',
					'total'        => '9.00000000',
					'taxes'        => array( 'total' => array( 1 => '0.90' ) ),
				),
			),
			$loaded->get_items()
		);
		$addresses = $loaded->get_addresses();
		$this->assertIsArray( $addresses );
		$this->assertSame( array( 'billing' ), array_keys( $addresses ) );
		$this->assertSame( Contract::ADDRESS_FIELDS, array_keys( $addresses['billing'] ) );
		$this->assertSame( 'Lisbon', $addresses['billing']['city'] );
		$this->assertNull( $addresses['billing']['phone'] );
		$this->assertSame( array(), $empty->get_items() );
		$this->assertSame( array(), $empty->get_addresses() );
	}

	public function test_plan_snapshot_payload_passes_through(): void {
		$payload = array(
			'selling_plan_id' => 2,
			'billing_policy'  => array(
				'period'   => 'month',
				'interval' => 1,
			),
		);

		$view = ContractView::from_contract( Contract::from_storage( $this->row(), PlanSnapshot::from_array( $payload ) ), false );

		$this->assertSame( $payload, $view->get_plan_snapshot() );
	}
}
