<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListSchema;
use WC_Unit_Test_Case;

/**
 * ShopperListSchemaTest class.
 */
class ShopperListSchemaTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var ShopperListSchema
	 */
	private $sut;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		// `saved-for-later` depends on the `cart_save_for_later` feature
		// flag. Filter the option read so `ShopperList::get_by_slug()`
		// returns a list without writing the option to the database.
		add_filter( 'pre_option_woocommerce_cart_save_for_later_enabled', array( $this, 'filter_save_for_later_enabled' ) );

		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$extend            = new ExtendSchema( $formatters );
		$schema_controller = new SchemaController( $extend );
		$this->sut         = new ShopperListSchema( $extend, $schema_controller );
		$this->user_id     = $this->factory->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		wp_delete_user( $this->user_id );
		$this->sut = null;
		remove_filter( 'pre_option_woocommerce_cart_save_for_later_enabled', array( $this, 'filter_save_for_later_enabled' ) );
		parent::tearDown();
	}

	/**
	 * Filter callback that forces the SFL option to `yes` for the lifetime of the test.
	 */
	public function filter_save_for_later_enabled(): string {
		return 'yes';
	}

	/**
	 * Build an empty saved-for-later list for the test user.
	 */
	private function build_list(): ShopperList {
		return ShopperList::get_by_slug( 'saved-for-later', $this->user_id );
	}

	/**
	 * Build a minimal ShopperListItem around a product ID.
	 *
	 * @param int $product_id Product ID.
	 */
	private function build_item( int $product_id ): ShopperListItem {
		return ShopperListItem::from_array(
			array(
				'key'                   => md5( (string) $product_id ),
				'product_id'            => $product_id,
				'variation_id'          => 0,
				'variation'             => array(),
				'quantity'              => 1,
				'date_added_gmt'        => '2024-04-25 03:20:00',
				'product_title_at_save' => 'Snapshot',
			)
		);
	}

	/**
	 * @testdox Should expose slug, item_count, date_created_gmt and a 0-indexed items array.
	 */
	public function test_serializes_top_level_fields(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$list    = $this->build_list();
		$list->add_item( $this->build_item( $product->get_id() ) );

		$response = $this->sut->get_item_response( $list );

		$this->assertArrayHasKey( 'slug', $response );
		$this->assertSame( 'saved-for-later', $response['slug'] );

		$this->assertArrayHasKey( 'item_count', $response );
		$this->assertSame( 1, $response['item_count'] );

		$this->assertArrayHasKey( 'items', $response );
		$this->assertIsArray( $response['items'] );
		$this->assertSame( array( 0 ), array_keys( $response['items'] ), 'items must be a 0-indexed list, not keyed by storage key.' );

		$this->assertArrayHasKey( 'date_created_gmt', $response );
		$this->assertIsString( $response['date_created_gmt'] );
		$this->assertNotSame( '', $response['date_created_gmt'] );

		$product->delete( true );
	}

	/**
	 * @testdox item_count should include items whose products no longer exist.
	 */
	public function test_item_count_includes_tombstones(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$list    = $this->build_list();
		$list->add_item( $this->build_item( $product->get_id() ) );
		$list->add_item( $this->build_item( 999999 ) );

		$response = $this->sut->get_item_response( $list );

		$this->assertSame( 2, $response['item_count'], 'item_count must count tombstoned items, not just live products.' );
		$this->assertCount( 2, $response['items'] );

		$product->delete( true );
	}

	/**
	 * @testdox Empty list should serialize with item_count=0 and items=[].
	 */
	public function test_empty_list(): void {
		$list = $this->build_list();

		$response = $this->sut->get_item_response( $list );

		$this->assertSame( 0, $response['item_count'] );
		$this->assertSame( array(), $response['items'] );
	}

	/**
	 * @testdox Each item should carry the keys produced by the item schema.
	 */
	public function test_items_use_item_schema_shape(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$list    = $this->build_list();
		$list->add_item( $this->build_item( $product->get_id() ) );

		$response = $this->sut->get_item_response( $list );

		$item = $response['items'][0];
		$this->assertArrayHasKey( 'key', $item );
		$this->assertArrayHasKey( 'product_id', $item );
		$this->assertArrayHasKey( 'quantity', $item );
		$this->assertArrayHasKey( 'is_live', $item );
		$this->assertTrue( $item['is_live'], 'Live product items should have is_live=true.' );

		$product->delete( true );
	}
}
