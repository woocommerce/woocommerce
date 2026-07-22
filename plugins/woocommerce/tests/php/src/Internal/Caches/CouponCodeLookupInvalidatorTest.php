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
	 * @testdox Should delete the coupon code lookup cache when a coupon is unpublished outside the CRUD.
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

		$this->assertFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), "The coupon code lookup cache should be deleted when the coupon transitions to {$new_status}" );
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
	 * @testdox Should delete the coupon code lookup cache when a coupon is deleted outside the CRUD.
	 */
	public function test_deleting_a_coupon_outside_the_crud_busts_the_lookup_cache(): void {
		$code   = 'cache-bust-delete';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$this->assertNotFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		wp_delete_post( $coupon->get_id(), true );

		$this->assertFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be deleted when the coupon post is deleted' );
		$this->assertSame( 0, wc_get_coupon_id_by_code( $code ), 'A deleted coupon should not be resolvable by code' );
	}

	/**
	 * @testdox Should keep the coupon code lookup cache when a published coupon is updated and stays published.
	 */
	public function test_updating_a_published_coupon_keeps_the_lookup_cache(): void {
		$code   = 'cache-keep-published';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		wc_get_coupon_id_by_code( $code );
		$this->assertNotFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be primed while the coupon is published' );

		wp_update_post(
			array(
				'ID'           => $coupon->get_id(),
				'post_excerpt' => 'Updated description',
			)
		);

		$this->assertNotFalse( wp_cache_get( $this->sut->get_cache_key( $code ), 'coupons' ), 'The coupon code lookup cache should be kept when the coupon stays published' );
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( $code ), 'A published coupon should remain resolvable by code' );

		$coupon->delete( true );
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
