<?php
/**
 * Visual attribute term meta tests.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;
use WC_Unit_Test_Case;

/**
 * Tests for the visual attribute term meta utility.
 */
class VisualAttributeTermMetaTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should save visual attribute term color or image meta exclusively.
	 */
	public function test_saves_exclusive_values(): void {
		$term_name = 'visual-meta-test-' . wp_rand();
		$term      = wp_insert_term( $term_name, 'product_cat' );
		$term_id   = is_array( $term ) ? (int) $term['term_id'] : 0;
		$image_id  = 0;

		$this->assertNotEmpty( $term_id, 'A test term should be created.' );

		try {
			$image_id = wp_insert_attachment(
				array(
					'post_title'     => 'Visual attribute term image',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			);
			$this->assertIsInt( $image_id, 'The image should be created.' );

			update_post_meta( $image_id, '_wp_attached_file', 'visual-attribute-term-image.jpg' );

			update_term_meta( $term_id, 'image', $image_id );
			update_term_meta( $term_id, 'color', '#112233' );

			VisualAttributeTermMeta::save_term_visual( $term_id, '#aabbcc', 0 );

			$this->assertSame( '#aabbcc', get_term_meta( $term_id, 'color', true ), 'Color meta should be saved.' );
			$this->assertSame( '', get_term_meta( $term_id, 'image', true ), 'Image meta should be removed when color is saved.' );
			$this->assertSame(
				array(
					'type'  => VisualAttributeTermMeta::TYPE_COLOR,
					'value' => '#aabbcc',
				),
				VisualAttributeTermMeta::get_term_visual( $term_id ),
				'Canonical visual meta should expose saved colors as a typed value.'
			);

			VisualAttributeTermMeta::save_term_visual( $term_id, '', $image_id );

			$this->assertSame( (string) $image_id, get_term_meta( $term_id, 'image', true ), 'Image meta should be saved.' );
			$this->assertSame( '', get_term_meta( $term_id, 'color', true ), 'Color meta should be removed when image is saved.' );
			$saved_image_visual = VisualAttributeTermMeta::get_term_visual( $term_id );
			$this->assertSame( VisualAttributeTermMeta::TYPE_IMAGE, $saved_image_visual['type'], 'Canonical visual meta should expose saved images as a typed value.' );
			$this->assertStringContainsString( 'visual-attribute-term-image.jpg', $saved_image_visual['value'], 'Canonical image values should use the image URL.' );

			VisualAttributeTermMeta::save_term_visual( $term_id, '', 999999 );

			$this->assertSame( '', get_term_meta( $term_id, 'image', true ), 'Invalid image IDs should be ignored.' );
			$this->assertSame( '', get_term_meta( $term_id, 'color', true ), 'Invalid image IDs should clear existing visual meta.' );
			$this->assertSame( VisualAttributeTermMeta::get_empty_visual(), VisualAttributeTermMeta::get_term_visual( $term_id ), 'Canonical visual meta should expose invalid image IDs as empty values.' );

			update_term_meta( $term_id, 'color', '#112233' );
			update_term_meta( $term_id, 'image', $image_id );

			VisualAttributeTermMeta::save_term_visual( $term_id, '#ff00aa', $image_id );

			$this->assertSame( '', get_term_meta( $term_id, 'color', true ), 'Color meta should be removed when image takes precedence.' );
			$this->assertSame( (string) $image_id, get_term_meta( $term_id, 'image', true ), 'Image should take precedence when both values are provided.' );
		} finally {
			if ( $term_id ) {
				wp_delete_term( $term_id, 'product_cat' );
			}

			if ( $image_id ) {
				wp_delete_attachment( $image_id, true );
			}
		}
	}
}
