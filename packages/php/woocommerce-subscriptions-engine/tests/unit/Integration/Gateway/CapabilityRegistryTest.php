<?php
/**
 * Unit tests for the WordPress-facing CapabilityRegistry.
 *
 * The unit bootstrap stubs no WordPress functions, so these tests load a
 * namespaced `apply_filters()` double (see capability-registry-filter-stub.php)
 * to exercise resolution steps 1 (Core declarations) and 3 (the override
 * filter). Step 2 - reading a live `WC_Payment_Gateway` instance's `$supports`
 * array off `WC()->payment_gateways()` - needs a booted WooCommerce and is
 * covered by integration tests, not here.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Integration\Gateway;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry;

require_once __DIR__ . '/capability-registry-filter-stub.php';

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry
 */
class CapabilityRegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		GatewayCapabilities::reset();
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_calls']  = array();
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_return'] = null;
	}

	protected function tearDown(): void {
		GatewayCapabilities::reset();
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_calls']  = array();
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_return'] = null;
		parent::tearDown();
	}

	public function test_declare_compatibility_delegates_to_core(): void {
		CapabilityRegistry::declare_compatibility( 'dummy', array( GatewayCapabilities::RECURRING ) );

		$this->assertTrue( GatewayCapabilities::is_declared( 'dummy', GatewayCapabilities::RECURRING ) );
	}

	public function test_supports_true_for_a_core_declaration(): void {
		CapabilityRegistry::declare_compatibility( 'dummy', array( GatewayCapabilities::RECURRING ) );

		$this->assertTrue( CapabilityRegistry::supports( 'dummy', GatewayCapabilities::RECURRING ) );
	}

	public function test_supports_false_for_an_undeclared_capability(): void {
		CapabilityRegistry::declare_compatibility( 'dummy', array( GatewayCapabilities::RECURRING ) );

		$this->assertFalse( CapabilityRegistry::supports( 'dummy', GatewayCapabilities::AMOUNT_CHANGES ) );
	}

	public function test_supports_false_for_an_unknown_gateway(): void {
		$this->assertFalse( CapabilityRegistry::supports( 'never-declared', GatewayCapabilities::RECURRING ) );
	}

	public function test_supports_passes_the_resolution_chain_through_the_filter(): void {
		CapabilityRegistry::declare_compatibility( 'dummy', array( GatewayCapabilities::RECURRING ) );

		CapabilityRegistry::supports( 'dummy', GatewayCapabilities::RECURRING );

		$calls = $GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_calls'];
		$this->assertIsArray( $calls );
		$this->assertCount( 1, $calls );
		$this->assertIsArray( $calls[0] );
		$this->assertSame( CapabilityRegistry::CAPABILITY_CHECK_FILTER, $calls[0]['hook'] );
		// The pre-filter value is the steps-1-2 result: true for a declared cap.
		$this->assertTrue( $calls[0]['value'] );
		// Filter args are gateway id, capability, then the order context (null).
		$this->assertSame( array( 'dummy', GatewayCapabilities::RECURRING, null ), $calls[0]['args'] );
	}

	public function test_filter_can_override_a_true_to_false(): void {
		CapabilityRegistry::declare_compatibility( 'dummy', array( GatewayCapabilities::RECURRING ) );
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_return'] = false;

		$this->assertFalse( CapabilityRegistry::supports( 'dummy', GatewayCapabilities::RECURRING ) );
	}

	public function test_filter_can_override_a_false_to_true(): void {
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_return'] = true;

		$this->assertTrue( CapabilityRegistry::supports( 'undeclared', GatewayCapabilities::AMOUNT_CHANGES ) );
	}

	public function test_supports_casts_a_non_bool_filter_result(): void {
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_return'] = '1';

		$result = CapabilityRegistry::supports( 'dummy', GatewayCapabilities::RECURRING );

		$this->assertIsBool( $result );
		$this->assertTrue( $result );
	}
}
