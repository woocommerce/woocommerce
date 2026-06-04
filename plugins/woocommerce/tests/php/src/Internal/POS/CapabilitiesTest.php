<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use WC_Unit_Test_Case;

/**
 * Unit tests for the POS access model.
 *
 * Covers preset assignment, the absence of implicit access from WP roles, the
 * capability matrix per preset, set_pos_preset validation, and the pos_staff
 * WP role label wiring (added/removed by set_pos_preset; not auto-reapplied
 * when the users.php dropdown overwrites it — admin's explicit intent wins).
 */
class CapabilitiesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Returns the assigned POS preset meta value when set.
	 */
	public function test_get_pos_preset_returns_meta_value(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_CASHIER );

		$this->assertSame( Capabilities::POS_PRESET_CASHIER, Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox WordPress administrators do NOT have implicit POS access.
	 *
	 * POS access requires an explicit preset assignment. Holding the
	 * administrator WP role grants no POS capabilities on its own.
	 */
	public function test_administrator_without_explicit_preset_has_no_access(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );
		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox WordPress shop managers do NOT have implicit POS access.
	 */
	public function test_shop_manager_without_explicit_preset_has_no_access(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );

		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );
		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Administrators can be explicitly assigned a POS preset.
	 */
	public function test_administrator_can_be_assigned_pos_admin(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_ADMIN );

		$this->assertSame( Capabilities::POS_PRESET_ADMIN, Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Rejects setting a non-assignable preset value.
	 */
	public function test_set_pos_preset_rejects_invalid_value(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertFalse( Capabilities::set_pos_preset( $user_id, 'bogus' ) );
		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Returns false when assigning a preset to a non-existent user.
	 */
	public function test_set_pos_preset_rejects_unknown_user(): void {
		$this->assertFalse( Capabilities::set_pos_preset( 0, Capabilities::POS_PRESET_CASHIER ) );
		$this->assertFalse( Capabilities::set_pos_preset( 9999999, Capabilities::POS_PRESET_CASHIER ) );
	}

	/**
	 * @testdox Clears the POS preset meta when null is passed.
	 */
	public function test_set_pos_preset_null_clears_meta(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_MANAGER );
		$this->assertSame( Capabilities::POS_PRESET_MANAGER, Capabilities::get_pos_preset( $user_id ) );

		Capabilities::set_pos_preset( $user_id, null );

		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Assigning a preset to an existing user leaves their WP role untouched.
	 *
	 * Granting POS access must not bolt the pos_staff role onto an existing user
	 * (e.g. a shop_manager) — that is the role that the users.php dropdown could
	 * silently overwrite, the bug this model avoids. Access comes from the caps,
	 * not a role.
	 */
	public function test_set_pos_preset_does_not_add_pos_staff_role(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );

		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_MANAGER );

		$user = get_userdata( $user_id );
		$this->assertSame(
			array( 'shop_manager' ),
			$user->roles,
			'Granting POS access must not change the user\'s WP roles.'
		);
		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Clearing a preset leaves the user's WP roles untouched.
	 *
	 * A POS-only account (created with pos_staff as its sole role) keeps that
	 * role — revoking access never leaves it roleless — and an existing user
	 * keeps their own role. Access is revoked purely by stripping the caps.
	 */
	public function test_set_pos_preset_null_leaves_roles_untouched(): void {
		$pos_only = self::factory()->user->create( array( 'role' => Capabilities::POS_STAFF_ROLE ) );
		$existing = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		Capabilities::set_pos_preset( $pos_only, Capabilities::POS_PRESET_CASHIER );
		Capabilities::set_pos_preset( $existing, Capabilities::POS_PRESET_MANAGER );

		Capabilities::set_pos_preset( $pos_only, null );
		Capabilities::set_pos_preset( $existing, null );

		$this->assertSame( array( Capabilities::POS_STAFF_ROLE ), get_userdata( $pos_only )->roles );
		$this->assertSame( array( 'shop_manager' ), get_userdata( $existing )->roles );
		$this->assertFalse( Capabilities::has_pos_access( $pos_only ), 'Caps stripped → no access.' );
		$this->assertFalse( Capabilities::has_pos_access( $existing ), 'Caps stripped → no access.' );

		wp_delete_user( $pos_only );
		wp_delete_user( $existing );
	}

	/**
	 * @testdox Assigning a preset grants the preset's pos_* caps as real WP capabilities.
	 */
	public function test_set_pos_preset_grants_real_wp_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_MANAGER );

		// user_can() reflects whatever is in the user's wp_capabilities meta.
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_PROCESS_SALES ) );
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_CREATE_COUPONS ) );
		// Manager does not include admin-only caps.
		$this->assertFalse( user_can( $user_id, Capabilities::CAP_EDIT_SETTINGS ) );
		$this->assertFalse( user_can( $user_id, Capabilities::CAP_MANAGE_STAFF ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Clearing a preset strips every pos_* cap from the user.
	 */
	public function test_set_pos_preset_null_strips_real_wp_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_ADMIN );
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_MANAGE_STAFF ) );

		Capabilities::set_pos_preset( $user_id, null );

		foreach ( Capabilities::all_pos_capabilities() as $cap ) {
			$this->assertFalse( user_can( $user_id, $cap ), "Cap {$cap} should be cleared." );
		}

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Switching preset from Manager to Cashier removes manager-only caps.
	 */
	public function test_set_pos_preset_downgrade_strips_higher_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_MANAGER );
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );

		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_CASHIER );

		// Cashier baseline retained.
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_PROCESS_SALES ) );
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_VIEW_ORDERS ) );
		// Manager-only caps gone.
		$this->assertFalse( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );
		$this->assertFalse( user_can( $user_id, Capabilities::CAP_CREATE_COUPONS ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Rejecting an invalid preset value does not mutate caps.
	 */
	public function test_set_pos_preset_invalid_value_preserves_existing_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_MANAGER );

		$this->assertFalse( Capabilities::set_pos_preset( $user_id, 'bogus' ) );

		// Caps from the prior valid preset must still be intact.
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );
		$this->assertSame( Capabilities::POS_PRESET_MANAGER, Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Assignable presets are exactly the three POS presets.
	 */
	public function test_assignable_pos_presets(): void {
		$this->assertSame(
			array(
				Capabilities::POS_PRESET_CASHIER,
				Capabilities::POS_PRESET_MANAGER,
				Capabilities::POS_PRESET_ADMIN,
			),
			Capabilities::assignable_pos_presets()
		);
	}

	/**
	 * @testdox Cashier capability map matches the M1 matrix.
	 */
	public function test_capabilities_for_cashier(): void {
		$caps = Capabilities::capabilities_for_preset( Capabilities::POS_PRESET_CASHIER );

		$this->assertArrayHasKey( Capabilities::CAP_PROCESS_SALES, $caps );
		$this->assertTrue( $caps[ Capabilities::CAP_PROCESS_SALES ] );
		$this->assertArrayHasKey( Capabilities::CAP_VIEW_ORDERS, $caps );
		$this->assertArrayHasKey( Capabilities::CAP_APPLY_COUPONS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_ISSUE_REFUNDS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_CREATE_COUPONS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_MANAGE_STAFF, $caps );
	}

	/**
	 * @testdox Manager capability map is a superset of the cashier matrix.
	 */
	public function test_capabilities_for_manager(): void {
		$caps = Capabilities::capabilities_for_preset( Capabilities::POS_PRESET_MANAGER );

		$this->assertArrayHasKey( Capabilities::CAP_PROCESS_SALES, $caps );
		$this->assertArrayHasKey( Capabilities::CAP_ISSUE_REFUNDS, $caps );
		$this->assertArrayHasKey( Capabilities::CAP_CREATE_COUPONS, $caps );
		$this->assertArrayHasKey( Capabilities::CAP_VIEW_SETTINGS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_EDIT_SETTINGS, $caps );
		$this->assertArrayNotHasKey( Capabilities::CAP_MANAGE_STAFF, $caps );
	}

	/**
	 * @testdox pos_admin capability map includes admin-only POS caps.
	 */
	public function test_capabilities_for_pos_admin(): void {
		$caps = Capabilities::capabilities_for_preset( Capabilities::POS_PRESET_ADMIN );

		$this->assertArrayHasKey( Capabilities::CAP_EDIT_SETTINGS, $caps );
		$this->assertArrayHasKey( Capabilities::CAP_MANAGE_STAFF, $caps );
		$this->assertArrayHasKey( Capabilities::CAP_EXIT_POS, $caps );
	}

	/**
	 * @testdox Cap constant string values use the pos_ prefix.
	 */
	public function test_cap_values_use_pos_prefix(): void {
		$this->assertSame( 'pos_process_sales', Capabilities::CAP_PROCESS_SALES );
		$this->assertSame( 'pos_view_orders', Capabilities::CAP_VIEW_ORDERS );
		$this->assertSame( 'pos_apply_coupons', Capabilities::CAP_APPLY_COUPONS );
		$this->assertSame( 'pos_create_coupons', Capabilities::CAP_CREATE_COUPONS );
		$this->assertSame( 'pos_issue_refunds', Capabilities::CAP_ISSUE_REFUNDS );
		$this->assertSame( 'pos_view_settings', Capabilities::CAP_VIEW_SETTINGS );
		$this->assertSame( 'pos_edit_settings', Capabilities::CAP_EDIT_SETTINGS );
		$this->assertSame( 'pos_manage_staff', Capabilities::CAP_MANAGE_STAFF );
		$this->assertSame( 'pos_exit', Capabilities::CAP_EXIT_POS );
	}

	/**
	 * @testdox Returns an empty capability map for unknown presets.
	 */
	public function test_capabilities_for_unknown_preset_is_empty(): void {
		$this->assertSame( array(), Capabilities::capabilities_for_preset( 'bogus' ) );
	}

	/**
	 * @testdox has_pos_access is false when the user holds no pos_* caps, even with stale preset meta.
	 */
	public function test_has_pos_access_false_when_no_pos_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		// Plant the preset meta directly, bypassing set_pos_preset() so no
		// pos_* caps are granted. Simulates tampered meta or a partial migration.
		update_user_meta( $user_id, Capabilities::POS_PRESET_META_KEY, Capabilities::POS_PRESET_CASHIER );

		$this->assertFalse(
			Capabilities::has_pos_access( $user_id ),
			'Preset meta without any pos_* cap must not grant POS access.'
		);

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox has_pos_access is true once the user holds any pos_* cap.
	 *
	 * Locks in the granular-caps semantics: a back-office refunds user who
	 * holds only `pos_issue_refunds` (no baseline `pos_process_sales`) still
	 * counts as POS staff for access checks.
	 */
	public function test_has_pos_access_true_with_a_single_non_baseline_cap(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_userdata( $user_id );
		$user->add_cap( Capabilities::CAP_ISSUE_REFUNDS );

		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox has_pos_access returns false for users that do not exist.
	 */
	public function test_has_pos_access_rejects_unknown_user(): void {
		$this->assertFalse( Capabilities::has_pos_access( 0 ) );
		$this->assertFalse( Capabilities::has_pos_access( 9999999 ) );
	}

	/**
	 * @testdox has_pos_access returns true once set_pos_preset has been applied.
	 */
	public function test_has_pos_access_true_after_set_pos_preset(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_CASHIER );

		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox has_pos_access survives WP_User::set_role() because access is cap-keyed.
	 *
	 * The wp-admin users.php "Change role to…" dropdown calls set_role(), which
	 * replaces all roles with the single chosen one. POS access must survive
	 * this — individual `pos_*` caps added via add_cap() are not cleared by
	 * set_role(), so the access gate stays stable. The pos_staff label is
	 * intentionally dropped (admin's explicit intent), but functional access
	 * is preserved.
	 */
	public function test_has_pos_access_survives_set_role_overwrite(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_MANAGER );

		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		$user = get_userdata( $user_id );
		$user->set_role( 'subscriber' );

		$this->assertTrue(
			Capabilities::has_pos_access( $user_id ),
			'POS access must survive a role overwrite — caps remain intact.'
		);

		wp_delete_user( $user_id );
	}
}
