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

		$meta_key = ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG;
		$this->assertSame( '', Users::get_site_user_meta( $this->user_id, $meta_key ), 'Empty saved-for-later should not be persisted before the first add.' );

		$list->add_item( $this->item );
		$list->save();

		$this->assertIsArray( Users::get_site_user_meta( $this->user_id, $meta_key ), 'saved-for-later should be persisted after the first add+save.' );
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
	 * @testdox get_by_slug should throw when the stored data is corrupt.
	 */
	public function test_load_throws_on_corrupt_data(): void {
		Users::update_site_user_meta(
			$this->user_id,
			ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG,
			'this-is-not-an-array'
		);

		$this->expectException( \RuntimeException::class );
		ShopperList::get_by_slug( self::SAVED_FOR_LATER_SLUG, $this->user_id );
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
