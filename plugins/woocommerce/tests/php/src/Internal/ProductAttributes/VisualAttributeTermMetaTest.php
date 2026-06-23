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

			VisualAttributeTermMeta::save_term_visual_by_type( $term_id, VisualAttributeTermMeta::TYPE_COLOR, '#445566', $image_id );

			$this->assertSame( '#445566', get_term_meta( $term_id, 'color', true ), 'Selected color type should save color even when image is posted.' );
			$this->assertSame( '', get_term_meta( $term_id, 'image', true ), 'Selected color type should remove stale image meta.' );

			VisualAttributeTermMeta::save_term_visual_by_type( $term_id, VisualAttributeTermMeta::TYPE_IMAGE, '#778899', $image_id );

			$this->assertSame( '', get_term_meta( $term_id, 'color', true ), 'Selected image type should remove stale color meta.' );
			$this->assertSame( (string) $image_id, get_term_meta( $term_id, 'image', true ), 'Selected image type should save image even when color is posted.' );
		} finally {
			if ( $term_id ) {
				wp_delete_term( $term_id, 'product_cat' );
			}

			if ( $image_id ) {
				wp_delete_attachment( $image_id, true );
			}
		}
	}

	/**
	 * @testdox Should create 9 default color terms for a new wc-visual attribute.
	 */
	public function test_seeds_default_color_terms_for_wc_visual_attribute(): void {
		$attribute_data = array(
			'name' => 'Seed Visual Test ' . wp_rand(),
			'slug' => 'seed-visual-test-' . wp_rand(),
			'type' => 'wc-visual',
		);
		$attribute_id   = wc_create_attribute( $attribute_data );

		$this->assertIsInt( $attribute_id, 'A wc-visual attribute should be created.' );

		$attribute = wc_get_attribute( $attribute_id );
		$taxonomy  = $attribute->slug;
		$term_ids  = array();

		try {
			register_taxonomy( $taxonomy, array( 'product' ) );

			VisualAttributeTermMeta::seed_visual_attribute_terms(
				$attribute_id,
				array(
					'attribute_name'    => $attribute_data['slug'],
					'attribute_type'    => $attribute_data['type'],
					'attribute_label'   => $attribute_data['name'],
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 0,
				)
			);

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			$this->assertIsArray( $terms, 'Terms should be returned for the taxonomy.' );
			$this->assertCount( 9, $terms, 'Nine default color terms should be created.' );

			foreach ( $terms as $term ) {
				$term_ids[] = (int) $term->term_id;
				$color      = get_term_meta( $term->term_id, 'color', true );
				$this->assertIsString( $color, 'Each default term should have a color.' );
				$this->assertMatchesRegularExpression( '/^#[0-9a-fA-F]{6}$/', $color, 'Color values should be 6-digit hex.' );
			}
		} finally {
			foreach ( $term_ids as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
			unregister_taxonomy( $taxonomy );
			wc_delete_attribute( $attribute_id );
		}
	}

	/**
	 * @testdox Should skip existing terms when seeding color terms.
	 */
	public function test_seeds_only_missing_default_color_terms(): void {
		$attribute_data = array(
			'name' => 'Seed Visual Partial ' . wp_rand(),
			'slug' => 'seed-visual-partial-' . wp_rand(),
			'type' => 'wc-visual',
		);
		$attribute_id   = wc_create_attribute( $attribute_data );

		$this->assertIsInt( $attribute_id, 'A wc-visual attribute should be created.' );

		$attribute = wc_get_attribute( $attribute_id );
		$taxonomy  = $attribute->slug;
		$term_id   = 0;
		$term_ids  = array();

		try {
			register_taxonomy( $taxonomy, array( 'product' ) );

			$term = wp_insert_term( 'Red', $taxonomy, array( 'slug' => 'red' ) );
			$this->assertIsArray( $term, 'An existing Red term should be inserted.' );

			$term_id = (int) $term['term_id'];
			update_term_meta( $term_id, 'color', '#000000' );
			$term_ids[] = $term_id;

			VisualAttributeTermMeta::seed_visual_attribute_terms(
				$attribute_id,
				array(
					'attribute_name'    => $attribute_data['slug'],
					'attribute_type'    => $attribute_data['type'],
					'attribute_label'   => $attribute_data['name'],
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 0,
				)
			);

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			$this->assertIsArray( $terms, 'Terms should be returned for the taxonomy.' );
			$this->assertCount( 9, $terms, 'Nine default color terms should exist after seeding.' );
			$this->assertSame( '#000000', get_term_meta( $term_id, 'color', true ), 'The existing Red term color should not be overwritten.' );

			foreach ( $terms as $term ) {
				$term_ids[] = (int) $term->term_id;
			}
		} finally {
			foreach ( $term_ids as $id ) {
				wp_delete_term( $id, $taxonomy );
			}
			unregister_taxonomy( $taxonomy );
			wc_delete_attribute( $attribute_id );
		}
	}

	/**
	 * @testdox Should not create color terms for non-wc-visual attribute types.
	 */
	public function test_does_not_seed_for_non_wc_visual_attribute(): void {
		$attribute_data = array(
			'name' => 'Seed Select Test ' . wp_rand(),
			'slug' => 'seed-select-test-' . wp_rand(),
			'type' => 'select',
		);
		$attribute_id   = wc_create_attribute( $attribute_data );

		$this->assertIsInt( $attribute_id, 'A select attribute should be created.' );

		$attribute = wc_get_attribute( $attribute_id );
		$taxonomy  = $attribute->slug;

		try {
			register_taxonomy( $taxonomy, array( 'product' ) );

			VisualAttributeTermMeta::seed_visual_attribute_terms(
				$attribute_id,
				array(
					'attribute_name'    => $attribute_data['slug'],
					'attribute_type'    => $attribute_data['type'],
					'attribute_label'   => $attribute_data['name'],
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 0,
				)
			);

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			$this->assertIsArray( $terms, 'get_terms should return an array.' );
			$this->assertCount( 0, $terms, 'No default color terms should be created for a select attribute.' );
		} finally {
			unregister_taxonomy( $taxonomy );
			wc_delete_attribute( $attribute_id );
		}
	}

	/**
	 * @testdox Should get visual values for multiple terms.
	 */
	public function test_gets_visual_values_for_multiple_terms(): void {
		$suffix        = (string) wp_rand();
		$color_term    = wp_insert_term( 'visual-color-' . $suffix, 'product_cat' );
		$image_term    = wp_insert_term( 'visual-image-' . $suffix, 'product_cat' );
		$empty_term    = wp_insert_term( 'visual-empty-' . $suffix, 'product_cat' );
		$color_term_id = is_array( $color_term ) ? (int) $color_term['term_id'] : 0;
		$image_term_id = is_array( $image_term ) ? (int) $image_term['term_id'] : 0;
		$empty_term_id = is_array( $empty_term ) ? (int) $empty_term['term_id'] : 0;
		$image_id      = 0;

		$this->assertNotEmpty( $color_term_id, 'A color term should be created.' );
		$this->assertNotEmpty( $image_term_id, 'An image term should be created.' );
		$this->assertNotEmpty( $empty_term_id, 'An empty term should be created.' );

		try {
			$image_id = wp_insert_attachment(
				array(
					'post_title'     => 'Visual attribute term batch image',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			);
			$this->assertIsInt( $image_id, 'The image should be created.' );

			update_post_meta( $image_id, '_wp_attached_file', 'visual-attribute-term-batch-image.jpg' );
			update_term_meta( $color_term_id, 'color', '#123456' );
			update_term_meta( $image_term_id, 'image', $image_id );

			$visuals = VisualAttributeTermMeta::get_term_visuals( array( $color_term_id, $image_term_id, $empty_term_id, $color_term_id, 0 ) );

			$this->assertCount( 3, $visuals, 'Duplicate and empty term IDs should be ignored.' );
			$this->assertSame(
				array(
					'type'  => VisualAttributeTermMeta::TYPE_COLOR,
					'value' => '#123456',
				),
				$visuals[ $color_term_id ],
				'Color terms should return a color visual value.'
			);
			$this->assertSame( VisualAttributeTermMeta::TYPE_IMAGE, $visuals[ $image_term_id ]['type'], 'Image terms should return an image visual value.' );
			$this->assertStringContainsString( 'visual-attribute-term-batch-image.jpg', $visuals[ $image_term_id ]['value'], 'Image terms should return the image URL.' );
			$this->assertSame( VisualAttributeTermMeta::get_empty_visual(), $visuals[ $empty_term_id ], 'Terms without visual meta should return an empty visual value.' );
		} finally {
			foreach ( array( $color_term_id, $image_term_id, $empty_term_id ) as $term_id ) {
				if ( $term_id ) {
					wp_delete_term( $term_id, 'product_cat' );
				}
			}

			if ( $image_id ) {
				wp_delete_attachment( $image_id, true );
			}
		}
	}
}
