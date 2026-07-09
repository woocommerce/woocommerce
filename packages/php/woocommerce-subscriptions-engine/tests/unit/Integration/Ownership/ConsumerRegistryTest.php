<?php
/**
 * Unit tests for the ConsumerRegistry (the set of engine-consuming extensions).
 *
 * The registry is WordPress-free (only the file-access guard), so it loads and
 * runs in the autoloader-only unit bootstrap.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Integration\Ownership;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry
 */
class ConsumerRegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		ConsumerRegistry::reset();
	}

	protected function tearDown(): void {
		ConsumerRegistry::reset();
		parent::tearDown();
	}

	public function test_is_empty_when_nothing_is_registered(): void {
		$this->assertTrue( ConsumerRegistry::is_empty() );
		$this->assertSame( array(), ConsumerRegistry::all() );
	}

	public function test_register_records_a_consumer(): void {
		ConsumerRegistry::register( 'lite' );

		$this->assertFalse( ConsumerRegistry::is_empty() );
		$this->assertSame( array( 'lite' ), ConsumerRegistry::all() );
	}

	public function test_register_is_idempotent_for_the_same_slug(): void {
		ConsumerRegistry::register( 'lite' );
		ConsumerRegistry::register( 'lite' );

		$this->assertSame( array( 'lite' ), ConsumerRegistry::all() );
	}

	public function test_register_records_multiple_consumers(): void {
		ConsumerRegistry::register( 'lite' );
		ConsumerRegistry::register( 'premium' );

		$this->assertFalse( ConsumerRegistry::is_empty() );

		$all = ConsumerRegistry::all();
		sort( $all );
		$this->assertSame( array( 'lite', 'premium' ), $all );
	}

	public function test_register_ignores_an_empty_slug(): void {
		ConsumerRegistry::register( '' );

		$this->assertTrue( ConsumerRegistry::is_empty(), 'A blank slug must not flip the gate open.' );
		$this->assertSame( array(), ConsumerRegistry::all() );
	}

	public function test_register_ignores_a_whitespace_only_slug_and_trims(): void {
		ConsumerRegistry::register( "  \t\n" );
		$this->assertTrue( ConsumerRegistry::is_empty(), 'A whitespace-only slug must not open the gate.' );

		ConsumerRegistry::register( '  woocommerce-subscriptions-lite  ' );
		$this->assertSame( array( 'woocommerce-subscriptions-lite' ), ConsumerRegistry::all(), 'The slug is stored trimmed.' );
	}

	public function test_unregister_removes_only_the_named_consumer(): void {
		ConsumerRegistry::register( 'lite' );
		ConsumerRegistry::register( 'premium' );

		ConsumerRegistry::unregister( 'lite' );
		$this->assertSame( array( 'premium' ), ConsumerRegistry::all(), 'Only the named consumer is removed.' );

		ConsumerRegistry::unregister( ' premium ' );
		$this->assertTrue( ConsumerRegistry::is_empty(), 'The slug is trimmed, matching register().' );

		ConsumerRegistry::unregister( 'unknown' );
		$this->assertTrue( ConsumerRegistry::is_empty(), 'An unknown slug is a no-op.' );
	}

	public function test_reset_clears_every_registration(): void {
		ConsumerRegistry::register( 'lite' );
		ConsumerRegistry::reset();

		$this->assertTrue( ConsumerRegistry::is_empty() );
		$this->assertSame( array(), ConsumerRegistry::all() );
	}
}
