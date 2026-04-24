<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\SavedListsStore;
use WC_Unit_Test_Case;

/**
 * Tests for the SavedListsStore utility class.
 */
class SavedListsStoreTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SavedListsStore
	 */
	private $sut;

	/**
	 * A test user's ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut     = new SavedListsStore();
		$this->user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should store and retrieve an added item on the save-for-later list.
	 */
	public function test_add_item_stores_and_retrieves_an_item(): void {
		$stored = $this->sut->add_item(
			SavedListsStore::SAVE_FOR_LATER,
			array(
				'product_id'   => 42,
				'variation_id' => 0,
				'quantity'     => 2,
				'variation'    => array(),
			)
		);

		$this->assertNotEmpty( $stored['key'], 'Stored item should have a generated key.' );
		$this->assertSame( 42, $stored['product_id'] );
		$this->assertSame( 2, $stored['quantity'] );

		$items = $this->sut->get_items( SavedListsStore::SAVE_FOR_LATER );
		$this->assertArrayHasKey( $stored['key'], $items, 'get_items should contain the key we just added.' );
	}

	/**
	 * @testdox Should merge quantity when adding an equivalent item twice.
	 */
	public function test_add_item_merges_quantity_when_item_already_exists(): void {
		$payload = array(
			'product_id'   => 42,
			'variation_id' => 99,
			'quantity'     => 1,
			'variation'    => array( 'attribute_pa_size' => 'small' ),
		);

		$first  = $this->sut->add_item( SavedListsStore::SAVE_FOR_LATER, $payload );
		$second = $this->sut->add_item( SavedListsStore::SAVE_FOR_LATER, array_merge( $payload, array( 'quantity' => 3 ) ) );

		$this->assertSame( $first['key'], $second['key'], 'Equivalent items should share a key.' );
		$this->assertSame( 4, $second['quantity'], 'Quantities should sum when the item already exists.' );
		$this->assertCount( 1, $this->sut->get_items( SavedListsStore::SAVE_FOR_LATER ) );
	}

	/**
	 * @testdox Should store items with different identity independently.
	 */
	public function test_add_different_items_coexist_independently(): void {
		$red = $this->sut->add_item(
			SavedListsStore::SAVE_FOR_LATER,
			array(
				'product_id'   => 42,
				'variation_id' => 10,
				'quantity'     => 1,
				'variation'    => array( 'attribute_pa_color' => 'red' ),
			)
		);

		$blue = $this->sut->add_item(
			SavedListsStore::SAVE_FOR_LATER,
			array(
				'product_id'   => 42,
				'variation_id' => 11,
				'quantity'     => 1,
				'variation'    => array( 'attribute_pa_color' => 'blue' ),
			)
		);

		$this->assertNotSame( $red['key'], $blue['key'] );
		$this->assertCount( 2, $this->sut->get_items( SavedListsStore::SAVE_FOR_LATER ) );
	}

	/**
	 * @testdox Should remove only the targeted item.
	 */
	public function test_remove_item_removes_only_that_item(): void {
		$keep = $this->sut->add_item(
			SavedListsStore::SAVE_FOR_LATER,
			array(
				'product_id' => 1,
				'quantity'   => 1,
			)
		);
		$drop = $this->sut->add_item(
			SavedListsStore::SAVE_FOR_LATER,
			array(
				'product_id' => 2,
				'quantity'   => 1,
			)
		);

		$this->sut->remove_item( SavedListsStore::SAVE_FOR_LATER, $drop['key'] );

		$items = $this->sut->get_items( SavedListsStore::SAVE_FOR_LATER );
		$this->assertArrayHasKey( $keep['key'], $items );
		$this->assertArrayNotHasKey( $drop['key'], $items );
	}

	/**
	 * @testdox Should throw 404 when removing an item that does not exist.
	 */
	public function test_remove_item_throws_when_key_not_found(): void {
		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 404 );

		$this->sut->remove_item( SavedListsStore::SAVE_FOR_LATER, str_repeat( 'a', 32 ) );
	}

	/**
	 * @testdox Should throw 401 when the user is not logged in.
	 */
	public function test_methods_throw_when_user_not_logged_in(): void {
		wp_set_current_user( 0 );

		$this->expectException( RouteException::class );
		$this->expectExceptionCode( 401 );

		$this->sut->get_items( SavedListsStore::SAVE_FOR_LATER );
	}
}
