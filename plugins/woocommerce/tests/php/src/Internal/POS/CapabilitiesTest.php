<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\POSPreset;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Unit tests for the POS access model (capability primitives).
 *
 * Covers the cap catalog, has_pos_access() (the single authorization signal), and
 * the preset layer (POSPreset, capabilities_for_preset / get_pos_preset /
 * set_pos_preset / preset_label).
 */
class CapabilitiesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Every POS capability is woocommerce_pos_-prefixed, keeping it isolated from core caps.
	 */
	public function test_all_caps_are_woocommerce_pos_prefixed(): void {
		foreach ( Capabilities::all_pos_capabilities() as $cap ) {
			$this->assertStringStartsWith( 'woocommerce_pos_', $cap, "POS cap '{$cap}' must be woocommerce_pos_-prefixed." );
		}
	}

	/**
	 * @testdox all_pos_capabilities lists exactly the nine known woocommerce_pos_* caps.
	 *
	 * Asserts the full set (order-insensitive) so the test fails if any cap is
	 * added, removed, or swapped — not just when the count changes.
	 */
	public function test_all_pos_capabilities_lists_every_cap(): void {
		$this->assertEqualsCanonicalizing(
			array(
				Capabilities::CAP_PROCESS_SALES,
				Capabilities::CAP_VIEW_ORDERS,
				Capabilities::CAP_APPLY_COUPONS,
				Capabilities::CAP_CREATE_COUPONS,
				Capabilities::CAP_ISSUE_REFUNDS,
				Capabilities::CAP_VIEW_SETTINGS,
				Capabilities::CAP_EDIT_SETTINGS,
				Capabilities::CAP_MANAGE_STAFF,
				Capabilities::CAP_EXIT_POS,
			),
			Capabilities::all_pos_capabilities()
		);
	}

	/**
	 * @testdox Default staff role is the stock subscriber role (no dedicated POS role yet).
	 */
	public function test_default_staff_role_is_subscriber(): void {
		$this->assertSame( 'subscriber', Capabilities::DEFAULT_STAFF_ROLE );
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function provider_privileged_roles(): array {
		return array(
			'administrator' => array( 'administrator' ),
			'shop manager'  => array( 'shop_manager' ),
		);
	}

	/**
	 * @testdox A fresh privileged WP role has no implicit POS access.
	 *
	 * POS access requires an explicitly granted woocommerce_pos_* cap; holding a privileged WP
	 * role (administrator, shop_manager) grants none on its own.
	 *
	 * @dataProvider provider_privileged_roles
	 *
	 * @param string $role WP role to create the user with.
	 */
	public function test_role_has_no_implicit_access( string $role ): void {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );

		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox A multisite super admin has no implicit POS access until granted a cap.
	 *
	 * user_can() grants a super admin every capability on multisite, but POS access
	 * is keyed on stored woocommerce_pos_* caps (WP_User::$allcaps), which omits the
	 * runtime super-admin grant. A super admin therefore needs an explicit cap like
	 * anyone else. Skips off multisite, where there is no super-admin concept.
	 */
	public function test_super_admin_has_no_implicit_access_on_multisite(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Super-admin access only applies on multisite.' );
		}

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		grant_super_admin( $user_id );

		$this->assertTrue(
			user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ),
			'Sanity: a super admin passes user_can() for any cap.'
		);
		$this->assertFalse(
			Capabilities::has_pos_access( $user_id ),
			'A super admin must not implicitly count as POS staff.'
		);

		$user = get_userdata( $user_id );
		$user->add_cap( Capabilities::CAP_ISSUE_REFUNDS );
		$this->assertTrue(
			Capabilities::has_pos_access( $user_id ),
			'A super admin gains POS access once granted an explicit woocommerce_pos_* cap.'
		);

		revoke_super_admin( $user_id );
		wp_delete_user( $user_id );
	}

	/**
	 * @testdox has_pos_access is true once the user holds any single woocommerce_pos_* cap.
	 *
	 * Locks in the granular-caps semantics: a back-office refunds user holding
	 * only `woocommerce_pos_issue_refunds` (no baseline `woocommerce_pos_process_sales`) still counts as
	 * POS staff.
	 */
	public function test_has_pos_access_true_with_a_single_cap(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_userdata( $user_id );
		$user->add_cap( Capabilities::CAP_ISSUE_REFUNDS );

		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox has_pos_access is false when the user holds no woocommerce_pos_* caps.
	 */
	public function test_has_pos_access_false_without_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

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
	 * @testdox has_pos_access survives a role overwrite because access is cap-keyed.
	 *
	 * The wp-admin users.php "Change role to…" dropdown calls set_role(), which
	 * replaces all roles. POS access must survive — individual woocommerce_pos_* caps added
	 * via add_cap() are not cleared by set_role().
	 */
	public function test_has_pos_access_survives_set_role_overwrite(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$user    = get_userdata( $user_id );
		$user->add_cap( Capabilities::CAP_ISSUE_REFUNDS );
		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		$user->set_role( 'subscriber' );

		$this->assertTrue(
			Capabilities::has_pos_access( $user_id ),
			'POS access must survive a role overwrite — caps remain intact.'
		);

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox POSPreset::get_all returns the three presets in ascending order.
	 */
	public function test_pos_preset_get_all(): void {
		$this->assertSame(
			array(
				POSPreset::CASHIER,
				POSPreset::MANAGER,
				POSPreset::ADMIN,
			),
			POSPreset::get_all()
		);
	}

	/**
	 * @return array<string, array<mixed>>
	 */
	public function provider_preset_caps(): array {
		$cashier = array( Capabilities::CAP_PROCESS_SALES, Capabilities::CAP_VIEW_ORDERS, Capabilities::CAP_APPLY_COUPONS );
		$manager = array_merge( $cashier, array( Capabilities::CAP_CREATE_COUPONS, Capabilities::CAP_ISSUE_REFUNDS, Capabilities::CAP_VIEW_SETTINGS ) );
		$admin   = array_merge( $manager, array( Capabilities::CAP_EDIT_SETTINGS, Capabilities::CAP_MANAGE_STAFF, Capabilities::CAP_EXIT_POS ) );

		return array(
			'cashier' => array( POSPreset::CASHIER, $cashier ),
			'manager' => array( POSPreset::MANAGER, $manager ),
			'admin'   => array( POSPreset::ADMIN, $admin ),
		);
	}

	/**
	 * @testdox capabilities_for_preset returns exactly the documented cap bundle per preset.
	 *
	 * Asserts the full expected set (including caps inherited from lower tiers), so a
	 * regression that drops a base cap or adds an unexpected one fails the test.
	 *
	 * @dataProvider provider_preset_caps
	 *
	 * @param string   $preset   Preset slug.
	 * @param string[] $expected The exact caps the preset grants.
	 */
	public function test_capabilities_for_preset( string $preset, array $expected ): void {
		$this->assertEqualsCanonicalizing(
			$expected,
			array_keys( Capabilities::capabilities_for_preset( $preset ) )
		);
	}

	/**
	 * @testdox capabilities_for_preset returns an empty bundle for an unknown preset.
	 */
	public function test_capabilities_for_unknown_preset_is_empty(): void {
		$this->assertSame( array(), Capabilities::capabilities_for_preset( 'bogus' ) );
	}

	/**
	 * @testdox preset_label returns a non-empty label per preset and empty for unknown.
	 */
	public function test_preset_label(): void {
		$this->assertNotSame( '', Capabilities::preset_label( POSPreset::CASHIER ) );
		$this->assertNotSame( '', Capabilities::preset_label( POSPreset::MANAGER ) );
		$this->assertNotSame( '', Capabilities::preset_label( POSPreset::ADMIN ) );
		$this->assertSame( '', Capabilities::preset_label( 'bogus' ) );
	}

	/**
	 * @testdox pos_staff_user_query_args selects POS cap-holders and excludes others.
	 *
	 * Verifies the query matches the access definition (any woocommerce_pos_* cap), not the
	 * preset meta: a cap-holder is returned and a fresh administrator is not.
	 */
	public function test_pos_staff_user_query_args_selects_cap_holders(): void {
		$staff    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$outsider = self::factory()->user->create( array( 'role' => 'administrator' ) );
		get_userdata( $staff )->add_cap( Capabilities::CAP_ISSUE_REFUNDS );

		$results = ( new \WP_User_Query( Capabilities::pos_staff_user_query_args() ) )->get_results();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertContains( $staff, $ids, 'A user holding a woocommerce_pos_* cap should be selected.' );
		$this->assertNotContains( $outsider, $ids, 'An administrator without any woocommerce_pos_* cap should not be selected.' );

		wp_delete_user( $staff );
		wp_delete_user( $outsider );
	}

	/**
	 * @testdox get_pos_preset returns the assigned preset.
	 */
	public function test_get_pos_preset_returns_assigned_preset(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, POSPreset::MANAGER );

		$this->assertSame( POSPreset::MANAGER, Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox get_pos_preset returns null when unset or when the meta is not an assignable preset.
	 */
	public function test_get_pos_preset_null_when_unset_or_invalid(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );

		Users::update_site_user_meta( $user_id, Capabilities::POS_PRESET_META_KEY, 'bogus' );
		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox set_pos_preset grants the preset's woocommerce_pos_* caps as real WP capabilities.
	 */
	public function test_set_pos_preset_grants_preset_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertTrue( Capabilities::set_pos_preset( $user_id, POSPreset::MANAGER ) );

		$this->assertTrue( user_can( $user_id, Capabilities::CAP_PROCESS_SALES ) );
		$this->assertTrue( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );
		$this->assertFalse( user_can( $user_id, Capabilities::CAP_MANAGE_STAFF ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Switching a preset from Manager to Cashier strips the manager-only caps.
	 */
	public function test_set_pos_preset_downgrade_strips_higher_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, POSPreset::MANAGER );

		Capabilities::set_pos_preset( $user_id, POSPreset::CASHIER );

		$this->assertTrue( user_can( $user_id, Capabilities::CAP_PROCESS_SALES ) );
		$this->assertFalse( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Clearing a preset strips every woocommerce_pos_* cap and deletes the preset meta.
	 */
	public function test_set_pos_preset_clear_strips_caps_and_meta(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, POSPreset::ADMIN );

		$this->assertTrue( Capabilities::set_pos_preset( $user_id, null ) );

		foreach ( Capabilities::all_pos_capabilities() as $cap ) {
			$this->assertFalse( user_can( $user_id, $cap ), "Cap {$cap} should be cleared." );
		}
		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Clearing a preset leaves the user's non-POS capabilities untouched.
	 *
	 * The strip loop iterates only all_pos_capabilities(), so a directly-granted cap
	 * outside the woocommerce_pos_* set must survive a clear — guarding against a regression to a
	 * blanket reset.
	 */
	public function test_set_pos_preset_clear_leaves_non_pos_caps_untouched(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_userdata( $user_id );
		$user->add_cap( 'edit_posts' );
		Capabilities::set_pos_preset( $user_id, POSPreset::MANAGER );

		Capabilities::set_pos_preset( $user_id, null );

		$this->assertFalse( Capabilities::has_pos_access( $user_id ), 'POS caps should be cleared.' );
		$this->assertTrue( user_can( $user_id, 'edit_posts' ), 'A non-POS cap held directly must survive a preset clear.' );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox set_pos_preset rejects an invalid preset without mutating existing caps.
	 */
	public function test_set_pos_preset_rejects_invalid_preset(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $user_id, POSPreset::MANAGER );

		$this->assertFalse( Capabilities::set_pos_preset( $user_id, 'bogus' ) );

		$this->assertTrue( user_can( $user_id, Capabilities::CAP_ISSUE_REFUNDS ) );
		$this->assertSame( POSPreset::MANAGER, Capabilities::get_pos_preset( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox set_pos_preset returns false for a non-existent user.
	 */
	public function test_set_pos_preset_rejects_unknown_user(): void {
		$this->assertFalse( Capabilities::set_pos_preset( 0, POSPreset::CASHIER ) );
		$this->assertFalse( Capabilities::set_pos_preset( 9999999, POSPreset::CASHIER ) );
	}

	/**
	 * @testdox Granting or clearing a preset leaves the user's WP role untouched.
	 */
	public function test_set_pos_preset_leaves_role_untouched(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );

		Capabilities::set_pos_preset( $user_id, POSPreset::MANAGER );
		$this->assertSame( array( 'shop_manager' ), get_userdata( $user_id )->roles );

		Capabilities::set_pos_preset( $user_id, null );
		$this->assertSame( array( 'shop_manager' ), get_userdata( $user_id )->roles );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox has_pos_access tracks set_pos_preset: true after assign, false after clear.
	 */
	public function test_has_pos_access_tracks_set_pos_preset(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		Capabilities::set_pos_preset( $user_id, POSPreset::CASHIER );
		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );

		Capabilities::set_pos_preset( $user_id, null );
		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox Preset meta without any woocommerce_pos_* cap does not grant POS access.
	 *
	 * The caps are the authorization signal, not the meta: a planted or partially
	 * migrated preset meta value must not by itself confer access.
	 */
	public function test_has_pos_access_false_with_stale_preset_meta_only(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		Users::update_site_user_meta( $user_id, Capabilities::POS_PRESET_META_KEY, POSPreset::CASHIER );

		$this->assertFalse( Capabilities::has_pos_access( $user_id ) );

		wp_delete_user( $user_id );
	}
}
