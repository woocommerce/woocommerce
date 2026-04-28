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
	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var \WC_Product
	 */
	private $product;

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
	 * Build a ShopperListItem for the test product.
	 */
	private function make_item(): ShopperListItem {
		return ShopperListItem::from_product( $this->product->get_id() );
	}

	/**
	 * @testdox get_by_slug should auto-create saved-for-later on first read.
	 */
	public function test_load_auto_creates_save_for_later(): void {
		$list = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertSame( 'saved-for-later', $list->get_slug() );
		$this->assertSame( array(), $list->get_items() );

		$persisted = Users::get_site_user_meta( $this->user_id, ShopperList::META_KEY_PREFIX . 'saved-for-later' );
		$this->assertIsArray( $persisted, 'Auto-created list should be persisted to user meta.' );
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
			ShopperList::META_KEY_PREFIX . 'saved-for-later',
			'this-is-not-an-array'
		);

		$this->expectException( \RuntimeException::class );
		ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
	}

	/**
	 * @testdox get_by_slug should default to the current user when user_id is omitted.
	 */
	public function test_load_defaults_to_current_user(): void {
		wp_set_current_user( $this->user_id );

		$list = ShopperList::get_by_slug( 'saved-for-later' );

		$this->assertInstanceOf( ShopperList::class, $list );
		$this->assertSame( 'saved-for-later', $list->get_slug() );
	}

	/**
	 * @testdox add_item should persist after save() and round-trip through reload.
	 */
	public function test_add_item_persists_through_save_and_reload(): void {
		$list = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
		$item = $this->make_item();

		$list->add_item( $item );
		$list->save();

		$reloaded = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
		$this->assertCount( 1, $reloaded->get_items() );
		$this->assertNotNull( $reloaded->find_item( $item->key() ) );
	}

	/**
	 * @testdox add_item should be idempotent — adding the same item twice keeps a single row.
	 */
	public function test_add_item_is_idempotent_for_same_product(): void {
		$list = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );

		$list->add_item( $this->make_item() );
		$list->add_item( $this->make_item() );
		$list->save();

		$this->assertCount( 1, ShopperList::get_by_slug( 'saved-for-later', $this->user_id )->get_items() );
	}

	/**
	 * @testdox remove_item should remove an existing item and return true.
	 */
	public function test_remove_item_removes_existing(): void {
		$list = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
		$item = $this->make_item();

		$list->add_item( $item );
		$list->save();

		$reloaded = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
		$this->assertTrue( $reloaded->remove_item( $item->key() ) );
		$reloaded->save();

		$this->assertSame( array(), ShopperList::get_by_slug( 'saved-for-later', $this->user_id )->get_items() );
	}

	/**
	 * @testdox remove_item should return false for an unknown key.
	 */
	public function test_remove_item_returns_false_for_unknown_key(): void {
		$list = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );

		$this->assertFalse( $list->remove_item( 'nonexistent-key' ) );
	}

	/**
	 * @testdox One user's items should not be visible to another user.
	 */
	public function test_lists_are_isolated_between_users(): void {
		$user_b = $this->factory->user->create( array( 'role' => 'customer' ) );

		$list_a = ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
		$list_a->add_item( $this->make_item() );
		$list_a->save();

		$list_b = ShopperList::get_by_slug( 'saved-for-later', $user_b );
		$this->assertSame( array(), $list_b->get_items() );
		$this->assertCount( 1, ShopperList::get_by_slug( 'saved-for-later', $this->user_id )->get_items() );
	}
}
