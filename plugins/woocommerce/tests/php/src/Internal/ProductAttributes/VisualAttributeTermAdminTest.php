<?php
/**
 * Visual attribute term admin tests.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermAdmin;
use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;
use WC_Unit_Test_Case;

/**
 * Tests for the visual attribute term admin functionality.
 */
class VisualAttributeTermAdminTest extends WC_Unit_Test_Case {

	/**
	 * Original theme stylesheet to restore after tests.
	 *
	 * @var string|null
	 */
	private $original_theme;

	/**
	 * Set up block theme + enable wc-visual feature so 'wc-visual' type
	 * is available via the real wc_get_attribute_types() logic.
	 * Matches the approach used in other visual attribute tests.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_theme = wp_get_theme()->get_stylesheet();

		// wc-visual requires block theme + feature. Enable for the duration
		// of the test so wc_create_attribute accepts/stores type 'wc-visual'.
		switch_theme( 'twentytwentyfour' );
		delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
		wc_get_container()
			->get( \Automattic\WooCommerce\Internal\Features\FeaturesController::class )
			->change_feature_enable( 'wc-visual-attribute', true );
	}

	/**
	 * Restore original theme and clean feature option.
	 */
	public function tearDown(): void {
		if ( $this->original_theme ) {
			switch_theme( $this->original_theme );
		}
		delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
		parent::tearDown();
	}

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
				$attribute_id
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
			}

			$black_term = get_term_by( 'slug', 'black', $taxonomy );
			$this->assertInstanceOf( \WP_Term::class, $black_term, 'Black term should be seeded with the canonical English slug.' );
			$this->assertSame( __( 'Black', 'woocommerce' ), $black_term->name, 'Term name should be the translated label, not hardcoded English.' );

			$expected_colors = array(
				'black'  => '#121212',
				'white'  => '#FFFFFF',
				'gray'   => '#6E6E6E',
				'red'    => '#D32F2F',
				'blue'   => '#1976D2',
				'green'  => '#388E3C',
				'yellow' => '#FBE02D',
				'pink'   => '#EC407A',
				'brown'  => '#5D4037',
			);

			foreach ( $expected_colors as $slug => $expected_hex ) {
				$term = get_term_by( 'slug', $slug, $taxonomy );
				$this->assertInstanceOf( \WP_Term::class, $term, sprintf( 'Term with slug "%s" should exist.', $slug ) );
				$this->assertSame(
					$expected_hex,
					get_term_meta( $term->term_id, 'color', true ),
					sprintf( 'Term "%s" should have color "%s".', $slug, $expected_hex )
				);
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
	 * @testdox Should auto-register the taxonomy and seed color terms when called before the taxonomy is registered.
	 */
	public function test_seeds_when_taxonomy_is_not_registered(): void {
		$suffix         = self::get_unique_suffix();
		$attribute_data = array(
			'name' => 'Seed Visual Unregistered ' . $suffix,
			'slug' => 'seed-visual-unregistered-' . $suffix,
			'type' => 'wc-visual',
		);
		$attribute_id   = wc_create_attribute( $attribute_data );

		$this->assertIsInt( $attribute_id, 'A wc-visual attribute should be created.' );

		$taxonomy = wc_attribute_taxonomy_name( $attribute_data['slug'] );
		$term_ids = array();

		// Pre-assert the fallback branch is actually exercised; if WC auto-registers
		// the taxonomy on init the test would silently bypass the code under test.
		$this->assertFalse(
			taxonomy_exists( $taxonomy ),
			'Taxonomy should not be registered before the seeder runs; otherwise the fallback branch is not exercised.'
		);

		try {
			VisualAttributeTermAdmin::seed_visual_attribute_terms(
				$attribute_id
			);

			$this->assertTrue( taxonomy_exists( $taxonomy ), 'Seeder should register the taxonomy when missing.' );

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			$this->assertIsArray( $terms, 'Terms should be returned for the taxonomy.' );
			$this->assertCount( 9, $terms, 'Nine default color terms should be seeded after auto-registering the taxonomy.' );

			foreach ( $terms as $term ) {
				$term_ids[] = (int) $term->term_id;
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
				$attribute_id
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
	 * @testdox Should not throw a TypeError when the current screen has a non-string id.
	 *
	 * Reproduces issue #66528: Visual Composer calls do_action('admin_enqueue_scripts', NULL),
	 * which triggers enqueue_visual_attribute_script() in a context where get_current_screen()
	 * may return a screen object whose id is an integer (post ID) rather than a string,
	 * causing strpos(): Argument #1 ($haystack) must be of type string.
	 */
	public function test_does_not_fatal_on_non_string_screen_id(): void {
		// We need the wc-visual attribute type available, same setup as other tests.
		switch_theme( 'twentytwentyfour' );
		delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
		wc_get_container()
			->get( \Automattic\WooCommerce\Internal\Features\FeaturesController::class )
			->change_feature_enable( 'wc-visual-attribute', true );

		$instance = wc_get_container()->get( VisualAttributeTermAdmin::class );

		// Simulate the scenario where get_current_screen() returns a screen with an integer id
		// (e.g. a post ID), as happens when third-party code triggers admin_enqueue_scripts
		// from outside the normal admin context.
		$screen = \WP_Screen::get( 'test-screen' );
		// Non-string, integer id like in the reported error.
		$screen->id = 35202; // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.IncorrectWarning

		$original_screen           = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		$GLOBALS['current_screen'] = $screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		try {
			// This should not throw a TypeError even though $screen->id is an integer.
			$instance->enqueue_visual_attribute_script();
			// If we reach here, the method handled the non-string id gracefully.
			$this->assertTrue( true );
		} catch ( \TypeError $e ) {
			$this->fail( 'TypeError thrown: ' . $e->getMessage() );
		} finally {
			$GLOBALS['current_screen'] = $original_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			delete_option( 'woocommerce_feature_wc_visual_attribute_enabled' );
			switch_theme( $this->original_theme );
		}
	}
}
