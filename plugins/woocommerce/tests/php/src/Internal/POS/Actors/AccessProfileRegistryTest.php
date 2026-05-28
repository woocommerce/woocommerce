<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Actors;

use Automattic\WooCommerce\Internal\POS\Actors\AccessProfileRegistry;
use WC_Unit_Test_Case;

/**
 * @since 10.9.0
 * @group pos-actors
 */
class AccessProfileRegistryTest extends WC_Unit_Test_Case {

	public function test_built_in_profiles_exist(): void {
		$registry = new AccessProfileRegistry();
		$this->assertTrue( $registry->exists( AccessProfileRegistry::PROFILE_CASHIER ) );
		$this->assertTrue( $registry->exists( AccessProfileRegistry::PROFILE_MANAGER ) );
		$this->assertTrue( $registry->exists( AccessProfileRegistry::PROFILE_ADMIN ) );
	}

	public function test_cashier_refund_is_approval_required(): void {
		$registry = new AccessProfileRegistry();
		$this->assertSame(
			AccessProfileRegistry::ACCESS_APPROVAL_REQUIRED,
			$registry->resolve(
				AccessProfileRegistry::PROFILE_CASHIER,
				AccessProfileRegistry::TAG_REFUND_ORDERS
			)
		);
	}

	public function test_manager_can_approve_overrides(): void {
		$registry = new AccessProfileRegistry();
		$this->assertSame(
			AccessProfileRegistry::ACCESS_ALLOW,
			$registry->resolve(
				AccessProfileRegistry::PROFILE_MANAGER,
				AccessProfileRegistry::TAG_MANAGER_APPROVAL
			)
		);
	}

	public function test_unknown_profile_resolves_to_deny(): void {
		$registry = new AccessProfileRegistry();
		$this->assertSame(
			AccessProfileRegistry::ACCESS_DENY,
			$registry->resolve( 'unknown_profile', AccessProfileRegistry::TAG_PROCESS_SALES )
		);
	}

	public function test_filter_can_inject_a_profile(): void {
		$callback = function ( array $profiles ): array {
			$profiles['pos_test_role'] = array(
				'name'        => 'Test',
				'permissions' => array(
					AccessProfileRegistry::TAG_PROCESS_SALES => AccessProfileRegistry::ACCESS_ALLOW,
				),
			);
			return $profiles;
		};

		add_filter( 'woocommerce_pos_access_profiles', $callback );

		$registry = new AccessProfileRegistry();
		$this->assertTrue( $registry->exists( 'pos_test_role' ) );
		$this->assertSame(
			AccessProfileRegistry::ACCESS_ALLOW,
			$registry->resolve( 'pos_test_role', AccessProfileRegistry::TAG_PROCESS_SALES )
		);

		remove_filter( 'woocommerce_pos_access_profiles', $callback );
	}
}
