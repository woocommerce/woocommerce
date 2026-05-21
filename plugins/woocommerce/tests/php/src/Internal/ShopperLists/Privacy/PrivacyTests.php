<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ShopperLists\Privacy;

use Automattic\WooCommerce\Internal\ShopperLists\Privacy\Privacy;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Unit tests for the shopper-lists privacy exporter and eraser.
 */
class PrivacyTests extends WC_Unit_Test_Case {

	private const SAVED_FOR_LATER_SLUG = 'saved-for-later';
	private const WISHLIST_SLUG        = 'wishlist';

	/**
	 * Map of shopper-list slug => feature option key.
	 */
	private const LIST_OPTIONS = array(
		self::SAVED_FOR_LATER_SLUG => 'woocommerce_cart_save_for_later_enabled',
		self::WISHLIST_SLUG        => 'woocommerce_product_wishlist_enabled',
	);

	private const TEST_EMAIL = 'shopper-privacy@example.com';

	/**
	 * The System Under Test.
	 *
	 * @var Privacy
	 */
	private $sut;

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

		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->enable_list( $slug );
		}

		$this->sut = new Privacy();

		$this->user_id = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => self::TEST_EMAIL,
			)
		);

		$this->product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Privacy SUT Product',
				'regular_price' => 10.00,
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
		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->disable_list( $slug );
		}
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
		parent::tearDown();
	}

	/**
	 * @testdox Exporter returns no data when the email does not match any user.
	 */
	public function test_export_returns_empty_for_unknown_email(): void {
		$result = $this->sut->export_data( 'nobody@example.com' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertSame( array(), $result['data'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * @testdox Exporter returns one group per stored shopper list.
	 */
	public function test_export_returns_one_group_per_stored_list(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );
		$this->seed_list( self::WISHLIST_SLUG );

		$result = $this->sut->export_data( self::TEST_EMAIL );

		$this->assertCount( 2, $result['data'] );
		$item_ids = array_column( $result['data'], 'item_id' );
		$this->assertContains( 'shopper-list-saved-for-later', $item_ids );
		$this->assertContains( 'shopper-list-wishlist', $item_ids );

		foreach ( $result['data'] as $group ) {
			$this->assertSame( 'woocommerce-shopper-lists', $group['group_id'] );
			$this->assertArrayHasKey( 'data', $group );
			$this->assertNotEmpty( $group['data'] );
		}
	}

	/**
	 * @testdox Exporter does not emit phantom empty in-memory lists when no meta is stored.
	 */
	public function test_export_skips_lists_with_no_stored_items(): void {
		$result = $this->sut->export_data( self::TEST_EMAIL );

		$this->assertSame( array(), $result['data'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * @testdox Exporter surfaces stored data even when the backing feature is disabled.
	 */
	public function test_export_surfaces_stored_data_when_feature_is_disabled(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$this->disable_list( self::SAVED_FOR_LATER_SLUG );

		$result = $this->sut->export_data( self::TEST_EMAIL );

		$this->assertCount( 1, $result['data'] );
		$this->assertSame( 'shopper-list-saved-for-later', $result['data'][0]['item_id'] );
	}

	/**
	 * @testdox Exporter includes the product title snapshot in each item row.
	 */
	public function test_export_includes_product_title_snapshot_in_item_row(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$result = $this->sut->export_data( self::TEST_EMAIL );

		$rows         = $result['data'][0]['data'];
		$row_values   = array_column( $rows, 'value' );
		$item_strings = array_filter(
			$row_values,
			static fn( string $value ): bool => false !== strpos( $value, 'Privacy SUT Product' )
		);
		$this->assertNotEmpty( $item_strings, 'Item row should contain the product title snapshot.' );
	}

	/**
	 * @testdox Eraser is a no-op when the email does not match any user.
	 */
	public function test_erase_is_noop_for_unknown_email(): void {
		$result = $this->sut->erase_data( 'nobody@example.com' );

		$this->assertFalse( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * @testdox Eraser deletes stored shopper-list meta for every supported slug.
	 */
	public function test_erase_removes_meta_for_every_supported_slug(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );
		$this->seed_list( self::WISHLIST_SLUG );

		$result = $this->sut->erase_data( self::TEST_EMAIL );

		$this->assertTrue( $result['items_removed'] );
		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->assertFalse(
				is_array( Users::get_site_user_meta( $this->user_id, ShopperList::META_KEY_PREFIX . $slug ) ),
				"Meta for slug {$slug} should be removed."
			);
		}
	}

	/**
	 * @testdox Eraser reports items_removed false when there is nothing to erase.
	 */
	public function test_erase_reports_no_removal_when_nothing_is_stored(): void {
		$result = $this->sut->erase_data( self::TEST_EMAIL );

		$this->assertFalse( $result['items_removed'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * @testdox Eraser removes stored data even when the backing feature is disabled.
	 */
	public function test_erase_removes_stored_data_when_feature_is_disabled(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$this->disable_list( self::SAVED_FOR_LATER_SLUG );

		$result = $this->sut->erase_data( self::TEST_EMAIL );

		$this->assertTrue( $result['items_removed'] );
		$this->assertFalse(
			is_array( Users::get_site_user_meta( $this->user_id, ShopperList::META_KEY_PREFIX . self::SAVED_FOR_LATER_SLUG ) )
		);
	}

	/**
	 * Seed a stored shopper list of the given slug for the test user.
	 *
	 * @param string $slug List slug.
	 */
	private function seed_list( string $slug ): void {
		$list = ShopperList::get_by_slug( $slug, $this->user_id, true );
		$this->assertNotFalse( $list );
		$list->add_item( ShopperListItem::from_product( $this->product->get_id() ) );
		$list->save();
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
}
