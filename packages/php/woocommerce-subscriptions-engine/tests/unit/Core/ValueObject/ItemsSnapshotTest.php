<?php
/**
 * Unit tests for ItemsSnapshot.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\ValueObject;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot
 */
class ItemsSnapshotTest extends TestCase {

	/**
	 * A representative two-item set.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function sample_items(): array {
		return array(
			array(
				'product_id' => 42,
				'quantity'   => 2,
				'total'      => '20.00',
			),
			array(
				'product_id' => 7,
				'quantity'   => 1,
				'total'      => '5.00',
			),
		);
	}

	public function test_schema_version_defaults_to_one(): void {
		$snapshot = ItemsSnapshot::from_items( $this->sample_items() );

		$this->assertSame( 1, $snapshot->get_schema_version() );
	}

	public function test_schema_version_is_carried(): void {
		$snapshot = ItemsSnapshot::from_items( $this->sample_items(), 2 );

		$this->assertSame( 2, $snapshot->get_schema_version() );
	}

	public function test_round_trips_through_items(): void {
		$items    = $this->sample_items();
		$snapshot = ItemsSnapshot::from_items( $items );

		$this->assertSame( $items, $snapshot->get_items() );
	}

	public function test_to_payload_preserves_the_item_list_order(): void {
		$snapshot = ItemsSnapshot::from_items( $this->sample_items() );

		$payload = $snapshot->to_payload();

		// The item list order is meaningful and preserved; no per-item key
		// reordering happens (dedup is copy-forward, not a canonical hash).
		$this->assertSame( 42, $payload[0]['product_id'] );
		$this->assertSame( 7, $payload[1]['product_id'] );
	}

	public function test_from_payload_reconstructs_an_equal_snapshot(): void {
		$snapshot = ItemsSnapshot::from_items( $this->sample_items(), 2 );

		$restored = ItemsSnapshot::from_payload( $snapshot->to_payload(), $snapshot->get_schema_version() );

		$this->assertSame( $snapshot->to_payload(), $restored->to_payload() );
		$this->assertSame( 2, $restored->get_schema_version() );
	}

	public function test_empty_item_set_is_allowed(): void {
		$snapshot = ItemsSnapshot::from_items( array() );

		$this->assertSame( array(), $snapshot->get_items() );
		$this->assertSame( array(), $snapshot->to_payload() );
	}

	public function test_negative_schema_version_throws(): void {
		$this->expectException( DomainException::class );

		ItemsSnapshot::from_items( $this->sample_items(), -1 );
	}
}
