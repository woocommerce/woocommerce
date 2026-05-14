<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListFullException;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use WC_Unit_Test_Case;

/**
 * Unit tests for ShopperList.
 */
class ShopperListTests extends WC_Unit_Test_Case {
	private const SAVED_FOR_LATER_SLUG = 'saved-for-later';

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
		parent::tearDown();
	}

	/**
	 * @testdox saved-for-later is in-memory only on first read and is persisted lazily on the first add_item()+save().
	 */
	public function test_save_for_later_persistence_is_lazy(): void {
		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertSame( self::SAVED_FOR_LATER_SLUG, $list->get_slug() );
		$this->assertSame( array(), $list->get_items() );

		$option_name = $this->option_name( $this->user_id, self::SAVED_FOR_LATER_SLUG );
		$this->assertFalse( get_option( $option_name, false ), 'Empty saved-for-later should not be persisted before the first add.' );

		$list->add_item( $this->item );
		$list->save();

		$this->assertIsArray( get_option( $option_name, false ), 'saved-for-later should be persisted after the first add+save.' );
	}

	/**
	 * @testdox get_by_slug should return false for any list slug other than saved-for-later.
	 */
	public function test_load_returns_false_for_unsupported_list_slug(): void {
		$this->assertFalse( ShopperList::get_by_slug( 'wishlist', $this->user_id ) );
		$this->assertFalse( ShopperList::get_by_slug( 'INVALID', $this->user_id ) );
		$this->assertFalse( ShopperList::get_by_slug( '', $this->user_id ) );
	}

	/**
	 * @testdox get_by_slug should self-heal saved-for-later when the stored option is corrupt.
	 */
	public function test_load_self_heals_corrupt_saved_for_later(): void {
		update_option(
			$this->option_name( $this->user_id, self::SAVED_FOR_LATER_SLUG ),
			'this-is-not-an-array',
			false
		);

		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertSame( array(), $list->get_items(), 'Corrupt storage must yield an empty in-memory list.' );
	}

	/**
	 * @testdox get_by_slug should skip individual corrupt items but still return the rest of the list.
	 */
	public function test_load_skips_corrupt_items(): void {
		$good_item = $this->item->to_array();
		update_option(
			$this->option_name( $this->user_id, self::SAVED_FOR_LATER_SLUG ),
			array(
				'slug'             => self::SAVED_FOR_LATER_SLUG,
				'date_created_gmt' => '2026-04-01 00:00:00',
				'items'            => array(
					$good_item['key'] => $good_item,
					// Missing key + product_id.
					'broken-row-key'  => array( 'variation_id' => 0 ),
				),
			),
			false
		);

		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertCount( 1, $list->get_items(), 'Bad rows should be skipped, the rest kept.' );
		$this->assertNotNull( $list->find_item( $good_item['key'] ) );
	}

	/**
	 * @testdox delete_all_for_user wipes the stored option for every known slug.
	 */
	public function test_delete_all_for_user_wipes_storage(): void {
		$option_name = $this->option_name( $this->user_id, self::SAVED_FOR_LATER_SLUG );
		update_option(
			$option_name,
			array(
				'slug'  => self::SAVED_FOR_LATER_SLUG,
				'items' => array(),
			),
			false
		);

		ShopperList::delete_all_for_user( $this->user_id );

		$this->assertFalse( get_option( $option_name, false ), 'delete_all_for_user must remove the wp_options row.' );
	}

	/**
	 * Storage option name for a (user, slug) pair.
	 *
	 * @param int    $user_id Owning user ID.
	 * @param string $slug    List slug.
	 */
	private function option_name( int $user_id, string $slug ): string {
		return ShopperList::OPTION_PREFIX . $user_id . '_' . $slug;
	}

	/**
	 * @testdox add_item should throw when the list is at capacity and the incoming item is a new key.
	 */
	public function test_add_item_throws_when_list_at_capacity_for_new_key(): void {
		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		for ( $i = 0; $i < ShopperList::MAX_ITEMS; $i++ ) {
			$list->add_item( $this->synthetic_item( 'item-' . $i, $i + 1 ) );
		}
		$this->assertCount( ShopperList::MAX_ITEMS, $list->get_items() );

		$this->expectException( ShopperListFullException::class );
		$list->add_item( $this->synthetic_item( 'item-overflow', 9999 ) );
	}

	/**
	 * @testdox add_item should still merge quantities at capacity when the incoming item matches an existing key.
	 */
	public function test_add_item_allows_merging_when_at_capacity(): void {
		$list = ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );

		for ( $i = 0; $i < ShopperList::MAX_ITEMS; $i++ ) {
			$list->add_item( $this->synthetic_item( 'item-' . $i, $i + 1 ) );
		}

		$list->add_item( $this->synthetic_item( 'item-0', 1, 3 ) );

		$this->assertCount( ShopperList::MAX_ITEMS, $list->get_items(), 'A duplicate add at capacity must not grow the list.' );
		$this->assertSame( 4, $list->find_item( 'item-0' )->get_quantity(), 'Merged quantity should equal the sum of the original and the new add.' );
	}

	/**
	 * ShopperListItem with synthetic key/product_id, bypassing real product fixtures.
	 *
	 * @param string $key        Storage key.
	 * @param int    $product_id Product ID.
	 * @param int    $quantity   Quantity.
	 */
	private function synthetic_item( string $key, int $product_id, int $quantity = 1 ): ShopperListItem {
		return ShopperListItem::from_array(
			array(
				'key'                   => $key,
				'product_id'            => $product_id,
				'variation_id'          => 0,
				'variation'             => array(),
				'quantity'              => $quantity,
				'date_added_gmt'        => '2026-04-01 00:00:00',
				'product_title_at_save' => 'Synthetic',
			)
		);
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
