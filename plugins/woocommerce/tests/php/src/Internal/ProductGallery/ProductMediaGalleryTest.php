<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductGallery;

use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductMediaGallery class.
 */
class ProductMediaGalleryTest extends WC_Unit_Test_Case {

	/**
	 * Enable the product gallery videos feature.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( ProductMediaGallery::ENABLE_OPTION_NAME, 'yes' );
	}

	/**
	 * Clean up feature option state.
	 */
	public function tearDown(): void {
		delete_option( ProductMediaGallery::ENABLE_OPTION_NAME );

		parent::tearDown();
	}

	/**
	 * @testdox Should merge positioned videos into gallery images.
	 */
	public function test_merges_positioned_videos_into_gallery_images(): void {
		$product = new WC_Product_Simple();
		$product->set_gallery_image_ids( array( 101, 102, 103 ) );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'id'       => 201,
					'position' => 1,
				),
				array(
					'id'       => 202,
					'position' => 2,
				),
			)
		);

		$media_items = ProductMediaGallery::get_product_media_gallery_items(
			$product,
			array(
				'include_product_image' => false,
				'resolve_video_posters' => false,
			)
		);

		$this->assertSame(
			array(
				array(
					'media_type' => 'image',
					'id'         => 101,
				),
				array(
					'media_type' => 'video',
					'id'         => 201,
				),
				array(
					'media_type' => 'video',
					'id'         => 202,
				),
				array(
					'media_type' => 'image',
					'id'         => 102,
				),
				array(
					'media_type' => 'image',
					'id'         => 103,
				),
			),
			$this->get_media_item_summary( $media_items ),
			'Videos should be placed at their final mixed gallery positions.'
		);
	}

	/**
	 * @testdox Should offset positioned videos when the product image is included.
	 */
	public function test_offsets_positioned_videos_when_product_image_is_included(): void {
		$product = new WC_Product_Simple();
		$product->set_image_id( 100 );
		$product->set_gallery_image_ids( array( 101, 102, 103 ) );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'id'       => 201,
					'position' => 1,
				),
				array(
					'id'       => 202,
					'position' => 2,
				),
			)
		);

		$media_items = ProductMediaGallery::get_product_media_gallery_items(
			$product,
			array(
				'include_product_image' => true,
				'resolve_video_posters' => false,
			)
		);

		$this->assertSame(
			array(
				array(
					'media_type' => 'image',
					'id'         => 100,
				),
				array(
					'media_type' => 'image',
					'id'         => 101,
				),
				array(
					'media_type' => 'video',
					'id'         => 201,
				),
				array(
					'media_type' => 'video',
					'id'         => 202,
				),
				array(
					'media_type' => 'image',
					'id'         => 102,
				),
				array(
					'media_type' => 'image',
					'id'         => 103,
				),
			),
			$this->get_media_item_summary( $media_items ),
			'Videos should keep positions relative to the gallery after the featured image.'
		);
	}

	/**
	 * @testdox Should extract positioned videos from a mixed gallery.
	 */
	public function test_extracts_positioned_videos_from_mixed_gallery(): void {
		$video_gallery = ProductMediaGallery::get_positioned_video_gallery_items_from_media_gallery(
			array(
				array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => 101,
				),
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => 201,
				),
				array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => 102,
				),
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => 202,
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => 201,
					'position'    => 1,
				),
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => 202,
					'position'    => 3,
				),
			),
			$video_gallery,
			'Video positions should match their indexes in the mixed gallery.'
		);
	}

	/**
	 * Get media item type and ID pairs.
	 *
	 * @param array $media_items Media items.
	 * @return array
	 */
	private function get_media_item_summary( array $media_items ): array {
		return array_map(
			static function ( array $media_item ): array {
				return array(
					'media_type' => $media_item['media_type'],
					'id'         => $media_item['id'],
				);
			},
			$media_items
		);
	}
}
