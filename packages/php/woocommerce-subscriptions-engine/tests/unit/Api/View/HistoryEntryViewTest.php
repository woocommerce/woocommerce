<?php
/**
 * Unit tests for the HistoryEntryView DTO.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Api\View;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\HistoryEntryView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\HistoryEntry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\View\HistoryEntryView
 */
class HistoryEntryViewTest extends TestCase {

	public function test_every_getter_reads_the_stored_row(): void {
		$entry = HistoryEntry::from_storage(
			array(
				'id'               => '9',
				'contract_id'      => '10',
				'cycle_id'         => '4',
				'type'             => 'renewal-failed',
				'creation_key'     => 'renewal-failed-4-77',
				'payload'          => '{"order_id":77}',
				'date_created_gmt' => '2026-02-01 00:00:00',
			)
		);

		$view = HistoryEntryView::from_entry( $entry );

		$this->assertSame( 9, $view->get_id() );
		$this->assertSame( 10, $view->get_contract_id() );
		$this->assertSame( 4, $view->get_cycle_id() );
		$this->assertSame( 'renewal-failed', $view->get_type() );
		$this->assertSame( array( 'order_id' => 77 ), $view->get_payload() );
		$this->assertSame( '2026-02-01 00:00:00', $view->get_date_created_gmt() );
		$this->assertFalse( ( new \ReflectionClass( HistoryEntryView::class ) )->hasMethod( 'get_creation_key' ), 'The creation key is not exposed.' );
	}

	public function test_an_entry_without_a_cycle_or_payload_reads_null(): void {
		$entry = HistoryEntry::from_storage(
			array(
				'id'               => 1,
				'contract_id'      => 2,
				'cycle_id'         => null,
				'type'             => 'held',
				'payload'          => null,
				'date_created_gmt' => '2026-02-01 00:00:00',
			)
		);

		$view = HistoryEntryView::from_entry( $entry );

		$this->assertNull( $view->get_cycle_id() );
		$this->assertNull( $view->get_payload() );
	}
}
