<?php
/**
 * Integration tests for Contract_Repository.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use Engine_Integration_Test_Case;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract_Status;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Contract_Repository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Contract_Repository
 */
class Contract_Repository_Test extends Engine_Integration_Test_Case {

	private function make_contract(): Contract {
		return Contract::create(
			array(
				'customer_id'          => 42,
				'currency'             => 'USD',
				'selling_plan_id'      => 7,
				'origin_order_id'      => 1001,
				'extension_slug'       => 'lite',
				'payment_method'       => 'woocommerce_payments',
				'payment_method_title' => 'Credit card',
				'payment_token_id'     => 55,
				'billing_total'        => '19.99',
				'start_gmt'            => '2026-06-15 00:00:00',
				'next_payment_gmt'     => '2026-07-15 00:00:00',
				'items'                => array(
					array(
						'item_name'  => 'Coffee bag',
						'item_type'  => 'line_item',
						'product_id' => 200,
						'quantity'   => '1',
						'subtotal'   => '19.99',
						'total'      => '19.99',
					),
				),
				'addresses'            => array(
					Contract::ADDRESS_BILLING  => array(
						'first_name' => 'Ada',
						'last_name'  => 'Lovelace',
						'country'    => 'US',
						'email'      => 'ada@example.test',
					),
					Contract::ADDRESS_SHIPPING => array(
						'first_name' => 'Ada',
						'last_name'  => 'Lovelace',
						'country'    => 'US',
					),
				),
				'meta'                 => array(
					'source_channel' => 'pdp',
				),
			)
		);
	}

	public function test_contract_round_trips_with_children(): void {
		$repo = new Contract_Repository();

		$id = $repo->insert( $this->make_contract() );
		$this->assertGreaterThan( 0, $id );

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Contract::class, $fetched );
		$this->assertSame( $id, $fetched->get_id() );
		$this->assertSame( 42, $fetched->get_customer_id() );
		$this->assertSame( 'USD', $fetched->get_currency() );
		$this->assertSame( 'lite', $fetched->get_extension_slug() );
		$this->assertSame( Contract_Status::ACTIVE, $fetched->get_status() );
		$this->assertSame( '2026-07-15 00:00:00', $fetched->get_next_payment_gmt() );

		// Payment instrument reference.
		$instrument = $fetched->get_payment_instrument();
		$this->assertSame( 55, $instrument->get_token_id() );
		$this->assertSame( 'woocommerce_payments', $instrument->get_gateway() );

		// Items.
		$items = $fetched->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'Coffee bag', $items[0]['item_name'] );

		// Addresses.
		$addresses = $fetched->get_addresses();
		$this->assertArrayHasKey( Contract::ADDRESS_BILLING, $addresses );
		$this->assertArrayHasKey( Contract::ADDRESS_SHIPPING, $addresses );
		$this->assertSame( 'Ada', $addresses[ Contract::ADDRESS_BILLING ]['first_name'] );

		// Meta.
		$this->assertSame( 'pdp', $fetched->get_meta()['source_channel'] );
	}

	public function test_extension_slug_defaults_to_null_when_unset(): void {
		$repo = new Contract_Repository();

		$id = $repo->insert(
			Contract::create(
				array(
					'customer_id'     => 1,
					'currency'        => 'EUR',
					'selling_plan_id' => 2,
					'origin_order_id' => 3,
					'start_gmt'       => '2026-06-15 00:00:00',
				)
			)
		);

		$this->assertNull( $repo->find( $id )->get_extension_slug() );
	}

	public function test_delete_removes_contract_and_children(): void {
		global $wpdb;

		$repo = new Contract_Repository();
		$id   = $repo->insert( $this->make_contract() );

		$this->assertTrue( $repo->delete( $id ) );
		$this->assertNull( $repo->find( $id ) );

		$items_table = \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Schema_Installer::get_table_name(
			\Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Schema_Installer::TABLE_CONTRACT_ITEMS
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE contract_id = %d", $id ) );

		$this->assertSame( '0', $remaining );
	}
}
