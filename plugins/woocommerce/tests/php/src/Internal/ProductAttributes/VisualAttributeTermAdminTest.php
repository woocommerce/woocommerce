<?php
/**
 * Visual attribute term admin tests.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermAdmin;
use WC_Unit_Test_Case;

/**
 * Tests for the visual attribute term admin functionality.
 */
class VisualAttributeTermAdminTest extends WC_Unit_Test_Case {

	/**
	 * Counter for unique attribute slugs within a test run.
	 *
	 * @var int
	 */
	private static $attribute_counter = 0;

	/**
	 * Get a unique suffix for test attribute slugs.
	 *
	 * @return string
	 */
	private static function get_unique_suffix(): string {
		return (string) ++self::$attribute_counter;
	}

	/**
	 * @testdox Should create 9 default color terms for a new wc-visual attribute.
	 */
	public function test_seeds_default_color_terms_for_wc_visual_attribute(): void {
		$suffix         = self::get_unique_suffix();
		$attribute_data = array(
			'name' => 'Seed Visual Test ' . $suffix,
			'slug' => 'seed-visual-test-' . $suffix,
			'type' => 'wc-visual',
		);
		$attribute_id   = wc_create_attribute( $attribute_data );

		$this->assertIsInt( $attribute_id, 'A wc-visual attribute should be created.' );

		$attribute = wc_get_attribute( $attribute_id );
		$taxonomy  = $attribute->slug;
		$term_ids  = array();

		try {
			register_taxonomy( $taxonomy, array( 'product' ) );

			VisualAttributeTermAdmin::seed_visual_attribute_terms(
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

			$black_term = get_term_by( 'slug', 'black', $taxonomy );
			$this->assertInstanceOf( \WP_Term::class, $black_term, 'Black term should be seeded with the canonical English slug.' );
			$this->assertSame( __( 'Black', 'woocommerce' ), $black_term->name, 'Term name should be the translated label, not hardcoded English.' );

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
		$suffix         = self::get_unique_suffix();
		$attribute_data = array(
			'name' => 'Seed Visual Partial ' . $suffix,
			'slug' => 'seed-visual-partial-' . $suffix,
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

			VisualAttributeTermAdmin::seed_visual_attribute_terms(
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
		$suffix         = self::get_unique_suffix();
		$attribute_data = array(
			'name' => 'Seed Select Test ' . $suffix,
			'slug' => 'seed-select-test-' . $suffix,
			'type' => 'select',
		);
		$attribute_id   = wc_create_attribute( $attribute_data );

		$this->assertIsInt( $attribute_id, 'A select attribute should be created.' );

		$attribute = wc_get_attribute( $attribute_id );
		$taxonomy  = $attribute->slug;

		try {
			register_taxonomy( $taxonomy, array( 'product' ) );

			VisualAttributeTermAdmin::seed_visual_attribute_terms(
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
}
