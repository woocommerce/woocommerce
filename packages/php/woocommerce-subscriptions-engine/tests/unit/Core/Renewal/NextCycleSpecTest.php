<?php
/**
 * Unit tests for NextCycleSpec.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Renewal;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\NextCycleSpec;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\NextCycleSpec
 */
class NextCycleSpecTest extends TestCase {

	public function test_exposes_its_period_and_amount(): void {
		$spec = new NextCycleSpec( '2026-02-15 00:00:00', '2026-03-15 00:00:00', '19.99', 'USD' );

		$this->assertSame( '2026-02-15 00:00:00', $spec->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $spec->get_ends_at_gmt() );
		$this->assertSame( 'USD', $spec->get_currency() );
	}

	public function test_normalizes_expected_total_to_the_storage_scale(): void {
		$spec = new NextCycleSpec( '2026-02-15 00:00:00', '2026-03-15 00:00:00', '19.99', 'USD' );

		// Stored on the same DECIMAL(26,8) scale the cycle's expected_total uses.
		$this->assertSame( '19.99000000', $spec->get_expected_total() );
	}

	public function test_normalizes_a_numeric_expected_total(): void {
		$spec = new NextCycleSpec( '2026-02-15 00:00:00', '2026-03-15 00:00:00', '5', 'EUR' );

		$this->assertSame( '5.00000000', $spec->get_expected_total() );
	}
}
