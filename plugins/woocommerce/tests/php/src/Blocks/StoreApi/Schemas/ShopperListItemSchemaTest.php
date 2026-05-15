<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListItemSchema;
use WC_Unit_Test_Case;

/**
 * ShopperListItemSchemaTest class.
 */
class ShopperListItemSchemaTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var ShopperListItemSchema
	 */
	private $sut;

	/**
	 * ExtendSchema instance.
	 *
	 * @var ExtendSchema
	 */
	private $extend;

	/**
	 * SchemaController instance.
	 *
	 * @var SchemaController
	 */
	private $schema_controller;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$this->extend            = new ExtendSchema( $formatters );
		$this->schema_controller = new SchemaController( $this->extend );
		$this->sut               = new ShopperListItemSchema( $this->extend, $this->schema_controller );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->sut               = null;
		$this->extend            = null;
		$this->schema_controller = null;
	}

	/**
	 * Build a ShopperListItem around a product.
	 *
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID, or 0.
	 * @param array  $variation    Variation attributes.
	 * @param string $title        Title snapshot.
	 */
	private function build_item( int $product_id, int $variation_id = 0, array $variation = array(), string $title = 'Snapshot Title' ): ShopperListItem {
		return ShopperListItem::from_array(
			array(
				'key'                   => md5( (string) $product_id ),
				'product_id'            => $product_id,
				'variation_id'          => $variation_id,
				'variation'             => $variation,
				'quantity'              => 1,
				'date_added_gmt'        => '2024-04-25 03:20:00',
				'product_title_at_save' => $title,
			)
		);
	}

	/**
	 * @testdox Should serve live product data and is_live=true for a published product.
	 */
	public function test_returns_live_data_when_is_live(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Live T-Shirt',
				'regular_price' => 19.99,
			)
		);

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id(), 0, array(), 'Snapshot T-Shirt' ) );

		$this->assertTrue( $response['is_live'], 'is_live must be true for a published product' );
		$this->assertTrue( $response['is_purchasable'], 'A published, in-stock priced product is purchasable' );
		$this->assertSame( 'Live T-Shirt', $response['name'], 'Live name should be served, not the snapshot' );
		$this->assertSame( $product->get_permalink(), $response['permalink'] );
		$this->assertNotNull( $response['prices'], 'Live prices should be populated' );

		$product->delete( true );
	}

	/**
	 * @testdox is_live should gate on publish status (self and parent). Stock, catalog visibility, and post password don't affect it.
	 * @dataProvider provider_is_live_cases
	 *
	 * @param array<string, string> $overrides     Post/product overrides to apply.
	 * @param bool                  $expected_live Expected is_live value.
	 */
	public function test_is_live_predicate( array $overrides, bool $expected_live ): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Subject',
				'regular_price' => 19.99,
			)
		);

		if ( isset( $overrides['stock_status'] ) ) {
			$product->set_stock_status( $overrides['stock_status'] );
			$product->save();
		}
		if ( isset( $overrides['catalog_visibility'] ) ) {
			$product->set_catalog_visibility( $overrides['catalog_visibility'] );
			$product->save();
		}
		$post_overrides = array_intersect_key( $overrides, array_flip( array( 'post_status', 'post_password' ) ) );
		if ( ! empty( $post_overrides ) ) {
			// `wp_update_post` silently rewrites `future` back to `publish` when post_date is in the past,
			// so a future date is needed to actually persist the status.
			if ( 'future' === ( $post_overrides['post_status'] ?? '' ) ) {
				$post_overrides['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
				$post_overrides['post_date']     = $post_overrides['post_date_gmt'];
			}
			wp_update_post( array_merge( array( 'ID' => $product->get_id() ), $post_overrides ) );
		}

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id(), 0, array(), 'Snapshot Title' ) );

		$this->assertSame( $expected_live, $response['is_live'] );
		if ( ! $expected_live ) {
			$this->assertFalse( $response['is_purchasable'], 'Non-public products are not purchasable' );
			$this->assertSame( 'Snapshot Title', $response['name'], 'Tombstone must not leak the live title' );
			$this->assertNull( $response['permalink'], 'Tombstone permalink must be null so iAPI strips the anchor href' );
			$this->assertNull( $response['prices'], 'Tombstone must not leak live prices' );
		}

		wp_delete_post( $product->get_id(), true );
	}

	/**
	 * @return array<string, array{0: array<string, string>, 1: bool}>
	 */
	public function provider_is_live_cases(): array {
		return array(
			// Renderable — guards against using `is_visible()`, which would
			// tombstone deliberately-saved OOS / catalog-hidden items.
			'OOS, publish'              => array( array( 'stock_status' => 'outofstock' ), true ),
			'catalog_visibility=hidden' => array( array( 'catalog_visibility' => 'hidden' ), true ),
			// Tombstone cases.
			'draft'                     => array( array( 'post_status' => 'draft' ), false ),
			'pending'                   => array( array( 'post_status' => 'pending' ), false ),
			'private'                   => array( array( 'post_status' => 'private' ), false ),
			'trash'                     => array( array( 'post_status' => 'trash' ), false ),
			'future'                    => array( array( 'post_status' => 'future' ), false ),
		);
	}

	/**
	 * @testdox Out-of-stock products stay live but aren't purchasable, matching catalog behavior.
	 */
	public function test_out_of_stock_product_is_live_but_not_purchasable(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'OOS T-Shirt',
				'regular_price' => 19.99,
			)
		);
		$product->set_stock_status( 'outofstock' );
		$product->save();

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertTrue( $response['is_live'], 'OOS products stay renderable' );
		$this->assertFalse( $response['is_purchasable'], 'Cart button hidden for OOS, mirroring the storefront catalog gate (is_purchasable() && is_in_stock())' );

		$product->delete( true );
	}

	/**
	 * @testdox Password-protected products stay live (clickable) but aren't purchasable.
	 */
	public function test_password_protected_product_is_live_but_not_purchasable(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Gated T-Shirt',
				'regular_price' => 19.99,
			)
		);
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret',
			)
		);

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertTrue( $response['is_live'], 'Page renders behind a password prompt, so the row stays clickable' );
		$this->assertFalse( $response['is_purchasable'], 'Customer must authenticate before purchasing' );
		$this->assertSame( $product->get_permalink(), $response['permalink'] );

		wp_delete_post( $product->get_id(), true );
	}

	/**
	 * @testdox Admins viewing their own draft products see a tombstone, not a Move-to-cart button.
	 */
	public function test_admin_viewing_own_draft_does_not_short_circuit_is_purchasable(): void {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Pre-launch T-Shirt',
				'regular_price' => 19.99,
			)
		);
		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_status' => 'draft',
			)
		);

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		// WC_Product::is_purchasable() short-circuits via `current_user_can( 'edit_post', ... )`
		// for admins. Our predicate must gate on is_live first so the tombstone row isn't
		// rendered with a working cart button.
		$this->assertFalse( $response['is_live'] );
		$this->assertFalse( $response['is_purchasable'], 'Admin escape hatch in WC_Product::is_purchasable() must not leak into is_purchasable on a tombstone row.' );

		wp_delete_post( $product->get_id(), true );
		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox Should tombstone a variation whose parent is not published.
	 */
	public function test_variation_with_non_publish_parent_is_tombstoned(): void {
		$variable = \WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();
		$this->assertNotEmpty( $children, 'Variable product helper should produce variation children' );
		$variation_id = (int) $children[0];

		wp_update_post(
			array(
				'ID'          => $variable->get_id(),
				'post_status' => 'draft',
			)
		);

		$response = $this->sut->get_item_response(
			$this->build_item( $variable->get_id(), $variation_id, array(), 'Snapshot Title' )
		);

		$this->assertFalse( $response['is_live'], 'Variations under a non-publish parent must be tombstoned' );
		$this->assertSame( 'Snapshot Title', $response['name'] );

		$variable->delete( true );
	}

	/**
	 * @testdox Variations under a password-protected parent stay live but aren't purchasable.
	 */
	public function test_variation_with_password_protected_parent_is_not_purchasable(): void {
		$variable = \WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();
		$this->assertNotEmpty( $children, 'Variable product helper should produce variation children' );
		$variation_id = (int) $children[0];

		wp_update_post(
			array(
				'ID'            => $variable->get_id(),
				'post_password' => 'secret',
			)
		);

		$response = $this->sut->get_item_response(
			$this->build_item( $variable->get_id(), $variation_id, array(), 'Snapshot Title' )
		);

		$this->assertTrue( $response['is_live'], 'Parent still renders behind a password prompt' );
		$this->assertFalse( $response['is_purchasable'], 'Parent password must gate the variation too' );

		$variable->delete( true );
	}

	/**
	 * @testdox Should expose price_html for live products and an empty string for tombstones.
	 */
	public function test_price_html_is_populated_for_live_products(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Priced T-Shirt',
				'regular_price' => 19.99,
			)
		);

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'price_html', $response, 'price_html must be present on the response' );
		$this->assertIsString( $response['price_html'] );
		$this->assertNotSame( '', $response['price_html'], 'price_html must be non-empty for a live priced product' );
		$this->assertStringContainsString( 'woocommerce-Price-amount', $response['price_html'], 'price_html should be the formatted markup from wc_price' );

		$product->delete( true );
	}

	/**
	 * @testdox Should expose image_html with the configured product thumbnail when one is set.
	 */
	public function test_image_html_uses_product_thumbnail_when_available(): void {
		$attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$product       = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Imaged T-Shirt' ) );
		$product->set_image_id( $attachment_id );
		$product->save();

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'image_html', $response, 'image_html must be present on the response' );
		$this->assertIsString( $response['image_html'] );
		$this->assertStringContainsString( '<img', $response['image_html'], 'image_html must be a fully-formed <img> element' );
		$this->assertStringContainsString( 'srcset=', $response['image_html'], 'image_html must carry the responsive srcset attribute' );

		$product->delete( true );
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * @testdox Should expose image_html using the WooCommerce placeholder when the product has no image.
	 */
	public function test_image_html_falls_back_to_placeholder_when_product_has_no_image(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'No-Image T-Shirt' ) );
		$product->set_image_id( 0 );
		$product->save();

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'image_html', $response );
		$this->assertSame(
			(string) wc_placeholder_img( 'woocommerce_thumbnail' ),
			$response['image_html'],
			'image_html for an image-less product must equal the configured placeholder markup'
		);

		$product->delete( true );
	}

	/**
	 * @testdox Should fall back to at-save snapshot data when the product no longer exists.
	 */
	public function test_falls_back_to_snapshot_when_product_missing(): void {
		$product    = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'About to be Deleted' ) );
		$product_id = $product->get_id();
		$item       = $this->build_item( $product_id, 0, array(), 'Snapshot Title' );
		wp_delete_post( $product_id, true );

		$response = $this->sut->get_item_response( $item );

		$this->assertFalse( $response['is_live'], 'is_live must be false when the product is gone' );
		$this->assertSame( 'Snapshot Title', $response['name'], 'Tombstone name should fall back to the at-save title snapshot' );
		$this->assertNull( $response['permalink'], 'Tombstone permalink must be null so iAPI strips the anchor href' );
		$this->assertSame( array(), $response['images'], 'No images should be returned for missing products' );
		$this->assertNull( $response['prices'], 'Live prices should be null for missing products' );
		$this->assertArrayNotHasKey( 'product_title_at_save', $response, 'Internal at-save title snapshot should not leak into the public response' );
	}

	/**
	 * @testdox Should expose an empty price_html and the placeholder image_html for tombstones.
	 */
	public function test_tombstone_returns_empty_price_html_and_placeholder_image_html(): void {
		$product    = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Going Away',
				'regular_price' => 24.99,
			)
		);
		$product_id = $product->get_id();
		$item       = $this->build_item( $product_id, 0, array(), 'Going Away' );
		wp_delete_post( $product_id, true );

		$response = $this->sut->get_item_response( $item );

		$this->assertArrayHasKey( 'price_html', $response );
		$this->assertSame( '', $response['price_html'], 'Tombstones must not advertise a price' );
		$this->assertArrayHasKey( 'image_html', $response );
		$this->assertSame(
			(string) wc_placeholder_img( 'woocommerce_thumbnail' ),
			$response['image_html'],
			'Tombstones must use the placeholder image markup'
		);
	}

	/**
	 * @testdox Should expose key on a live-product response.
	 */
	public function test_response_carries_the_item_key(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$item    = $this->build_item( $product->get_id() );

		$response = $this->sut->get_item_response( $item );

		$this->assertArrayHasKey( 'key', $response );
		$this->assertSame( $item->get_key(), $response['key'], 'Response key must match the saved item key.' );

		$product->delete( true );
	}

	/**
	 * @testdox Should format date_added_gmt via wc_rest_prepare_date_response (ISO8601, no timezone suffix).
	 */
	public function test_date_added_gmt_is_iso8601(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'date_added_gmt', $response );
		$this->assertSame( '2024-04-25T03:20:00', $response['date_added_gmt'], 'date_added_gmt must be the ISO8601 formatting of the GMT save time.' );

		$product->delete( true );
	}

	/**
	 * @testdox Should expose the variation ID as id when the saved item is a variation.
	 */
	public function test_id_resolves_to_variation_id_for_variations(): void {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variation_ids    = $variable_product->get_children();
		$variation_id     = (int) $variation_ids[0];
		$item             = $this->build_item( $variable_product->get_id(), $variation_id );

		$response = $this->sut->get_item_response( $item );

		$this->assertArrayHasKey( 'id', $response );
		$this->assertSame( $variation_id, $response['id'], 'id should resolve to variation_id when set.' );
		$this->assertArrayHasKey( 'product_id', $response );
		$this->assertSame( $variable_product->get_id(), $response['product_id'], 'product_id should still hold the parent product id.' );
		$this->assertArrayHasKey( 'variation_id', $response );
		$this->assertSame( $variation_id, $response['variation_id'] );

		$variable_product->delete( true );
	}

	/**
	 * @testdox Should format saved variation attributes via format_variation_data on live variations.
	 */
	public function test_variation_attributes_are_formatted_on_live_variations(): void {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variation_id     = (int) $variable_product->get_children()[0];
		$item             = $this->build_item(
			$variable_product->get_id(),
			$variation_id,
			array( 'attribute_pa_size' => 'small' )
		);

		$response = $this->sut->get_item_response( $item );

		$this->assertArrayHasKey( 'variation', $response );
		$this->assertIsArray( $response['variation'] );
		$this->assertCount( 1, $response['variation'], 'A single saved attribute should produce one entry in the variation list.' );

		$entry = $response['variation'][0];
		$this->assertArrayHasKey( 'raw_attribute', $entry );
		$this->assertArrayHasKey( 'attribute', $entry );
		$this->assertArrayHasKey( 'value', $entry );
		$this->assertSame( 'attribute_pa_size', $entry['raw_attribute'] );

		$variable_product->delete( true );
	}
}
