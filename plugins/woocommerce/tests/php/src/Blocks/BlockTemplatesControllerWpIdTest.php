<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use WC_Unit_Test_Case;

/**
 * Tests wp_id queries for the BlockTemplatesController class.
 */
class BlockTemplatesControllerWpIdTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BlockTemplatesController
	 */
	private $sut;

	/**
	 * Post IDs created during a test.
	 *
	 * @var int[]
	 */
	private $created_template_part_ids = array();

	/**
	 * Active theme before each test, restored in tearDown.
	 *
	 * @var string
	 */
	private $original_theme;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_theme = get_stylesheet();
		switch_theme( 'twentytwentyfour' );
		$this->sut = new BlockTemplatesController();
		$this->flush_block_template_caches();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->created_template_part_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$this->created_template_part_ids = array();
		$this->flush_block_template_caches();
		switch_theme( $this->original_theme );
		parent::tearDown();
	}

	/**
	 * @testdox Should not prepend customised WooCommerce template parts when querying by wp_id.
	 */
	public function test_wp_id_query_does_not_prepend_unrelated_woo_templates(): void {
		$woo_template_part   = $this->create_template_part( 'woo-custom-template-part' . uniqid(), BlockTemplateUtils::PLUGIN_SLUG );
		$theme_template_part = $this->create_template_part( 'theme-custom-template-part' . uniqid(), get_stylesheet() );
		$this->flush_block_template_caches();

		$new_template = BlockTemplateUtils::build_template_result_from_post( $theme_template_part );
		$result       = $this->sut->add_db_templates_with_woo_slug(
			array( $new_template ),
			array( 'wp_id' => $theme_template_part->ID ),
			'wp_template_part'
		);

		$this->assertNotEmpty( $result, 'A wp_id query should return the requested template part.' );
		$this->assertSame(
			$theme_template_part->ID,
			(int) $result[0]->wp_id,
			'create_item uses the first result as the created template part.'
		);

		foreach ( $result as $template ) {
			$this->assertSame(
				$theme_template_part->ID,
				(int) ( $template->wp_id ?? 0 ),
				'A wp_id query must not include unrelated WooCommerce templates.'
			);
		}

		$this->assertNotContains(
			$woo_template_part->post_name,
			array_column( $result, 'slug' ),
			'Customised WooCommerce template parts must not leak into wp_id queries for other parts.'
		);
	}

	/**
	 * @testdox Should still return a customised WooCommerce template part when queried by its own wp_id.
	 */
	public function test_wp_id_query_returns_matching_customised_woo_template(): void {
		$woo_template_part = $this->create_template_part( 'woo-custom-template-part' . uniqid(), BlockTemplateUtils::PLUGIN_SLUG );
		$this->create_template_part( 'other-woo-custom-template-part' . uniqid(), BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$result = $this->sut->add_db_templates_with_woo_slug(
			array(),
			array( 'wp_id' => $woo_template_part->ID ),
			'wp_template_part'
		);

		$this->assertNotEmpty( $result, 'Customised WooCommerce template parts must remain findable by wp_id.' );
		$this->assertSame(
			$woo_template_part->ID,
			(int) $result[0]->wp_id,
			'The matching customised WooCommerce template part should be returned first.'
		);

		foreach ( $result as $template ) {
			$this->assertSame(
				$woo_template_part->ID,
				(int) ( $template->wp_id ?? 0 ),
				'A wp_id query must not include other customised WooCommerce templates.'
			);
		}
	}

	/**
	 * @testdox Should still include customised WooCommerce template parts when the query has no wp_id.
	 */
	public function test_query_without_wp_id_includes_customised_woo_templates(): void {
		$woo_template_part = $this->create_template_part( 'woo-custom-template-part' . uniqid(), BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$result = $this->sut->add_db_templates_with_woo_slug( array(), array(), 'wp_template_part' );

		$this->assertContains(
			$woo_template_part->post_name,
			array_column( $result, 'slug' ),
			'Unfiltered queries should still surface customised WooCommerce template parts.'
		);
	}

	/**
	 * Creates a template part post attributed to a theme.
	 *
	 * @param string $slug          Post slug.
	 * @param string $theme         Theme term name.
	 * @return \WP_Post
	 */
	private function create_template_part( string $slug, string $theme ): \WP_Post {
		$term = get_term_by( 'name', $theme, 'wp_theme', ARRAY_A );
		if ( ! $term ) {
			$term = wp_insert_term( $theme, 'wp_theme' );
		}

		$post_id = wp_insert_post(
			array(
				'post_name'    => $slug,
				'post_type'    => 'wp_template_part',
				'post_title'   => $slug,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			)
		);

		wp_set_post_terms( $post_id, array( $term['term_id'] ), 'wp_theme' );
		$this->created_template_part_ids[] = $post_id;

		return get_post( $post_id );
	}

	/**
	 * Clears template ID caches so newly created posts are visible.
	 */
	private function flush_block_template_caches(): void {
		wp_cache_delete( 'wp_template-ids', 'woocommerce_blocks' );
		wp_cache_delete( 'wp_template_part-ids', 'woocommerce_blocks' );
	}
}
