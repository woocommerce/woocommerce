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
	 * @testdox Exporter emits one entry per saved item, scoped to a per-slug group ID.
	 */
	public function test_export_emits_one_entry_per_item_with_slug_scoped_group_id(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );
		$this->seed_list( self::WISHLIST_SLUG );

		$result = $this->sut->export_data( self::TEST_EMAIL );

		$this->assertCount( 2, $result['data'] );

		$group_ids = array_column( $result['data'], 'group_id' );
		$this->assertContains( 'woocommerce-shopper-lists-saved-for-later', $group_ids );
		$this->assertContains( 'woocommerce-shopper-lists-wishlist', $group_ids );

		$labels = array_column( $result['data'], 'group_label', 'group_id' );
		$this->assertSame( 'Shopper List: saved-for-later', $labels['woocommerce-shopper-lists-saved-for-later'] );
		$this->assertSame( 'Shopper List: wishlist', $labels['woocommerce-shopper-lists-wishlist'] );

		foreach ( $result['data'] as $entry ) {
			$this->assertArrayHasKey( 'item_id', $entry );
			$this->assertArrayHasKey( 'data', $entry );
			$this->assertNotEmpty( $entry['data'] );
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
		$this->assertSame( 'woocommerce-shopper-lists-saved-for-later', $result['data'][0]['group_id'] );
	}

	/**
	 * @testdox Exporter includes per-field rows for product, quantity, and date for each item.
	 */
	public function test_export_includes_per_field_rows_for_each_item(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$entry = $this->find_first_entry_for_slug(
			$this->sut->export_data( self::TEST_EMAIL ),
			self::SAVED_FOR_LATER_SLUG
		);

		$this->assertSame( (string) $this->product->get_id(), $this->row_value( $entry, 'Product ID' ) );
		$this->assertSame( 'Privacy SUT Product', $this->row_value( $entry, 'Product' ) );
		$this->assertSame( '1', $this->row_value( $entry, 'Quantity' ) );
		$this->assertNotEmpty( $this->row_value( $entry, 'Date Added' ) );
	}

	/**
	 * @testdox Exporter uses the live product title when the product still exists.
	 */
	public function test_export_uses_live_product_title(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$this->product->set_name( 'Renamed After Save' );
		$this->product->save();

		$entry = $this->find_first_entry_for_slug(
			$this->sut->export_data( self::TEST_EMAIL ),
			self::SAVED_FOR_LATER_SLUG
		);

		$this->assertSame( 'Renamed After Save', $this->row_value( $entry, 'Product' ) );
	}

	/**
	 * @testdox Exporter falls back to the title snapshot when the product is gone.
	 */
	public function test_export_falls_back_to_title_snapshot_when_product_is_deleted(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$this->product->delete( true );
		$this->product = null;

		$entry = $this->find_first_entry_for_slug(
			$this->sut->export_data( self::TEST_EMAIL ),
			self::SAVED_FOR_LATER_SLUG
		);

		$this->assertSame( 'Privacy SUT Product', $this->row_value( $entry, 'Product' ) );
	}

	/**
	 * @testdox Exporter includes a URL row when the product is publicly accessible.
	 */
	public function test_export_includes_url_row_when_product_is_live(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$entry     = $this->find_first_entry_for_slug(
			$this->sut->export_data( self::TEST_EMAIL ),
			self::SAVED_FOR_LATER_SLUG
		);
		$permalink = get_permalink( $this->product->get_id() );

		$this->assertIsString( $permalink );
		$this->assertSame( $permalink, $this->row_value( $entry, 'URL' ) );
	}

	/**
	 * @testdox Exporter omits the URL row when the product is not publicly accessible.
	 */
	public function test_export_omits_url_row_when_product_is_not_live(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );

		$this->product->set_status( 'draft' );
		$this->product->save();

		$entry = $this->find_first_entry_for_slug(
			$this->sut->export_data( self::TEST_EMAIL ),
			self::SAVED_FOR_LATER_SLUG
		);

		$this->assertNull( $this->row_value( $entry, 'URL' ) );
	}

	/**
	 * @testdox Eraser is a no-op when the email does not match any user.
	 */
	public function test_erase_is_noop_for_unknown_email(): void {
		$result = $this->sut->erase_data( 'nobody@example.com' );

		$this->assertFalse( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertSame( array(), $result['messages'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * @testdox Eraser deletes stored shopper-list meta and emits one prefixed message per removed slug.
	 */
	public function test_erase_removes_meta_and_emits_message_per_removed_slug(): void {
		$this->seed_list( self::SAVED_FOR_LATER_SLUG );
		$this->seed_list( self::WISHLIST_SLUG );

		$result = $this->sut->erase_data( self::TEST_EMAIL );

		$this->assertTrue( $result['items_removed'] );
		$this->assertCount( count( self::LIST_OPTIONS ), $result['messages'] );
		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->assertFalse(
				is_array( Users::get_site_user_meta( $this->user_id, ShopperList::META_KEY_PREFIX . $slug ) ),
				"Meta for slug {$slug} should be removed."
			);
			$this->assertContains( "Shopper List: {$slug}", $result['messages'], "Eraser message should reference {$slug}." );
		}
	}

	/**
	 * @testdox Eraser reports items_removed false when the matched user has no stored list data.
	 */
	public function test_erase_reports_no_removal_when_nothing_is_stored(): void {
		$result = $this->sut->erase_data( self::TEST_EMAIL );

		$this->assertFalse( $result['items_removed'] );
		$this->assertSame( array(), $result['messages'] );
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
	 * Pluck the first exported entry whose `group_id` matches the given slug.
	 *
	 * @param array  $result Result from Privacy::export_data().
	 * @param string $slug   List slug.
	 *
	 * @return array<string, mixed>
	 */
	private function find_first_entry_for_slug( array $result, string $slug ): array {
		$group_id = 'woocommerce-shopper-lists-' . $slug;
		foreach ( $result['data'] as $entry ) {
			if ( $group_id === $entry['group_id'] ) {
				return $entry;
			}
		}
		$this->fail( "No exported entry for slug {$slug}." );
	}

	/**
	 * Return the value of the row with the given name within an exported entry,
	 * or null if no such row exists.
	 *
	 * @param array  $entry     Exported entry returned by find_first_entry_for_slug().
	 * @param string $row_name  Row name to look up.
	 */
	private function row_value( array $entry, string $row_name ): ?string {
		foreach ( $entry['data'] as $row ) {
			if ( $row_name === $row['name'] ) {
				return $row['value'];
			}
		}
		return null;
	}

	/**
	 * Seed a stored shopper list of the given slug for the test user.
	 *
	 * @param string $slug List slug.
	 */
	private function seed_list( string $slug ): void {
		$list = ShopperList::get_by_slug_raw( $slug, $this->user_id );
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
