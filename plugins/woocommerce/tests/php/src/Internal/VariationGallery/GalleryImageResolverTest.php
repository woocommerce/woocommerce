<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * Integration tests for the shared classic and block gallery rules.
 */
class GalleryImageResolverTest extends \WC_Unit_Test_Case {

	/**
	 * Attachment IDs keyed by the photo names used in each test.
	 *
	 * @var array<string, int>
	 */
	private $images;

	/**
	 * Parent product with a main photo and a size guide.
	 *
	 * @var \WC_Product_Variable
	 */
	private $parent;

	/**
	 * Create the shared product and photo fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->images = array(
			'none'    => 0,
			'invalid' => 999999999,
		);
		foreach ( array( 'parent', 'size', 'front', 'back' ) as $name ) {
			$this->images[ $name ] = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg' ) );
			update_post_meta( $this->images[ $name ], '_wp_attached_file', 'orphaned-' . $name . '.jpg' );
		}
		$this->images['pdf'] = wp_insert_attachment( array( 'post_mime_type' => 'application/pdf' ) );
		update_post_meta( $this->images['pdf'], '_wp_attached_file', 'orphaned-pdf.pdf' );
		wp_update_attachment_metadata(
			$this->images['pdf'],
			array(
				'sizes' => array(
					'full' => array(
						'file'      => 'orphaned-pdf.jpg',
						'width'     => 600,
						'height'    => 800,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);

		$this->parent = new \WC_Product_Variable();
		$this->parent->set_image_id( $this->images['parent'] );
		$this->parent->set_gallery_image_ids( array( $this->images['size'] ) );
		$this->parent->save();
	}

	/**
	 * @testdox Should use the parent gallery when the variation has no valid gallery of its own.
	 * @testWith ["none", [], ["parent", "size"]]
	 *           ["front", [], ["front", "size"]]
	 *           ["none", ["invalid"], ["parent", "size"]]
	 *           ["pdf", [], ["pdf", "size"]]
	 *
	 * @param string   $featured Featured photo name, or none.
	 * @param string[] $gallery Variation gallery photo names.
	 * @param string[] $expected Photos to display, in order.
	 */
	public function test_parent_gallery_fallback( string $featured, array $gallery, array $expected ): void {
		$variation               = $this->create_variation( $featured, $gallery );
		list( $classic, $block ) = $this->get_storefront_data( $variation );

		$this->assertSame( $this->images[ $expected[0] ], $classic['image_id'], 'Classic should open on the expected photo.' );
		$this->assertSame( $this->image_ids_for( $expected ), $block['image_ids'], 'The block should retain the parent gallery extras.' );
		$this->assertSame( '', $classic['gallery_images_html'], 'Classic should keep its existing parent gallery markup.' );
	}

	/**
	 * @testdox Should replace the classic gallery with the same ordered photos shown by the block.
	 * @testWith ["none", ["front"], ["front", "size"]]
	 *           ["none", ["front", "back"], ["front", "back"]]
	 *           ["front", ["back"], ["front", "back"]]
	 *           ["parent", ["back"], ["parent", "back"]]
	 *           ["invalid", ["front"], ["front", "size"]]
	 *           ["none", ["size"], ["size"]]
	 *           ["size", [], ["size"]]
	 *           ["none", ["pdf"], ["pdf", "size"]]
	 *           ["none", ["pdf", "back"], ["pdf", "back"]]
	 *           ["front", ["pdf"], ["front", "pdf"]]
	 *
	 * @param string   $featured Featured photo name, or none.
	 * @param string[] $gallery Variation gallery photo names.
	 * @param string[] $expected Photos to display, in order.
	 */
	public function test_replacement_gallery( string $featured, array $gallery, array $expected ): void {
		$variation               = $this->create_variation( $featured, $gallery );
		list( $classic, $block ) = $this->get_storefront_data( $variation );

		$this->assertSame( $this->images[ $expected[0] ], $classic['image_id'], 'Classic should open on the expected photo.' );
		$this->assertSame( $this->image_ids_for( $expected ), $block['image_ids'], 'The block should display the expected photo order.' );
		$this->assertSame( $expected, $this->photo_names_from_html( $classic['gallery_images_html'] ), 'Classic replacement markup should match the block photo order.' );
	}

	/**
	 * @testdox Should accept a filter-supplied variation photo while excluding the inherited parent photo.
	 * @testWith ["front", ["back"], ["front", "back"]]
	 *           ["parent", ["front"], ["front", "size"]]
	 *
	 * @param string   $filtered Featured photo supplied by the filter.
	 * @param string[] $gallery Variation gallery photo names.
	 * @param string[] $expected Photos to display, in order.
	 */
	public function test_filtered_featured_image( string $filtered, array $gallery, array $expected ): void {
		$variation = $this->create_variation( 'none', $gallery );
		$image_id  = $this->images[ $filtered ];
		add_filter( 'woocommerce_product_variation_get_image_id', static fn() => $image_id );

		list( $classic, $block ) = $this->get_storefront_data( $variation );

		$this->assertSame( $this->images[ $expected[0] ], $classic['image_id'], 'Classic should distinguish a filtered variation photo from the parent fallback.' );
		$this->assertSame( $this->image_ids_for( $expected ), $block['image_ids'], 'The block should apply the same featured-image rule.' );
		$this->assertSame( $expected, $this->photo_names_from_html( $classic['gallery_images_html'] ), 'Classic replacement markup should include the expected photos.' );
	}

	/**
	 * @testdox Should leave saved featured and gallery fields unchanged when rendering either storefront.
	 * @testWith ["none", ["front"]]
	 *           ["none", ["front", "back"]]
	 *           ["parent", ["back"]]
	 *           ["invalid", ["front"]]
	 *
	 * @param string   $featured Saved featured photo name, or none.
	 * @param string[] $gallery Saved gallery photo names.
	 */
	public function test_storefront_reads_leave_saved_images_unchanged( string $featured, array $gallery ): void {
		$variation = $this->create_variation( $featured, $gallery );

		$this->get_storefront_data( $variation );

		$stored = new \WC_Product_Variation( $variation->get_id() );
		$this->assertSame( $this->images[ $featured ], (int) $stored->get_image_id( 'edit' ), 'Rendering must not change the saved featured image.' );
		$this->assertSame( $this->image_ids_for( $gallery ), $stored->get_gallery_image_ids( 'edit' ), 'Rendering must not change the saved gallery.' );
	}

	/**
	 * @testdox Should retain PDF previews in the parent fallback gallery.
	 */
	public function test_parent_pdf_preview(): void {
		$this->parent->set_image_id( $this->images['pdf'] );
		$this->parent->save();
		$variation = $this->create_variation( 'none', array() );

		list( $classic, $block ) = $this->get_storefront_data( $variation );

		$this->assertFalse( wp_attachment_is_image( $this->images['pdf'] ), 'The fixture must be a PDF, rather than an image attachment.' );
		$this->assertSame( $this->images['pdf'], $classic['image_id'] );
		$this->assertStringEndsWith( '/orphaned-pdf.jpg', $classic['image']['src'] );
		$this->assertSame( $this->image_ids_for( array( 'pdf', 'size' ) ), $block['image_ids'] );
	}

	/**
	 * @testdox Should not use a PDF without an image preview as a storefront image.
	 */
	public function test_pdf_without_preview(): void {
		delete_post_meta( $this->images['pdf'], '_wp_attachment_metadata' );
		$variation = $this->create_variation( 'none', array( 'pdf' ) );

		list( $classic, $block ) = $this->get_storefront_data( $variation );

		$this->assertSame( $this->images['parent'], $classic['image_id'] );
		$this->assertSame( $this->image_ids_for( array( 'parent', 'size' ) ), $block['image_ids'] );
	}

	/**
	 * Create a variation and reload it so inherited parent data is available.
	 *
	 * @param string   $featured Featured photo name, or none.
	 * @param string[] $gallery Gallery photo names.
	 * @return \WC_Product_Variation The saved variation.
	 */
	private function create_variation( string $featured, array $gallery ): \WC_Product_Variation {
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $this->parent->get_id() );
		$variation->set_regular_price( '10' );
		$variation->set_image_id( $this->images[ $featured ] );
		$variation->set_gallery_image_ids( $this->image_ids_for( $gallery ) );
		$variation->save();
		$this->parent->set_children( array( $variation->get_id() ) );

		return new \WC_Product_Variation( $variation->get_id() );
	}

	/**
	 * Read the classic and block gallery responses for the variation.
	 *
	 * @param \WC_Product_Variation $variation The variation to render.
	 * @return array{0: array, 1: array} Classic response followed by the block response.
	 */
	private function get_storefront_data( \WC_Product_Variation $variation ): array {
		return array(
			$this->parent->get_available_variation( $variation ),
			ProductGalleryUtils::get_product_variation_gallery_data( $this->parent )[ $variation->get_id() ],
		);
	}

	/**
	 * Convert fixture photo names to attachment IDs, preserving order.
	 *
	 * @param string[] $names Photo names.
	 * @return int[] Attachment IDs.
	 */
	private function image_ids_for( array $names ): array {
		return array_map( fn( $name ) => $this->images[ $name ], $names );
	}

	/**
	 * Read the fixture photo order from classic gallery thumbnails.
	 *
	 * @param string $html Classic gallery markup.
	 * @return string[] Photo names in thumbnail order.
	 */
	private function photo_names_from_html( string $html ): array {
		preg_match_all( '/data-thumb="[^"]*\/orphaned-([a-z]+)\.jpg"/', $html, $matches );

		return $matches[1];
	}
}
