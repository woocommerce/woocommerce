<?php
/**
 * Unit tests for the pure GatewayCapabilities declaration store.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Gateway;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities
 */
class GatewayCapabilitiesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		GatewayCapabilities::reset();
	}

	protected function tearDown(): void {
		GatewayCapabilities::reset();
		parent::tearDown();
	}

	public function test_known_capabilities_lists_every_flag(): void {
		$this->assertSame(
			array(
				'recurring',
				'payment_method_change',
				'amount_changes',
				'multiple_per_customer',
				'gateway_scheduled_renewals',
			),
			GatewayCapabilities::known_capabilities()
		);
	}

	public function test_constants_match_their_string_values(): void {
		$this->assertSame( 'recurring', GatewayCapabilities::RECURRING );
		$this->assertSame( 'payment_method_change', GatewayCapabilities::PAYMENT_METHOD_CHANGE );
		$this->assertSame( 'amount_changes', GatewayCapabilities::AMOUNT_CHANGES );
		$this->assertSame( 'multiple_per_customer', GatewayCapabilities::MULTIPLE_PER_CUSTOMER );
		$this->assertSame( 'gateway_scheduled_renewals', GatewayCapabilities::GATEWAY_SCHEDULED_RENEWALS );
	}

	public function test_declared_capabilities_resolve_true(): void {
		GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::RECURRING ) );

		$this->assertTrue( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
	}

	public function test_undeclared_capability_resolves_false(): void {
		GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::RECURRING ) );

		$this->assertFalse( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::AMOUNT_CHANGES ) );
	}

	public function test_unknown_gateway_resolves_false(): void {
		$this->assertFalse( GatewayCapabilities::is_declared( 'never-declared', GatewayCapabilities::RECURRING ) );
	}

	public function test_declare_with_unknown_flag_throws(): void {
		$this->expectException( InvalidArgumentException::class );

		GatewayCapabilities::declare( 'dummy', array( 'totally_made_up' ) );
	}

	public function test_declare_rejects_the_whole_batch_when_one_flag_is_unknown(): void {
		try {
			GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::RECURRING, 'bogus' ) );
			$this->fail( 'Expected an InvalidArgumentException for the unknown flag.' );
		} catch ( InvalidArgumentException $e ) {
			// The valid flag in the same batch must not have been stored.
			$this->assertFalse( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
		}
	}

	public function test_redeclaration_replaces_rather_than_merges(): void {
		GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::RECURRING, GatewayCapabilities::AMOUNT_CHANGES ) );
		GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::PAYMENT_METHOD_CHANGE ) );

		$this->assertTrue( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::PAYMENT_METHOD_CHANGE ) );
		$this->assertFalse( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
		$this->assertFalse( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::AMOUNT_CHANGES ) );
	}

	public function test_duplicate_flags_are_deduplicated(): void {
		GatewayCapabilities::declare(
			'dummy',
			array(
				GatewayCapabilities::RECURRING,
				GatewayCapabilities::RECURRING,
				GatewayCapabilities::AMOUNT_CHANGES,
			)
		);

		// Behaviourally a duplicate must not change the answer; declaring twice
		// is still a single positive declaration.
		$this->assertTrue( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
		$this->assertTrue( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::AMOUNT_CHANGES ) );
	}

	public function test_empty_declaration_clears_a_gateway(): void {
		GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::RECURRING ) );
		GatewayCapabilities::declare( 'dummy', array() );

		$this->assertFalse( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
	}

	public function test_declarations_are_isolated_per_gateway(): void {
		GatewayCapabilities::declare( 'a', array( GatewayCapabilities::RECURRING ) );
		GatewayCapabilities::declare( 'b', array( GatewayCapabilities::AMOUNT_CHANGES ) );

		$this->assertTrue( GatewayCapabilities::is_declared( 'a', GatewayCapabilities::RECURRING ) );
		$this->assertFalse( GatewayCapabilities::is_declared( 'a', GatewayCapabilities::AMOUNT_CHANGES ) );
		$this->assertTrue( GatewayCapabilities::is_declared( 'b', GatewayCapabilities::AMOUNT_CHANGES ) );
		$this->assertFalse( GatewayCapabilities::is_declared( 'b', GatewayCapabilities::RECURRING ) );
	}

	public function test_reset_clears_all_declarations(): void {
		GatewayCapabilities::declare( 'dummy', array( GatewayCapabilities::RECURRING ) );
		GatewayCapabilities::reset();

		$this->assertFalse( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
	}
}
