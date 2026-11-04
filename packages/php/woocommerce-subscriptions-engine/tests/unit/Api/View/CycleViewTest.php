<?php
/**
 * Unit tests for the CycleView DTO.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Api\View;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\CycleView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\View\CycleView
 */
class CycleViewTest extends TestCase {

	public function test_every_getter_reads_the_stored_row(): void {
		$cycle = Cycle::from_storage(
			array(
				'id'             => 9,
				'contract_id'    => 10,
				'kind'           => Cycle::KIND_BILLING,
				'sequence_no'    => 2,
				'count'          => 2,
				'status'         => 'billed',
				'starts_at_gmt'  => '2026-02-01 00:00:00',
				'ends_at_gmt'    => '2026-03-01 00:00:00',
				'expected_total' => '20.00',
				'currency'       => 'USD',
				'order_id'       => 77,
			)
		);

		$view = CycleView::from_cycle( $cycle );

		$this->assertSame( 9, $view->get_id() );
		$this->assertSame( 10, $view->get_contract_id() );
		$this->assertSame( Cycle::KIND_BILLING, $view->get_kind() );
		$this->assertSame( 2, $view->get_sequence_no() );
		$this->assertSame( 2, $view->get_count() );
		$this->assertSame( 'billed', $view->get_status() );
		$this->assertSame( '2026-02-01 00:00:00', $view->get_starts_at_gmt() );
		$this->assertSame( '2026-03-01 00:00:00', $view->get_ends_at_gmt() );
		$this->assertSame( '20.00000000', $view->get_expected_total() );
		$this->assertSame( 'USD', $view->get_currency() );
		$this->assertSame( 77, $view->get_order_id() );
	}

	public function test_a_non_counting_cycle_without_an_order_reads_null(): void {
		$cycle = Cycle::from_storage(
			array(
				'id'            => 1,
				'contract_id'   => 2,
				'sequence_no'   => 1,
				'count'         => null,
				'status'        => 'pending',
				'starts_at_gmt' => '2026-02-01 00:00:00',
				'ends_at_gmt'   => '2026-03-01 00:00:00',
				'currency'      => 'USD',
			)
		);

		$view = CycleView::from_cycle( $cycle );

		$this->assertNull( $view->get_count() );
		$this->assertNull( $view->get_order_id() );
		$this->assertSame( 'pending', $view->get_status() );
	}
}
