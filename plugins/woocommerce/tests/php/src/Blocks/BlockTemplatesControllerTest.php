<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the BlockTemplatesController class.
 */
class BlockTemplatesControllerTest extends WC_Unit_Test_Case {

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
	private $created_post_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		switch_theme( 'twentytwentytwo' );
		$this->sut = new BlockTemplatesController();
		$this->flush_block_template_caches();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->created_post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$this->created_post_ids = array();
		$this->flush_block_template_caches();
		parent::tearDown();
	}

	/**
	 * @testdox Should not prepend customised WooCommerce template parts when querying by wp_id.
	 */
	public function test_wp_id_query_does_not_prepend_unrelated_woo_templates(): void {
		$woo_post = $this->create_template_post( 'woo-custom-' . uniqid(), 'wp_template_part', BlockTemplateUtils::PLUGIN_SLUG );
		$new_post = $this->create_template_post( 'new-part-' . uniqid(), 'wp_template_part', get_stylesheet() );
		$this->flush_block_template_caches();

		$new_template = BlockTemplateUtils::build_template_result_from_post( $new_post );
		$result       = $this->sut->add_db_templates_with_woo_slug(
			array( $new_template ),
			array( 'wp_id' => $new_post->ID ),
			'wp_template_part'
		);

		$this->assertNotEmpty( $result, 'A wp_id query should return the requested template part.' );
		$this->assertSame(
			$new_post->ID,
			(int) $result[0]->wp_id,
			'create_item uses the first result as the created template part.'
		);

		foreach ( $result as $template ) {
			$this->assertSame(
				$new_post->ID,
				(int) ( $template->wp_id ?? 0 ),
				'A wp_id query must not include unrelated WooCommerce templates.'
			);
		}

		$this->assertNotContains(
			$woo_post->post_name,
			array_column( $result, 'slug' ),
			'Customised WooCommerce template parts must not leak into wp_id queries for other parts.'
		);
	}

	/**
	 * @testdox Should still return a customised WooCommerce template part when queried by its own wp_id.
	 */
	public function test_wp_id_query_returns_matching_customised_woo_template(): void {
		$woo_post = $this->create_template_post( 'woo-custom-' . uniqid(), 'wp_template_part', BlockTemplateUtils::PLUGIN_SLUG );
		$this->create_template_post( 'other-woo-custom-' . uniqid(), 'wp_template_part', BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$result = $this->sut->add_db_templates_with_woo_slug(
			array(),
			array( 'wp_id' => $woo_post->ID ),
			'wp_template_part'
		);

		$this->assertNotEmpty( $result, 'Customised WooCommerce template parts must remain findable by wp_id.' );
		$this->assertSame(
			$woo_post->ID,
			(int) $result[0]->wp_id,
			'The matching customised WooCommerce template part should be returned first.'
		);

		foreach ( $result as $template ) {
			$this->assertSame(
				$woo_post->ID,
				(int) ( $template->wp_id ?? 0 ),
				'A wp_id query must not include other customised WooCommerce templates.'
			);
		}
	}

	/**
	 * @testdox Should still include customised WooCommerce template parts when the query has no wp_id.
	 */
	public function test_query_without_wp_id_includes_customised_woo_templates(): void {
		$woo_post = $this->create_template_post( 'woo-custom-' . uniqid(), 'wp_template_part', BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$result = $this->sut->add_db_templates_with_woo_slug( array(), array(), 'wp_template_part' );

		$this->assertContains(
			$woo_post->post_name,
			array_column( $result, 'slug' ),
			'Unfiltered queries should still surface customised WooCommerce template parts.'
		);
	}

	/**
	 * Creates a template or template part post attributed to a theme.
	 *
	 * @param string $slug          Post slug.
	 * @param string $template_type wp_template or wp_template_part.
	 * @param string $theme         Theme term name.
	 * @return \WP_Post
	 */
	private function create_template_post( string $slug, string $template_type, string $theme ): \WP_Post {
		$term = get_term_by( 'name', $theme, 'wp_theme', ARRAY_A );
		if ( ! $term ) {
			$term = wp_insert_term( $theme, 'wp_theme' );
		}

		$post_id = wp_insert_post(
			array(
				'post_name'    => $slug,
				'post_type'    => $template_type,
				'post_title'   => $slug,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			)
		);

		wp_set_post_terms( $post_id, array( $term['term_id'] ), 'wp_theme' );
		$this->created_post_ids[] = $post_id;

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
