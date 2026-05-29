<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use WC_Unit_Test_Case;

/**
 * Unit tests for the user-meta-based POS access model.
 *
 * Covers the role enum, the absence of implicit fallback from WP roles, the
 * capability matrix per POS role, and set_pos_role validation.
 */
class CapabilitiesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Returns the assigned POS role meta value when set.
	 */
	public function test_get_pos_role_returns_meta_value(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_role( $user_id, Capabilities::POS_ROLE_CASHIER );

		$this->assertSame( Capabilities::POS_ROLE_CASHIER, Capabilities::get_pos_role( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox WordPress administrators do NOT have implicit POS access.
	 *
	 * Per Proposal 1, POS roles are orthogonal to WP roles — an administrator
	 * must be explicitly assigned a POS role like any other user.
	 */
	public function test_administrator_without_explicit_pos_role_has_no_access(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertNull( Capabilities::get_pos_role( $user_id ) );
		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox WordPress shop managers do NOT have implicit POS access.
	 */
	public function test_shop_manager_without_explicit_pos_role_has_no_access(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );

		$this->assertNull( Capabilities::get_pos_role( $user_id ) );
		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Administrators can be explicitly assigned a POS role.
	 */
	public function test_administrator_can_be_assigned_pos_admin(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		Capabilities::set_pos_role( $user_id, Capabilities::POS_ROLE_ADMIN );

		$this->assertSame( Capabilities::POS_ROLE_ADMIN, Capabilities::get_pos_role( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Rejects setting a non-assignable POS role.
	 */
	public function test_set_pos_role_rejects_invalid_value(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertFalse( Capabilities::set_pos_role( $user_id, 'bogus' ) );
		$this->assertNull( Capabilities::get_pos_role( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Clears the POS role meta when null is passed.
	 */
	public function test_set_pos_role_null_clears_meta(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_role( $user_id, Capabilities::POS_ROLE_MANAGER );
		$this->assertSame( Capabilities::POS_ROLE_MANAGER, Capabilities::get_pos_role( $user_id ) );

		Capabilities::set_pos_role( $user_id, null );

		$this->assertNull( Capabilities::get_pos_role( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Assignable roles are exactly the three POS roles.
	 */
	public function test_assignable_pos_roles(): void {
		$this->assertSame(
			array(
				Capabilities::POS_ROLE_CASHIER,
				Capabilities::POS_ROLE_MANAGER,
				Capabilities::POS_ROLE_ADMIN,
			),
			Capabilities::assignable_pos_roles()
		);
	}

	/**
	 * @testdox Cashier capability map matches the M1 matrix.
	 */
	public function test_capabilities_for_cashier(): void {
		$caps = Capabilities::capabilities_for_role( Capabilities::POS_ROLE_CASHIER );

		$this->assertSame( true, $caps[ Capabilities::CAP_PROCESS_SALES ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_VIEW_ORDERS ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_APPLY_COUPONS ] ?? null );
		$this->assertArrayNotHasKey( Capabilities::CAP_ISSUE_REFUNDS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_CREATE_COUPONS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_MANAGE_POS_STAFF, $caps );
	}

	/**
	 * @testdox Manager capability map is a superset of the cashier matrix.
	 */
	public function test_capabilities_for_manager(): void {
		$caps = Capabilities::capabilities_for_role( Capabilities::POS_ROLE_MANAGER );

		$this->assertSame( true, $caps[ Capabilities::CAP_PROCESS_SALES ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_ISSUE_REFUNDS ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_CREATE_COUPONS ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_VIEW_POS_SETTINGS ] ?? null );
		$this->assertArrayNotHasKey( Capabilities::CAP_EDIT_POS_SETTINGS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_MANAGE_POS_STAFF, $caps );
	}

	/**
	 * @testdox pos_admin capability map includes admin-only POS caps.
	 */
	public function test_capabilities_for_pos_admin(): void {
		$caps = Capabilities::capabilities_for_role( Capabilities::POS_ROLE_ADMIN );

		$this->assertSame( true, $caps[ Capabilities::CAP_EDIT_POS_SETTINGS ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_MANAGE_POS_STAFF ] ?? null );
		$this->assertSame( true, $caps[ Capabilities::CAP_EXIT_POS ] ?? null );
	}

	/**
	 * @testdox Returns an empty capability map for unknown POS roles.
	 */
	public function test_capabilities_for_unknown_role_is_empty(): void {
		$this->assertSame( array(), Capabilities::capabilities_for_role( 'bogus' ) );
	}
}
