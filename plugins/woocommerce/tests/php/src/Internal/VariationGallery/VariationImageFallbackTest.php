<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\Utilities\ProductUtil;
use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;

/**
 * Tests for the shared variation main-image fallback and its consumers.
 */
class VariationImageFallbackTest extends \WC_Unit_Test_Case {

	/** @var int[] Attachment IDs keyed by photo name. */
	private $images;

	/** @var \WC_Product_Variable Parent product. */
	private $parent;

	/** @var \WC_Product_Variation The System Under Test. */
	private $sut;

	/**
	 * Create a parent and a gallery-only variation.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->images = array();
		foreach ( array( 'parent', 'front', 'back' ) as $name ) {
			$id = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg' ) );
			update_post_meta( $id, '_wp_attached_file', 'fallback-' . $name . '.jpg' );
			$this->images[ $name ] = $id;
		}
		$this->parent = new \WC_Product_Variable();
		$this->parent->set_name( 'Fallback shirt' );
		$this->parent->set_image_id( $this->images['parent'] );
		$this->parent->save();

		$this->sut = new \WC_Product_Variation();
		$this->sut->set_parent_id( $this->parent->get_id() );
		$this->sut->set_regular_price( '10' );
		$this->sut->set_gallery_image_ids( array( $this->images['front'], $this->images['back'] ) );
		$this->sut->save();
		$this->sut = new \WC_Product_Variation( $this->sut->get_id() );
	}

	/**
	 * @testdox Should prefer the first usable gallery photo without changing saved fields or pending changes.
	 */
	public function test_read_only_fallback(): void {
		$this->sut->set_gallery_image_ids( array( 999999999, $this->images['front'], $this->images['back'] ) );
		$this->sut->save();
		$before = get_post_meta( $this->sut->get_id() );

		$this->assertSame( $this->images['front'], $this->sut->get_image_id() );
		$this->assertSame( 0, (int) $this->sut->get_image_id( 'edit' ), 'Edit context must expose the empty saved main image.' );
		$this->assertSame( $before, get_post_meta( $this->sut->get_id() ), 'Reading must not write product metadata.' );
		$this->assertSame( array(), $this->sut->get_changes(), 'Reading must not schedule changes for a later save.' );
	}

	/**
	 * @testdox Should retain explicit main images, including the parent image, without reading the gallery.
	 * @testWith ["front"]
	 *           ["parent"]
	 *
	 * @param string $name Featured photo name.
	 */
	public function test_explicit_featured_fast_path( string $name ): void {
		$this->sut->set_image_id( $this->images[ $name ] );
		$gallery_reads = 0;
		add_filter(
			'woocommerce_product_variation_get_gallery_image_ids',
			function ( $ids ) use ( &$gallery_reads ) {
				++$gallery_reads;
				return $ids;
			}
		);

		$this->assertSame( $this->images[ $name ], $this->sut->get_image_id() );
		$this->assertSame( 0, $gallery_reads, 'An assigned main image must retain the existing cheap path.' );
	}

	/**
	 * @testdox Should skip gallery lookups in edit context even when the saved main image is empty.
	 */
	public function test_edit_context_does_not_read_gallery(): void {
		add_filter(
			'woocommerce_product_variation_get_gallery_image_ids',
			function () {
				$this->fail( 'Edit context must not read filtered gallery values.' );
			}
		);
		$this->assertSame( 0, (int) $this->sut->get_image_id( 'edit' ) );
	}

	/**
	 * @testdox Should fall back to the parent when no usable gallery photo exists.
	 */
	public function test_invalid_gallery_falls_back_to_parent(): void {
		$this->sut->set_gallery_image_ids( array( 999999999 ) );
		$this->assertSame( $this->images['parent'], $this->sut->get_image_id() );
		$this->parent->set_image_id( $this->images['back'] );
		$this->parent->save();
		$this->sut = new \WC_Product_Variation( $this->sut->get_id() );
		$this->sut->set_gallery_image_ids( array() );
		$this->assertSame( $this->images['back'], $this->sut->get_image_id(), 'An unassigned variation must continue following parent changes.' );
		$this->parent->set_image_id( 0 );
		$this->parent->save();
		$this->sut = new \WC_Product_Variation( $this->sut->get_id() );
		$this->sut->set_gallery_image_ids( array() );
		$this->assertSame( 0, (int) $this->sut->get_image_id(), 'No photos must yield the existing empty-image value.' );
	}

	/**
	 * @testdox Should preserve both image-filter calls and let filters override the gallery fallback.
	 */
	public function test_image_filter_receives_gallery_fallback(): void {
		$seen = array();
		add_filter(
			'woocommerce_product_variation_get_image_id',
			function ( $id ) use ( &$seen ) {
				$seen[] = (int) $id;
				return $id ? $this->images['back'] : $id;
			}
		);
		$this->assertSame( $this->images['back'], $this->sut->get_image_id() );
		$this->assertSame( array( 0, $this->images['front'] ), $seen, 'The existing raw-then-fallback filter order must be retained.' );
	}

	/**
	 * @testdox Should accept gallery filter photos and ignore malformed values safely.
	 * @testWith [null]
	 *           [false]
	 *           ["invalid"]
	 *           [[null, false, -1, {}, "invalid"]]
	 *
	 * @param mixed $ids Gallery filter result.
	 */
	public function test_invalid_filtered_gallery( $ids ): void {
		add_filter( 'woocommerce_product_variation_get_gallery_image_ids', static fn() => $ids );
		$this->assertSame( $this->images['parent'], $this->sut->get_image_id() );
	}

	/**
	 * @testdox Should resolve numeric-string gallery filter IDs and respond to later gallery changes.
	 */
	public function test_filtered_gallery_and_later_changes(): void {
		$filter = fn() => array( (string) $this->images['back'] );
		add_filter( 'woocommerce_product_variation_get_gallery_image_ids', $filter );
		$this->assertSame( $this->images['back'], $this->sut->get_image_id() );
		remove_filter( 'woocommerce_product_variation_get_gallery_image_ids', $filter );
		$this->sut->set_gallery_image_ids( array( $this->images['front'] ) );
		$this->assertSame( $this->images['front'], $this->sut->get_image_id(), 'A previous read must not cache a now-stale selection.' );
	}

	/**
	 * @testdox Should allow a gallery filter to read the main image without recursion.
	 */
	public function test_gallery_filter_can_read_main_image(): void {
		$calls = 0;
		add_filter(
			'woocommerce_product_variation_get_gallery_image_ids',
			function ( $ids, $variation ) use ( &$calls ) {
				$this->assertLessThan( 3, ++$calls, 'Gallery lookup must not recurse through the getter.' );
				$this->assertSame( $this->images['parent'], $variation->get_image_id(), 'The nested read should retain the parent fallback.' );
				return $ids;
			},
			10,
			2
		);
		$this->assertSame( $this->images['front'], $this->sut->get_image_id(), 'The outer read must still choose the gallery photo.' );
	}

	/**
	 * @testdox Should reset the gallery lookup guard after a filter throws.
	 */
	public function test_gallery_lookup_guard_recovers_after_exception(): void {
		$filter = static function () {
			throw new \RuntimeException( 'Gallery filter failed.' );
		};
		add_filter( 'woocommerce_product_variation_get_gallery_image_ids', $filter );
		try {
			$this->sut->get_image_id();
			$this->fail( 'The filter exception must propagate.' );
		} catch ( \RuntimeException $error ) {
			$this->assertSame( 'Gallery filter failed.', $error->getMessage() );
		} finally {
			remove_filter( 'woocommerce_product_variation_get_gallery_image_ids', $filter );
		}
		$this->assertSame( $this->images['front'], $this->sut->get_image_id() );
	}

	/**
	 * @testdox Should use legacy AVI gallery photos until core explicitly disables that fallback.
	 */
	public function test_legacy_gallery_fallback_respects_core_management(): void {
		( new LegacyVariationGalleryCompatibility() )->register();
		$this->sut->set_gallery_image_ids( array() );
		$this->sut->save();
		$this->sut = new \WC_Product_Variation( $this->sut->get_id() );
		update_post_meta( $this->sut->get_id(), '_wc_additional_variation_images', array( $this->images['back'] ) );
		$this->assertSame( $this->images['back'], $this->sut->get_image_id(), 'Legacy gallery photos must use the shared main-image fallback.' );
		LegacyVariationGalleryCompatibility::mark_variation_id_core_managed( $this->sut->get_id() );
		$this->assertSame( $this->images['parent'], $this->sut->get_image_id(), 'An explicitly cleared core gallery must not revive legacy photos.' );
	}

	/**
	 * @testdox Should skip a deleted gallery photo on the next read without rewriting the gallery.
	 */
	public function test_deleted_gallery_photo_is_skipped(): void {
		$this->assertSame( $this->images['front'], $this->sut->get_image_id() );
		wp_delete_attachment( $this->images['front'], true );
		$this->assertSame( $this->images['back'], $this->sut->get_image_id() );
		$this->assertSame( array( $this->images['front'], $this->images['back'] ), $this->sut->get_gallery_image_ids( 'edit' ), 'A read fallback must not repair stored gallery assignments.' );
		$restored_id = wp_insert_attachment(
			array(
				'import_id'      => $this->images['front'],
				'post_mime_type' => 'image/jpeg',
			)
		);
		update_post_meta( $restored_id, '_wp_attached_file', 'fallback-restored.jpg' );
		$this->assertSame( $this->images['front'], $restored_id, 'The fixture must restore the same previously missing ID.' );
		$this->assertSame( $restored_id, $this->sut->get_image_id(), 'Creating an attachment must invalidate the missing-image cache.' );
	}

	/**
	 * @testdox Should not repeat database queries for missing gallery attachments within the same request.
	 */
	public function test_missing_gallery_attachment_queries_are_not_repeated(): void {
		global $wpdb;
		$this->sut->set_gallery_image_ids( array( 999999999 ) );
		$this->assertSame( $this->images['parent'], $this->sut->get_image_id() );
		$before = $wpdb->num_queries;
		foreach ( range( 1, 10 ) as $index ) {
			$this->assertSame( $this->images['parent'], $this->sut->get_image_id(), 'Read ' . $index . ' must retain the parent fallback.' );
		}
		$this->assertSame( $before, $wpdb->num_queries, 'A missing attachment must not trigger another query until post data changes.' );
	}

	/**
	 * @testdox Should keep missing-attachment results separate when switching sites.
	 */
	public function test_missing_attachment_cache_is_site_scoped(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This case requires multisite.' );
		}
		$site_id = self::factory()->blog->create();
		wp_delete_attachment( $this->images['front'], true );
		$this->assertSame( $this->images['back'], $this->sut->get_image_id() );

		switch_to_blog( $site_id );
		try {
			wp_delete_post( $this->images['front'], true );
			$image_id = wp_insert_attachment(
				array(
					'import_id'      => $this->images['front'],
					'post_mime_type' => 'image/jpeg',
				)
			);
			update_post_meta( $image_id, '_wp_attached_file', 'other-site-photo.jpg' );
			$this->assertSame( $this->images['front'], $image_id, 'Both sites must use the same numeric attachment ID.' );
			$variation = new \WC_Product_Variation();
			$variation->set_gallery_image_ids( array( $image_id ) );
			$this->assertSame( $image_id, $variation->get_image_id(), 'A missing attachment on one site must not hide a photo on another.' );
		} finally {
			restore_current_blog();
		}
		$this->assertSame( $this->images['back'], $this->sut->get_image_id(), 'Returning to the first site must retain its own gallery result.' );
	}

	/**
	 * @testdox Should show the same main photo in product images, Store API products, cart items and email items.
	 */
	public function test_cart_email_and_store_api_main_photo(): void {
		$this->assertStringContainsString( 'fallback-front.jpg', $this->sut->get_image(), 'Classic cart thumbnails use the product image renderer.' );
		$product_schema = wc_get_container()->get( \Automattic\WooCommerce\StoreApi\SchemaController::class )->get( ProductSchema::IDENTIFIER );
		$product_data   = $product_schema->get_item_response( $this->sut );
		$this->assertSame( $this->images['front'], $product_data['images'][0]->id, 'Store API products must begin with the gallery fallback.' );
		$this->assertCount( 2, $product_data['images'], 'The fallback main image must not be duplicated.' );

		$key = WC()->cart->add_to_cart( $this->parent->get_id(), 1, $this->sut->get_id() );
		$this->assertNotFalse( $key, 'The gallery-only variation must be addable to the cart.' );
		$cart_schema = wc_get_container()->get( \Automattic\WooCommerce\StoreApi\SchemaController::class )->get( CartItemSchema::IDENTIFIER );
		$cart_data   = $cart_schema->get_item_response( WC()->cart->get_cart_item( $key ) );
		$this->assertSame( $this->images['front'], $cart_data['images'][0]->id, 'Cart and checkout must receive the gallery fallback photo.' );

		$order = wc_create_order();
		$order->add_product( $this->sut, 1 );
		$order->save();
		$html = wc_get_email_order_items( $order, array( 'show_image' => true ) );
		$this->assertStringContainsString( 'fallback-front.jpg', $html, 'Email item thumbnails must use the gallery fallback.' );
		$this->assertStringNotContainsString( 'fallback-parent.jpg', $html, 'The inherited parent photo must not replace the variation photo.' );
	}

	/**
	 * @testdox Should prime distinct variation gallery attachments in a batch before calling the main-image getter.
	 */
	public function test_collection_priming_avoids_per_variation_queries(): void {
		global $wpdb;
		$products = array();
		foreach ( range( 1, 5 ) as $index ) {
			$id = wp_insert_attachment(
				array(
					'post_mime_type' => 'image/jpeg',
					'post_title'     => 'Photo ' . $index,
				)
			);
			update_post_meta( $id, '_wp_attached_file', 'collection-' . $index . '.jpg' );
			$product = new \WC_Product_Variation();
			$product->set_gallery_image_ids( array( $id ) );
			$products[] = $product;
			clean_post_cache( $id );
		}
		$before = $wpdb->num_queries;
		wc_get_container()->get( ProductUtil::class )->prime_image_caches( $products );
		$this->assertLessThanOrEqual( 3, $wpdb->num_queries - $before, 'Cold gallery attachment reads must be batched, not one query per variation.' );
		$before = $wpdb->num_queries;
		foreach ( $products as $product ) {
			$this->assertNotEmpty( $product->get_image_id() );
		}
		$this->assertSame( $before, $wpdb->num_queries, 'Repeated main-image reads must use the primed attachment caches.' );
	}
}
