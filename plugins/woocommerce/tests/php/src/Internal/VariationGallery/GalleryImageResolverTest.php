<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;

/**
 * Integration tests for the shared classic and block gallery rules.
 */
class GalleryImageResolverTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Should resolve both storefronts consistently without rewriting stored variation fields.
	 *
	 * @dataProvider gallery_cases
	 *
	 * @param string      $featured Variation featured image name.
	 * @param string[]    $gallery Variation gallery image names.
	 * @param string[]    $expected Expected storefront image names.
	 * @param string|null $filtered Image name supplied by an extension filter.
	 */
	public function test_storefront_rules( string $featured, array $gallery, array $expected, ?string $filtered = null ): void {
		$images = array(
			'none'    => 0,
			'invalid' => 999999999,
		);
		foreach ( array( 'parent', 'size', 'front', 'back' ) as $name ) {
			$images[ $name ] = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg' ) );
			update_post_meta( $images[ $name ], '_wp_attached_file', 'orphaned-' . $name . '.jpg' );
		}
		$parent = new \WC_Product_Variable();
		$parent->set_image_id( $images['parent'] );
		$parent->set_gallery_image_ids( array( $images['size'] ) );
		$parent->save();

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( '10' );
		$variation->set_image_id( $images[ $featured ] );
		$gallery_ids = array_map( static fn( $name ) => $images[ $name ], $gallery );
		$variation->set_gallery_image_ids( $gallery_ids );
		$variation->save();
		$parent->set_children( array( $variation->get_id() ) );
		$variation = wc_get_product( $variation->get_id() );

		$expected_ids = array_map( static fn( $name ) => $images[ $name ], $expected );
		$filter       = static fn() => $images[ $filtered ];
		if ( null !== $filtered ) {
			add_filter( 'woocommerce_product_variation_get_image_id', $filter );
		}
		try {
			$classic = $parent->get_available_variation( $variation );
			$block   = ProductGalleryUtils::get_product_variation_gallery_data( $parent )[ $variation->get_id() ];
		} finally {
			remove_filter( 'woocommerce_product_variation_get_image_id', $filter );
		}

		$this->assertSame( $expected_ids[0], $classic['image_id'], 'Classic should open on the expected photo.' );
		$this->assertSame( $expected_ids, $block['image_ids'], 'The new block should use the same ordered photo set.' );
		if ( 'size' === $featured ) {
			$this->assertNotEmpty( $classic['gallery_images_html'], 'An own featured image that overlaps the parent gallery must use the resolved gallery markup.' );
		}
		if ( $classic['gallery_images_html'] ) {
			preg_match_all( '/data-thumb="[^"]*\/orphaned-([a-z]+)\.jpg"/', $classic['gallery_images_html'], $matches );
			$this->assertSame( $expected, $matches[1], 'Classic replacement markup should match the block photo order.' );
		}

		$stored = wc_get_product( $variation->get_id() );
		$this->assertSame( $images[ $featured ], (int) $stored->get_image_id( 'edit' ), 'Rendering must not persist the selected fallback.' );
		$this->assertSame( $gallery_ids, $stored->get_gallery_image_ids( 'edit' ), 'Rendering must leave import/API gallery fields unchanged.' );
	}

	/**
	 * Photo combinations with literal expected results.
	 *
	 * @return array<string, array>
	 */
	public function gallery_cases(): array {
		return array(
			'no own photos'                         => array( 'none', array(), array( 'parent', 'size' ) ),
			'one gallery-only photo'                => array( 'none', array( 'front' ), array( 'front', 'size' ) ),
			'two gallery-only photos'               => array( 'none', array( 'front', 'back' ), array( 'front', 'back' ) ),
			'featured only'                         => array( 'front', array(), array( 'front', 'size' ) ),
			'featured and gallery'                  => array( 'front', array( 'back' ), array( 'front', 'back' ) ),
			'intentional parent pin'                => array( 'parent', array( 'back' ), array( 'parent', 'back' ) ),
			'invalid featured'                      => array( 'invalid', array( 'front' ), array( 'front', 'size' ) ),
			'invalid gallery'                       => array( 'none', array( 'invalid' ), array( 'parent', 'size' ) ),
			'gallery photo also in parent gallery'  => array( 'none', array( 'size' ), array( 'size' ) ),
			'featured photo also in parent gallery' => array( 'size', array(), array( 'size' ) ),
			'filtered own featured'                 => array( 'none', array( 'back' ), array( 'front', 'back' ), 'front' ),
			'filtered parent image'                 => array( 'none', array( 'front' ), array( 'front', 'size' ), 'parent' ),
		);
	}
}
