<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Unit tests for ShopperList.
 */
class ShopperListTests extends WC_Unit_Test_Case {
	private const SAVED_FOR_LATER_SLUG = 'saved-for-later';

	/**
	 * Map of shopper-list slug => feature option key.
	 */
	private const LIST_OPTIONS = array(
		'saved-for-later' => 'woocommerce_cart_save_for_later_enabled',
		'wishlist'        => 'woocommerce_product_wishlist_enabled',
	);

	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * @var ShopperListItem
	 */
	private $item;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_list( self::SAVED_FOR_LATER_SLUG );

		$this->user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$this->product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'List SUT Product',
				'regular_price' => 19.99,
			)
		);
		$this->item    = ShopperListItem::from_product( $this->product->get_id() );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( $this->product ) {
			$this->product->delete( true );
		}
		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->disable_list( $slug );
		}
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
		parent::tearDown();
	}

	/**
	 * Enable the feature backing the given shopper-list slug.
	 *
	 * @param string $slug List slug.
	 */
	private function enable_list( string $slug ): void {
		update_option( self::LIST_OPTIONS[ $slug ], 'yes' );
	}

	/**
	 * Disable the feature backing the given shopper-list slug.
	 *
	 * @param string $slug List slug.
	 */
	private function disable_list( string $slug ): void {
		update_option( self::LIST_OPTIONS[ $slug ], 'no' );
	}

	/**
	 * @testdox saved-for-later is in-memory only on first read and is persisted lazily on the first add_item()+save().
	 */
	public function test_save_for_later_persistence_is_lazy(): void {
		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertSame( self::SAVED_FOR_LATER_SLUG, $list->get_slug() );
		$this->assertSame( array(), $list->get_items() );

		$meta_key = ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG;
		$this->assertSame( '', Users::get_site_user_meta( $this->user_id, $meta_key ), 'Empty saved-for-later should not be persisted before the first add.' );

		$list->add_item( $this->item );
		$list->save();

		$this->assertIsArray( Users::get_site_user_meta( $this->user_id, $meta_key ), 'saved-for-later should be persisted after the first add+save.' );
	}

	/**
	 * @testdox get_by_slug should return false for any disabled or unknown list slug.
	 */
	public function test_load_returns_false_for_unsupported_list_slug(): void {
		// `wishlist` is a known slug but its feature is off in this test class.
		$this->assertFalse( ShopperList::get_by_slug( 'wishlist', $this->user_id ) );
		$this->assertFalse( ShopperList::get_by_slug( 'INVALID', $this->user_id ) );
		$this->assertFalse( ShopperList::get_by_slug( '', $this->user_id ) );
	}

	/**
	 * @testdox get_by_slug should return false when the feature is disabled, even when the list has persisted items.
	 */
	public function test_load_returns_false_when_feature_disabled_for_persisted_list(): void {
		// Persist a list while the feature is on.
		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );
		$list->add_item( $this->item );
		$list->save();
		$meta_key = ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG;
		$this->assertIsArray( Users::get_site_user_meta( $this->user_id, $meta_key ) );

		// Disable the feature; the persisted list must no longer be returned.
		$this->disable_list( self::SAVED_FOR_LATER_SLUG );
		$this->assertFalse( ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id ) );
	}

	/**
	 * @testdox get_by_slug should self-heal saved-for-later when the stored meta is corrupt.
	 */
	public function test_load_self_heals_corrupt_saved_for_later(): void {
		Users::update_site_user_meta(
			$this->user_id,
			ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG,
			'this-is-not-an-array'
		);

		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertSame( array(), $list->get_items(), 'Corrupt meta must yield an empty in-memory list.' );
	}

	/**
	 * @testdox get_by_slug should skip individual corrupt items but still return the rest of the list.
	 */
	public function test_load_skips_corrupt_items(): void {
		$good_item = $this->item->to_array();
		Users::update_site_user_meta(
			$this->user_id,
			ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG,
			array(
				'slug'             => self::SAVED_FOR_LATER_SLUG,
				'date_created_gmt' => '2026-04-01 00:00:00',
				'items'            => array(
					$good_item['key'] => $good_item,
					// Missing key + product_id.
					'broken-row-key'  => array( 'variation_id' => 0 ),
				),
			)
		);

		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertCount( 1, $list->get_items(), 'Bad rows should be skipped, the rest kept.' );
		$this->assertNotNull( $list->find_item( $good_item['key'] ) );
	}

	/**
	 * @testdox add_item/remove_item round-trip through save() and reload, merge quantities for the same key, and report unknown keys.
	 */
	public function test_list_item_crud(): void {
		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$list->add_item( $this->item );
		$list->add_item( $this->item );
		$list->save();

		$reloaded = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );
		$this->assertCount( 1, $reloaded->get_items(), 'Adding the same item twice must keep a single row.' );

		$merged = $reloaded->find_item( $this->item->get_key() );
		$this->assertNotNull( $merged );
		$this->assertSame( 2, $merged->get_quantity(), 'Quantities must be summed when the same product+variation is added again.' );

		$this->assertFalse( $reloaded->remove_item( 'nonexistent-key' ), 'remove_item should return false for unknown keys.' );
		$this->assertTrue( $reloaded->remove_item( $this->item->get_key() ) );
		$reloaded->save();

		$this->assertSame( array(), ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id )->get_items() );
	}
}
