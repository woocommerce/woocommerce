<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StoreActors;

use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use Automattic\WooCommerce\Tests\Internal\StoreActors\Concerns\EnablesActorsFeature;
use WC_Unit_Test_Case;

/**
 * @since 10.9.0
 * @group store-actors
 */
class ActorRepositoryTest extends WC_Unit_Test_Case {

	use EnablesActorsFeature;

	private ActorRepository $actors;
	private ActorAccessRepository $access;

	public function setUp(): void {
		parent::setUp();
		$this->install_actor_tables();
		$this->actors = wc_get_container()->get( ActorRepository::class );
		$this->access = wc_get_container()->get( ActorAccessRepository::class );

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DELETE FROM ' . $this->access->get_table_name() );
		$wpdb->query( 'DELETE FROM ' . $this->actors->get_table_name() );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function test_insert_generates_uuid_and_returns_id(): void {
		$id = $this->actors->insert(
			array(
				'display_name' => 'Alex Cashier',
			)
		);

		$this->assertGreaterThan( 0, $id );
		$row = $this->actors->get_by_id( $id );
		$this->assertNotNull( $row );
		$this->assertSame( 'Alex Cashier', $row['display_name'] );
		$this->assertNotEmpty( $row['actor_uuid'] );
		$this->assertSame( ActorRepository::STATUS_ACTIVE, $row['status'] );
		$this->assertNull( $row['wp_user_id'] );
		$this->assertNull( $row['date_deleted_gmt'] );
	}

	public function test_find_by_wp_user_id_returns_linked_actor_only(): void {
		$linked = $this->actors->insert(
			array(
				'display_name' => 'Manager',
				'wp_user_id'   => 7,
			)
		);
		$this->actors->insert( array( 'display_name' => 'POS-only cashier' ) );

		$found = $this->actors->find_by_wp_user_id( 7 );
		$this->assertNotNull( $found );
		$this->assertSame( $linked, (int) $found['actor_id'] );

		$this->assertNull( $this->actors->find_by_wp_user_id( 999 ) );
	}

	public function test_list_active_skips_soft_deleted(): void {
		$keep = $this->actors->insert( array( 'display_name' => 'Keep' ) );
		$drop = $this->actors->insert( array( 'display_name' => 'Drop' ) );
		$this->actors->soft_delete( $drop );

		$rows = $this->actors->list_active();
		$ids  = array_map( static fn( $r ) => (int) $r['actor_id'], $rows );

		$this->assertContains( $keep, $ids );
		$this->assertNotContains( $drop, $ids );
	}

	public function test_soft_delete_sets_status_inactive_and_date_deleted(): void {
		$id = $this->actors->insert( array( 'display_name' => 'Bye' ) );
		$this->assertTrue( $this->actors->soft_delete( $id ) );

		$row = $this->actors->get_by_id( $id );
		$this->assertNotNull( $row );
		$this->assertSame( ActorRepository::STATUS_INACTIVE, $row['status'] );
		$this->assertNotNull( $row['date_deleted_gmt'] );
	}

	public function test_detach_wp_user_nulls_link_and_inactivates(): void {
		$linked = $this->actors->insert(
			array(
				'display_name' => 'Manager',
				'wp_user_id'   => 42,
			)
		);
		$other = $this->actors->insert(
			array(
				'display_name' => 'Other',
				'wp_user_id'   => 99,
			)
		);

		$affected = $this->actors->detach_wp_user( 42 );
		$this->assertSame( 1, $affected );

		$row = $this->actors->get_by_id( $linked );
		$this->assertNull( $row['wp_user_id'] );
		$this->assertSame( ActorRepository::STATUS_INACTIVE, $row['status'] );

		$untouched = $this->actors->get_by_id( $other );
		$this->assertSame( 99, (int) $untouched['wp_user_id'] );
		$this->assertSame( ActorRepository::STATUS_ACTIVE, $untouched['status'] );
	}

	public function test_update_refreshes_updated_at_and_only_writes_allowed_fields(): void {
		$id      = $this->actors->insert( array( 'display_name' => 'Original' ) );
		$initial = $this->actors->get_by_id( $id );

		// Attempt to overwrite actor_uuid (not allowed) and display_name (allowed).
		$this->actors->update(
			$id,
			array(
				'display_name' => 'Renamed',
				'actor_uuid'   => 'evil-uuid',
			)
		);

		$updated = $this->actors->get_by_id( $id );
		$this->assertSame( 'Renamed', $updated['display_name'] );
		$this->assertSame( $initial['actor_uuid'], $updated['actor_uuid'] );
	}
}
