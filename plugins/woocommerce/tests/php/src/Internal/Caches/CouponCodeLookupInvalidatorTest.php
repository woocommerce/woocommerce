<?php
/**
 * CouponCodeLookupInvalidatorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\CouponCodeLookupInvalidator;
use WC_Helper_Coupon;
use WC_Unit_Test_Case;

/**
 * Tests for the CouponCodeLookupInvalidator class.
 */
class CouponCodeLookupInvalidatorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CouponCodeLookupInvalidator
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( CouponCodeLookupInvalidator::class );
	}

	/**
	 * @testdox Should invalidate the coupon code lookup cache when a coupon is unpublished outside the CRUD.
	 * @dataProvider unpublished_coupon_status_data
	 *
	 * @param string $new_status The status the coupon is updated to.
	 */
	public function test_unpublishing_a_coupon_outside_the_crud_busts_the_lookup_cache( string $new_status ): void {
		$code   = 'cache-bust-' . $new_status;
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$this->assertNotFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		wp_update_post(
			array(
				'ID'          => $coupon->get_id(),
				'post_status' => $new_status,
			)
		);

		$this->assertFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), "The coupon code lookup cache should be invalidated when the coupon transitions to {$new_status}" );
		$this->assertSame( 0, wc_get_coupon_id_by_code( $code ), "A {$new_status} coupon should not be resolvable by code" );

		$coupon->delete( true );
	}

	/**
	 * Data provider for unpublished coupon status tests.
	 *
	 * @return array
	 */
	public function unpublished_coupon_status_data() {
		return array(
			'draft'   => array( 'draft' ),
			'pending' => array( 'pending' ),
			'private' => array( 'private' ),
			'trash'   => array( 'trash' ),
		);
	}

	/**
	 * @testdox Should invalidate the coupon code lookup cache when a coupon is deleted outside the CRUD.
	 */
	public function test_deleting_a_coupon_outside_the_crud_busts_the_lookup_cache(): void {
		$code   = 'cache-bust-delete';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$this->assertNotFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		wp_delete_post( $coupon->get_id(), true );

		$this->assertFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be invalidated when the coupon post is deleted' );
		$this->assertSame( 0, wc_get_coupon_id_by_code( $code ), 'A deleted coupon should not be resolvable by code' );
	}

	/**
	 * @testdox Should keep the coupon code lookup cache of other coupons when a coupon is unpublished.
	 */
	public function test_unpublishing_a_coupon_keeps_the_lookup_cache_of_other_coupons(): void {
		$unpublished = WC_Helper_Coupon::create_coupon( 'cache-keep-unpublished' );
		$kept        = WC_Helper_Coupon::create_coupon( 'cache-keep-kept' );

		wc_get_coupon_id_by_code( 'cache-keep-unpublished' );
		wc_get_coupon_id_by_code( 'cache-keep-kept' );
		$kept_key = $this->sut->get_cache_key( 'cache-keep-kept' );
		$this->assertNotFalse( wp_cache_get( $kept_key, 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		wp_update_post(
			array(
				'ID'          => $unpublished->get_id(),
				'post_status' => 'draft',
			)
		);

		$this->assertSame( $kept_key, $this->sut->get_cache_key( 'cache-keep-kept' ), 'Unpublishing a coupon should not rotate the coupons group prefix' );
		$this->assertNotFalse( wp_cache_get( $kept_key, 'coupons' ), "Unpublishing a coupon should not flush another coupon's lookup entry" );
		$this->assertSame( 0, wc_get_coupon_id_by_code( 'cache-keep-unpublished' ), 'The unpublished coupon should not be resolvable by code' );
		$this->assertSame( $kept->get_id(), wc_get_coupon_id_by_code( 'cache-keep-kept' ), 'The other coupon should still resolve by code' );

		$unpublished->delete( true );
		$kept->delete( true );
	}

	/**
	 * Data provider for the statuses a deleted coupon can be in without ever having been cached.
	 *
	 * @return array
	 */
	public function never_cacheable_coupon_status_data() {
		return array(
			'draft'      => array( 'draft' ),
			'auto-draft' => array( 'auto-draft' ),
			'pending'    => array( 'pending' ),
			'private'    => array( 'private' ),
			'trash'      => array( 'trash' ),
		);
	}

	/**
	 * @testdox Should keep the coupon code lookup cache when a coupon in another status is deleted.
	 * @dataProvider never_cacheable_coupon_status_data
	 *
	 * @param string $status The status of the coupon being deleted.
	 */
	public function test_deleting_a_non_published_coupon_keeps_the_lookup_cache( string $status ): void {
		$code   = 'cache-keep-delete-' . $status;
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$cache_key = $this->sut->get_cache_key( $code );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		$doomed_id = wp_insert_post(
			array(
				'post_type'   => 'shop_coupon',
				'post_title'  => 'cache-keep-doomed-' . $status,
				'post_status' => $status,
			)
		);
		wp_delete_post( $doomed_id, true );

		$this->assertSame( $cache_key, $this->sut->get_cache_key( $code ), "Deleting a {$status} coupon should not rotate the coupons group prefix" );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), "Deleting a {$status} coupon should not flush an unrelated lookup entry" );

		$coupon->delete( true );
	}

	/**
	 * @testdox Should keep the coupon code lookup cache of other coupons when a new coupon is published.
	 */
	public function test_publishing_a_coupon_keeps_the_lookup_cache_of_other_coupons(): void {
		$code   = 'cache-keep-on-create';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$cache_key = $this->sut->get_cache_key( $code );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		$other = WC_Helper_Coupon::create_coupon( 'cache-keep-on-create-other' );

		$this->assertSame( $cache_key, $this->sut->get_cache_key( $code ), 'Publishing a coupon should not rotate the coupons group prefix' );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), "Publishing a coupon should not flush another coupon's lookup entry" );

		$other->delete( true );
		$coupon->delete( true );
	}

	/**
	 * @testdox Should invalidate the lookup cache of a code when a newer coupon is published under it.
	 */
	public function test_publishing_a_duplicate_code_busts_the_lookup_cache_of_that_code(): void {
		$code = 'cache-bust-duplicate';

		// Backdated so the "newest wins" ordering of get_ids_by_code() is deterministic.
		$older_id = wp_insert_post(
			array(
				'post_type'     => 'shop_coupon',
				'post_title'    => $code,
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - HOUR_IN_SECONDS ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			)
		);

		$this->assertSame( $older_id, wc_get_coupon_id_by_code( $code ), 'The older coupon should resolve while it is the only one' );

		$newer_id = wp_insert_post(
			array(
				'post_type'   => 'shop_coupon',
				'post_title'  => $code,
				'post_status' => 'publish',
			)
		);

		$this->assertSame( $newer_id, wc_get_coupon_id_by_code( $code ), 'The newest coupon published under a duplicated code should win the lookup' );

		wp_update_post(
			array(
				'ID'          => $newer_id,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( $older_id, wc_get_coupon_id_by_code( $code ), 'The older coupon should win the lookup again once the newer one is unpublished' );

		wp_delete_post( $newer_id, true );
		wp_delete_post( $older_id, true );
	}

	/**
	 * @testdox Invalidating the 'coupons' cache group should still reach the lookup entries.
	 */
	public function test_invalidating_the_coupons_cache_group_busts_the_lookup_cache(): void {
		$code   = 'cache-bust-group';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$cache_key = $this->sut->get_cache_key( $code );
		$this->assertNotFalse( wp_cache_get( $cache_key, $this->sut->get_cache_group() ), 'The coupon code lookup cache should be primed while the coupon is published' );

		\WC_Cache_Helper::invalidate_cache_group( 'coupons' );

		$this->assertNotSame( $cache_key, $this->sut->get_cache_key( $code ), "Rotating the 'coupons' group prefix should strand the lookup keys built under it" );

		$coupon->delete( true );
	}

	/**
	 * @testdox Invalidating every lookup entry at once should strand every previously built lookup key.
	 */
	public function test_invalidate_all_strands_previous_keys(): void {
		$raw_key_before       = $this->sut->get_cache_key( 'probe&test' );
		$sanitized_key_before = $this->sut->get_cache_key( 'probe&amp;test' );

		$this->sut->invalidate_all();

		$this->assertNotSame( $raw_key_before, $this->sut->get_cache_key( 'probe&test' ), 'The raw-alias lookup key should be unreachable afterwards' );
		$this->assertNotSame( $sanitized_key_before, $this->sut->get_cache_key( 'probe&amp;test' ), 'The sanitized-alias lookup key should be unreachable afterwards' );
	}

	/**
	 * @testdox Should keep the coupon code lookup cache when a published coupon is updated and stays published.
	 */
	public function test_updating_a_published_coupon_keeps_the_lookup_cache(): void {
		$code   = 'cache-keep-published';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$cache_key = $this->sut->get_cache_key( $code );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		wp_update_post(
			array(
				'ID'           => $coupon->get_id(),
				'post_excerpt' => 'Updated description',
			)
		);

		$this->assertSame( $cache_key, $this->sut->get_cache_key( $code ), 'The coupons group prefix should not rotate when the coupon stays published' );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), 'The coupon code lookup cache should be kept when the coupon stays published' );
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( $code ), 'A published coupon should remain resolvable by code' );

		$coupon->delete( true );
	}

	/**
	 * @testdox A primed lookup entry for a published coupon should be served without querying the database.
	 */
	public function test_a_fresh_lookup_entry_is_served_without_a_query(): void {
		global $wpdb;

		$code   = 'cache-hit';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		// Make sure the post cache is primed too, like it is after the coupon is read.
		get_post( $coupon->get_id() );

		$queries_before = $wpdb->num_queries;
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( $code ), 'The published coupon should resolve by code' );
		$this->assertSame( $queries_before, $wpdb->num_queries, 'A fresh lookup entry should be served from the object cache without a query' );

		$coupon->delete( true );
	}

	/**
	 * @testdox A custom coupon data store should keep serving its lookup entries instead of having them rejected on every read.
	 */
	public function test_a_custom_coupon_data_store_keeps_its_lookup_entries(): void {
		global $wpdb;

		$code   = 'custom-store-code';
		$coupon = WC_Helper_Coupon::create_coupon( $code );
		wp_update_post(
			array(
				'ID'          => $coupon->get_id(),
				'post_status' => 'private',
			)
		);

		// A data store that resolves more than the published coupons the read-time check describes.
		$store = new class() extends \WC_Coupon_Data_Store_CPT {
			/**
			 * Resolve private coupons alongside the published ones.
			 *
			 * @param string $code Coupon code.
			 * @return array Array of ids.
			 */
			public function get_ids_by_code( $code ) {
				global $wpdb;
				return $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM $wpdb->posts WHERE LOWER(post_title) = LOWER(%s) AND post_type = 'shop_coupon' AND post_status IN ( 'publish', 'private' ) ORDER BY post_date DESC",
						wc_sanitize_coupon_code( $code )
					)
				);
			}
		};

		$use_custom_store = function () use ( $store ) {
			return $store;
		};
		add_filter( 'woocommerce_coupon_data_store', $use_custom_store );

		// Prime the lookup entry and the post cache the read-time check would have consulted.
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( $code ), 'The custom data store should resolve the private coupon' );
		get_post( $coupon->get_id() );

		$queries_before = $wpdb->num_queries;
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( $code ), 'The custom data store should resolve the private coupon' );
		$this->assertSame( $queries_before, $wpdb->num_queries, "A custom data store's lookup entry should be served from the object cache, not thrown away and re-queried on every read" );

		remove_filter( 'woocommerce_coupon_data_store', $use_custom_store );

		$coupon->delete( true );
	}

	/**
	 * Data provider for the ways a cached coupon can stop being published.
	 *
	 * @return array
	 */
	public function coupon_removal_data() {
		return array(
			'unpublished' => array(
				function ( int $id ) {
					wp_update_post(
						array(
							'ID'          => $id,
							'post_status' => 'draft',
						)
					);
				},
			),
			'trashed'     => array(
				function ( int $id ) {
					wp_trash_post( $id );
				},
			),
			'deleted'     => array(
				function ( int $id ) {
					wp_delete_post( $id, true );
				},
			),
		);
	}

	/**
	 * @testdox A lookup entry written after the coupon stopped being published is rejected at read time, and unrelated coupon meta survives.
	 * @dataProvider coupon_removal_data
	 *
	 * @param callable $remove Removes the coupon from the published set.
	 */
	public function test_a_stale_lookup_entry_is_rejected_at_read_time( callable $remove ): void {
		$code   = 'race-code';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		// A second, unrelated published coupon whose meta cache must survive the invalidation.
		$unrelated_coupon = WC_Helper_Coupon::create_coupon( 'unrelated-code' );
		$unrelated_coupon->read_meta_data( true );
		$unrelated_meta_key = $unrelated_coupon->get_meta_cache_key();

		wc_get_coupon_id_by_code( $code );
		$lookup_key = $this->sut->get_cache_key( $code );
		$this->assertNotFalse( wp_cache_get( $lookup_key, 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );
		$this->assertNotFalse( wp_cache_get( $unrelated_meta_key, 'coupons' ), 'The unrelated coupon meta cache should be primed' );

		$remove( $coupon->get_id() );

		// Simulate an in-flight lookup completing and writing the stale id back after the invalidation.
		wp_cache_set( $lookup_key, array( $coupon->get_id() ), 'coupons' );

		$this->assertSame( 0, wc_get_coupon_id_by_code( $code ), 'A late write must not resurrect a coupon that is no longer published' );
		$this->assertFalse( wp_cache_get( $lookup_key, 'coupons' ), 'The rejected lookup entry should be removed from the object cache' );
		$this->assertNotFalse( wp_cache_get( $unrelated_meta_key, 'coupons' ), 'Invalidating a lookup entry must not flush unrelated coupon meta' );

		if ( get_post( $coupon->get_id() ) ) {
			$coupon->delete( true );
		}
		$unrelated_coupon->delete( true );
	}

	/**
	 * Data provider for the representations of a code that resolve the same coupon but are cached under another key.
	 *
	 * Each case gives the stored (sanitized) code and the alias a caller could pass to wc_get_coupon_id_by_code().
	 *
	 * @return array
	 */
	public function code_alias_data() {
		return array(
			'raw entity'          => array( 'alias&amp;test', 'alias&test' ),
			'trailing whitespace' => array( 'alias-space', 'alias-space ' ),
			'leading whitespace'  => array( 'alias-space', ' alias-space' ),
			'accent'              => array( 'alias-cafe', 'alias-café' ),
			'decomposed accent'   => array( 'alias-cafe', "alias-cafe\u{0301}" ),
		);
	}

	/**
	 * @testdox Unpublishing a coupon should invalidate every cached representation of its code, not just the key of its stored title.
	 * @dataProvider code_alias_data
	 *
	 * @param string $stored_code The code as it is stored in the coupon's post title.
	 * @param string $alias       Another representation of the code that resolves the same coupon.
	 */
	public function test_unpublishing_a_coupon_invalidates_every_cached_representation_of_its_code( string $stored_code, string $alias ): void {
		$coupon = WC_Helper_Coupon::create_coupon( $stored_code );

		$alias_key = $this->sut->get_cache_key( $alias );
		$this->assertNotSame( $this->sut->get_cache_key( $stored_code ), $alias_key, 'The alias should be cached under a different key than the stored code' );

		if ( $coupon->get_id() !== wc_get_coupon_id_by_code( $alias ) ) {
			$coupon->delete( true );
			$this->markTestSkipped( 'The database collation of this test environment does not resolve the alias, so there is nothing to invalidate.' );
		}
		$this->assertNotFalse( wp_cache_get( $alias_key, 'coupons' ), 'The alias lookup should be primed while the coupon is published' );

		wp_update_post(
			array(
				'ID'          => $coupon->get_id(),
				'post_status' => 'draft',
			)
		);

		$this->assertSame( 0, wc_get_coupon_id_by_code( $alias ), 'The alias must not resolve the coupon once it is unpublished' );
		$this->assertFalse( wp_cache_get( $alias_key, 'coupons' ), 'The stale alias lookup entry should be removed from the object cache' );

		$coupon->delete( true );
	}

	/**
	 * @testdox is_lookup_entry_stale() should only trust entries whose ids all belong to published coupons.
	 */
	public function test_is_lookup_entry_stale(): void {
		$published = WC_Helper_Coupon::create_coupon( 'stale-check-published' );
		$draft     = WC_Helper_Coupon::create_coupon( 'stale-check-draft' );
		wp_update_post(
			array(
				'ID'          => $draft->get_id(),
				'post_status' => 'draft',
			)
		);
		$deleted_id = WC_Helper_Coupon::create_coupon( 'stale-check-deleted' )->get_id();
		wp_delete_post( $deleted_id, true );
		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_title'  => 'stale-check-page',
				'post_status' => 'publish',
			)
		);

		$this->assertFalse( $this->sut->is_lookup_entry_stale( array( $published->get_id() ) ), 'An entry of a published coupon should be fresh' );
		$this->assertFalse( $this->sut->is_lookup_entry_stale( array( (string) $published->get_id() ) ), 'Ids stored as strings should be accepted' );
		$this->assertTrue( $this->sut->is_lookup_entry_stale( array( $draft->get_id() ) ), 'An entry of a draft coupon should be stale' );
		$this->assertTrue( $this->sut->is_lookup_entry_stale( array( $deleted_id ) ), 'An entry of a deleted coupon should be stale' );
		$this->assertTrue( $this->sut->is_lookup_entry_stale( array( $published->get_id(), $draft->get_id() ) ), 'An entry is stale as soon as one of its coupons is not published' );
		$this->assertTrue( $this->sut->is_lookup_entry_stale( array( $page_id ) ), 'An entry pointing at a post that is not a coupon should be stale' );
		$this->assertTrue( $this->sut->is_lookup_entry_stale( array() ), 'An empty entry should be stale' );
		$this->assertTrue( $this->sut->is_lookup_entry_stale( array( 0 ) ), 'An entry with an invalid id should be stale' );

		$published->delete( true );
		$draft->delete( true );
		wp_delete_post( $page_id, true );
	}

	/**
	 * @testdox Invalidate should do nothing for an empty code.
	 */
	public function test_invalidate_ignores_empty_codes(): void {
		wp_cache_set( $this->sut->get_cache_key( '' ), array( 123 ), 'coupons' );

		$this->sut->invalidate( '' );

		$this->assertSame( array( 123 ), wp_cache_get( $this->sut->get_cache_key( '' ), 'coupons' ), 'Invalidating an empty code should not touch the cache' );
	}
}
