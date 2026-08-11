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

		$this->assertSame( $cache_key, $this->sut->get_cache_key( $code ), "Deleting a {$status} coupon should not rotate the lookup namespace" );
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

		$this->assertSame( $cache_key, $this->sut->get_cache_key( $code ), 'Publishing a coupon should not rotate the lookup namespace' );
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

		wp_delete_post( $newer_id, true );
		wp_delete_post( $older_id, true );
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

		$this->assertSame( $cache_key, $this->sut->get_cache_key( $code ), 'The lookup namespace should not rotate when the coupon stays published' );
		$this->assertNotFalse( wp_cache_get( $cache_key, 'coupons' ), 'The coupon code lookup cache should be kept when the coupon stays published' );
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( $code ), 'A published coupon should remain resolvable by code' );

		$coupon->delete( true );
	}

	/**
	 * @testdox A late lookup write under the old namespace is stranded, and unrelated coupon meta survives.
	 */
	public function test_lookup_namespace_rotation_strands_stale_writes_and_preserves_unrelated_coupon_meta(): void {
		$code   = 'race-code';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		// A second, unrelated published coupon whose meta cache must survive the invalidation.
		$unrelated_coupon = WC_Helper_Coupon::create_coupon( 'unrelated-code' );
		$unrelated_coupon->read_meta_data( true );
		$unrelated_meta_key = $unrelated_coupon->get_meta_cache_key();

		wc_get_coupon_id_by_code( $code );
		$old_lookup_key = $this->sut->get_cache_key( $code );
		$this->assertNotFalse( wp_cache_get( $old_lookup_key, 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );
		$this->assertNotFalse( wp_cache_get( $unrelated_meta_key, 'coupons' ), 'The unrelated coupon meta cache should be primed' );

		// Unpublishing outside the CRUD rotates the lookup namespace.
		wp_update_post(
			array(
				'ID'          => $coupon->get_id(),
				'post_status' => 'draft',
			)
		);

		// Simulate an in-flight lookup completing and writing the stale id back under the old namespace.
		wp_cache_set( $old_lookup_key, array( $coupon->get_id() ), 'coupons' );

		$this->assertNotSame( $old_lookup_key, $this->sut->get_cache_key( $code ), 'The lookup key prefix should rotate after the coupon is unpublished' );
		$this->assertSame( 0, wc_get_coupon_id_by_code( $code ), 'A late write under the old lookup namespace must not resurrect an unpublished coupon' );
		$this->assertNotFalse( wp_cache_get( $unrelated_meta_key, 'coupons' ), 'Rotating the lookup namespace must not flush unrelated coupon meta' );

		$coupon->delete( true );
		$unrelated_coupon->delete( true );
	}

	/**
	 * @testdox Rotating the lookup namespace invalidates every representation of a code, not just one physical key.
	 */
	public function test_lookup_namespace_rotation_covers_all_code_representations(): void {
		// The public lookup function hashes raw caller input, while invalidation from a post title
		// sees the sanitized form, so the same logical code can be primed under different keys.
		$raw_key_before       = $this->sut->get_cache_key( 'probe&test' );
		$sanitized_key_before = $this->sut->get_cache_key( 'probe&amp;test' );
		$this->assertNotSame( $raw_key_before, $sanitized_key_before, 'Different code representations should hash to different keys' );

		$this->sut->invalidate_lookup_namespace();

		$this->assertNotSame( $raw_key_before, $this->sut->get_cache_key( 'probe&test' ), 'The raw-alias lookup key should be unreachable after rotation' );
		$this->assertNotSame( $sanitized_key_before, $this->sut->get_cache_key( 'probe&amp;test' ), 'The sanitized-alias lookup key should be unreachable after rotation' );
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
