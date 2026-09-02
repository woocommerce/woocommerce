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
		$product   = new WC_Product_Simple();
		$image_ids = array(
			$this->create_attachment( 'Image A', 'image/jpeg' ),
			$this->create_attachment( 'Image B', 'image/jpeg' ),
			$this->create_attachment( 'Image C', 'image/jpeg' ),
		);
		$video_ids = array(
			$this->create_attachment( 'Video 1', 'video/mp4' ),
			$this->create_attachment( 'Video 2', 'video/mp4' ),
		);

		$product->set_gallery_image_ids( $image_ids );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'id'       => $video_ids[0],
					'position' => 1,
				),
				array(
					'id'       => $video_ids[1],
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
					'id'         => $image_ids[0],
				),
				array(
					'media_type' => 'video',
					'id'         => $video_ids[0],
				),
				array(
					'media_type' => 'video',
					'id'         => $video_ids[1],
				),
				array(
					'media_type' => 'image',
					'id'         => $image_ids[1],
				),
				array(
					'media_type' => 'image',
					'id'         => $image_ids[2],
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
		$product          = new WC_Product_Simple();
		$product_image_id = $this->create_attachment( 'Product image', 'image/jpeg' );
		$image_ids        = array(
			$this->create_attachment( 'Image A', 'image/jpeg' ),
			$this->create_attachment( 'Image B', 'image/jpeg' ),
			$this->create_attachment( 'Image C', 'image/jpeg' ),
		);
		$video_ids        = array(
			$this->create_attachment( 'Video 1', 'video/mp4' ),
			$this->create_attachment( 'Video 2', 'video/mp4' ),
		);

		$product->set_image_id( $product_image_id );
		$product->set_gallery_image_ids( $image_ids );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'id'       => $video_ids[0],
					'position' => 1,
				),
				array(
					'id'       => $video_ids[1],
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
					'id'         => $product_image_id,
				),
				array(
					'media_type' => 'image',
					'id'         => $image_ids[0],
				),
				array(
					'media_type' => 'video',
					'id'         => $video_ids[0],
				),
				array(
					'media_type' => 'video',
					'id'         => $video_ids[1],
				),
				array(
					'media_type' => 'image',
					'id'         => $image_ids[1],
				),
				array(
					'media_type' => 'image',
					'id'         => $image_ids[2],
				),
			),
			$this->get_media_item_summary( $media_items ),
			'Videos should keep positions relative to the gallery after the featured image.'
		);
	}

	/**
	 * @testdox Should clamp positioned videos when gallery images are removed.
	 */
	public function test_clamps_positioned_videos_when_gallery_images_are_removed(): void {
		$product   = new WC_Product_Simple();
		$image_ids = array(
			$this->create_attachment( 'Image A', 'image/jpeg' ),
			$this->create_attachment( 'Image B', 'image/jpeg' ),
			$this->create_attachment( 'Image C', 'image/jpeg' ),
		);
		$video_id  = $this->create_attachment( 'Video', 'video/mp4' );

		$product->set_gallery_image_ids( $image_ids );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'id'       => $video_id,
					'position' => 3,
				),
			)
		);

		$product->set_gallery_image_ids( array( $image_ids[0] ) );

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
					'id'         => $image_ids[0],
				),
				array(
					'media_type' => 'video',
					'id'         => $video_id,
				),
			),
			$this->get_media_item_summary( $media_items ),
			'Videos should move to the end when their stored position is past the remaining images.'
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

	/**
	 * Create a test attachment.
	 *
	 * @param string $title     Attachment title.
	 * @param string $mime_type Attachment MIME type.
	 * @return int
	 */
	private function create_attachment( string $title, string $mime_type ): int {
		return wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => $mime_type,
			)
		);
	}
}
