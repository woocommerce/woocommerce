<?php
/**
 * Integration tests for the `manage_subscription_contract` meta capability.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Ownership;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ContractCapabilities
 */
class ContractCapabilitiesTest extends EngineIntegrationTestCase {

	/**
	 * The contract's customer and store managers may read and manage it; other customers and
	 * guests may not.
	 *
	 * @testWith ["read_subscription_contract"]
	 *           ["manage_subscription_contract"]
	 *
	 * @param string $capability Capability.
	 */
	public function test_customer_and_store_managers_hold_the_capability( string $capability ): void {
		$customer_id = $this->create_user( 'customer' );
		$contract    = $this->create_contract( $customer_id );

		$this->assertTrue( user_can( $customer_id, $capability, $contract ) );
		$this->assertTrue( user_can( $this->create_user( 'shop_manager' ), $capability, $contract ) );
		$this->assertFalse( user_can( $this->create_user( 'customer' ), $capability, $contract ) );
		$this->assertFalse( user_can( 0, $capability, $contract ) );
	}

	/**
	 * A contract without a customer is for store managers only.
	 */
	public function test_contract_without_a_customer_is_for_store_managers(): void {
		$contract = $this->create_contract( null );

		$this->assertFalse( user_can( 0, 'manage_subscription_contract', $contract ) );
		$this->assertTrue( user_can( $this->create_user( 'administrator' ), 'manage_subscription_contract', $contract ) );
	}

	/**
	 * Anything but a `ContractView` is refused, even for an administrator.
	 */
	public function test_requires_a_contract_view(): void {
		$administrator_id = $this->create_user( 'administrator' );
		$contract         = $this->create_contract( null );

		$this->assertFalse( user_can( $administrator_id, 'manage_subscription_contract', $contract->get_id() ) );
		$this->assertFalse( user_can( $administrator_id, 'manage_subscription_contract' ) );
	}

	/**
	 * Create a contract.
	 *
	 * @param int|null $customer_id Customer id.
	 */
	private function create_contract( ?int $customer_id ): ContractView {
		return Contracts::create(
			array(
				'extension_slug' => 'test-extension',
				'status'         => 'active',
				'customer_id'    => $customer_id,
				'currency'       => 'USD',
			)
		);
	}

	/**
	 * Create a user with a role.
	 *
	 * @param string $role Role slug.
	 */
	private function create_user( string $role ): int {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		$this->assertIsInt( $user_id );

		return $user_id;
	}
}
