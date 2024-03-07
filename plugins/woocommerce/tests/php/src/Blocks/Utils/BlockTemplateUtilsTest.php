<?php

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Migration;
use Automattic\WooCommerce\Blocks\Options;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use WP_UnitTestCase;

/**
 * Tests for the BlockTemplateUtils class.
 */
class BlockTemplateUtilsTest extends WP_UnitTestCase {

	/**
	 * Holds an instance of the dependency injection container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Setup test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Switch to a block theme and initialize template logic.
		switch_theme( 'twentytwentytwo' );
		$this->container = Package::container();
		$this->container->get( BlockTemplatesRegistry::class )->init();

		// Reset options.
		delete_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE );
		delete_option( Options::WC_BLOCK_VERSION );
	}

	/**
	 * Provides data for testing template_is_eligible_for_product_archive_fallback.
	 */
	public function provideFallbackData() {
		return array(
			array( 'taxonomy-product_cat', true ),
			array( 'taxonomy-product_tag', true ),
			array( 'taxonomy-product_attribute', true ),
			array( 'single-product', false ),
		);
	}

	/**
	 * Test template_is_eligible_for_product_archive_fallback.
	 *
	 * @param string $input    The template slug.
	 * @param bool   $expected The expected result.
	 *
	 * @dataProvider provideFallbackData
	 */
	public function test_template_is_eligible_for_product_archive_fallback( $input, $expected ) {
		$this->assertEquals( $expected, BlockTemplateUtils::template_is_eligible_for_product_archive_fallback( $input ) );
	}

	/**
	 * Test template_is_eligible_for_product_archive_fallback_from_db when the template is not eligible.
	 */
	public function test_template_is_eligible_for_product_archive_fallback_from_db_no_eligible_template() {
		$this->assertEquals( false, BlockTemplateUtils::template_is_eligible_for_product_archive_fallback_from_db( 'single-product', array() ) );
	}

	/**
	 * Test template_is_eligible_for_product_archive_fallback_from_db when the template is eligible but not in the db.
	 */
	public function test_template_is_eligible_for_product_archive_fallback_from_db_eligible_template_empty_db() {
		$this->assertEquals( false, BlockTemplateUtils::template_is_eligible_for_product_archive_fallback_from_db( 'taxonomy-product_cat', array() ) );
	}

	/**
	 * Test template_is_eligible_for_product_archive_fallback_from_db when the template is eligible and in the db.
	 */
	public function test_template_is_eligible_for_product_archive_fallback_from_db_eligible_template_custom_in_the_db() {
		$db_templates = array(
			(object) array( 'slug' => 'archive-product' ),
		);
		$this->assertEquals( true, BlockTemplateUtils::template_is_eligible_for_product_archive_fallback_from_db( 'taxonomy-product_cat', $db_templates ) );
	}

	/**
	 * Test build_template_result_from_post.
	 */
	public function test_build_template_result_from_post() {
		$theme       = BlockTemplateUtils::PLUGIN_SLUG;
		$post_fields = array(
			'ID'           => 'the_post_id',
			'post_name'    => 'the_post_name',
			'post_content' => 'the_post_content',
			'post_type'    => 'the_post_type',
			'post_excerpt' => 'the_post_excerpt',
			'post_title'   => 'the_post_title',
			'post_status'  => 'the_post_status',
		);
		$post        = $this->createPost( $post_fields, $theme );

		$template = BlockTemplateUtils::build_template_result_from_post( $post );

		$this->assertEquals( $post->ID, $template->wp_id );
		$this->assertEquals( $theme . '//' . $post_fields['post_name'], $template->id );
		$this->assertEquals( $theme, $template->theme );
		$this->assertEquals( $post_fields['post_content'], $template->content );
		$this->assertEquals( $post_fields['post_name'], $template->slug );
		$this->assertEquals( 'custom', $template->source );
		$this->assertEquals( $post_fields['post_type'], $template->type );
		$this->assertEquals( $post_fields['post_excerpt'], $template->description );
		$this->assertEquals( $post_fields['post_title'], $template->title );
		$this->assertEquals( $post_fields['post_status'], $template->status );
		$this->assertEquals( 'plugin', $template->origin );
		$this->assertTrue( $template->has_theme_file );
		$this->assertFalse( $template->is_custom );
		$this->assertEmpty( $template->post_types );
	}

	/**
	 * Test build_template_result_from_file.
	 */
	public function test_build_template_result_from_file() {
		switch_theme( 'storefront' );
		$template_file = array(
			'slug'        => 'single-product',
			'id'          => 'woocommerce/woocommerce//single-product',
			'path'        => __DIR__ . '/single-product.html',
			'type'        => 'wp_template',
			'theme'       => 'woocommerce/woocommerce',
			'source'      => 'plugin',
			'title'       => 'Single Product',
			'description' => 'Displays a single product.',
		);

		$template = BlockTemplateUtils::build_template_result_from_file( $template_file, 'wp_template' );

		$this->assertEquals( BlockTemplateUtils::PLUGIN_SLUG . '//' . $template_file['slug'], $template->id );
		$this->assertEquals( BlockTemplateUtils::PLUGIN_SLUG, $template->theme );
		$this->assertStringContainsString( '"theme":"storefront"', $template->content );
		$this->assertEquals( $template_file['source'], $template->source );
		$this->assertEquals( $template_file['slug'], $template->slug );
		$this->assertEquals( 'wp_template', $template->type );
		$this->assertEquals( $template_file['title'], $template->title );
		$this->assertEquals( $template_file['description'], $template->description );
		$this->assertEquals( 'publish', $template->status );
		$this->assertTrue( $template->has_theme_file );
		$this->assertEquals( $template_file['source'], $template->origin );
		$this->assertFalse( $template->is_custom );
		$this->assertEmpty( $template->post_types );
		$this->assertEquals( 'uncategorized', $template->area );
	}

	/**
	 * Test set_has_theme_file_if_fallback_is_available when the template file has no fallback.
	 */
	public function test_set_has_theme_file_if_fallback_is_available_no_fallback() {
		$query_result = array(
			(object) array(
				'slug'  => 'single-product',
				'theme' => 'twentytwentytwo',
			),
		);

		$template_file = (object) array(
			'slug'  => 'archive-product',
			'theme' => 'twentytwentytwo',
		);

		$this->assertFalse( BlockTemplateUtils::set_has_theme_file_if_fallback_is_available( $query_result, $template_file ) );
	}

	/**
	 * Test set_has_theme_file_if_fallback_is_available when the template file has a fallback.
	 */
	public function test_set_has_theme_file_if_fallback_is_available_with_fallback() {
		$template_file = (object) array(
			'slug'  => 'taxonomy-product_cat',
			'theme' => 'twentytwentytwo',
		);

		$query_result = array(
			(object) array(
				'slug'  => 'taxonomy-product_cat',
				'theme' => 'twentytwentytwo',
			),
		);

		$this->assertTrue( BlockTemplateUtils::set_has_theme_file_if_fallback_is_available( $query_result, $template_file ) );
	}

	/**
	 * Test create_new_block_template_object.
	 */
	public function test_create_new_block_template_object() {
		$expected_template = (object) array(
			'slug'        => 'single-product',
			'id'          => 'woocommerce/woocommerce//single-product',
			'path'        => __DIR__ . '/single-product.html',
			'type'        => 'wp_template',
			'theme'       => 'woocommerce/woocommerce',
			'source'      => 'plugin',
			'title'       => 'Single Product',
			'description' => 'Displays a single product.',
			'post_types'  => array(),
		);

		$template = BlockTemplateUtils::create_new_block_template_object(
			__DIR__ . '/single-product.html',
			'wp_template',
			'single-product',
			false
		);

		$this->assertEquals( $expected_template, $template );
	}

	/**
	 * Test remove_theme_templates_with_custom_alternative.
	 */
	public function test_remove_theme_templates_with_custom_alternative() {
		$templates = array(
			(object) array(
				'slug'   => 'single-product',
				'source' => 'theme',
			),
			(object) array(
				'slug'   => 'taxonomy-product_tag',
				'source' => 'theme',
			),
			(object) array(
				'slug'   => 'taxonomy-product_cat',
				'source' => 'theme',
			),
			(object) array(
				'slug'   => 'taxonomy-product_cat',
				'source' => 'custom',
			),
		);

		$expected_templates = array(
			(object) array(
				'slug'   => 'single-product',
				'source' => 'theme',
			),
			(object) array(
				'slug'   => 'taxonomy-product_tag',
				'source' => 'theme',
			),
			(object) array(
				'slug'   => 'taxonomy-product_cat',
				'source' => 'custom',
			),
		);

		$this->assertEquals( $expected_templates, BlockTemplateUtils::remove_theme_templates_with_custom_alternative( $templates ) );
	}

	/**
	 * Test inject_theme_attribute_in_content with no template part.
	 */
	public function test_inject_theme_attribute_in_content_with_no_template_part() {
		$template_content = '<!-- wp:woocommerce/legacy-template {"template":"archive-product"} /-->';

		$this->assertEquals( $template_content, BlockTemplateUtils::inject_theme_attribute_in_content( $template_content ) );
	}

	/**
	 * Test inject_theme_attribute_in_content with a template part.
	 */
	public function test_inject_theme_attribute_in_content_with_template_parts() {
		switch_theme( 'storefront' );
		$template_content = '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->';

		$expected_template_content = '<!-- wp:template-part {"slug":"header","tagName":"header","theme":"storefront"} /-->';

		$this->assertEquals( $expected_template_content, BlockTemplateUtils::inject_theme_attribute_in_content( $template_content ) );
	}

	/**
	 * Test a new installation with a classic theme.
	 */
	public function test_new_installation_with_a_classic_theme_should_not_use_blockified_templates() {
		switch_theme( 'storefront' );

		$this->assertFalse( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Test a new installation with a block theme.
	 */
	public function test_new_installation_with_a_block_theme_should_use_blockified_templates() {
		switch_theme( 'twentytwentytwo' );

		$this->assertTrue( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Test a new installation with a classic theme switching to a block theme.
	 */
	public function test_new_installation_with_a_classic_theme_switching_to_a_block_should_use_blockified_templates() {
		switch_theme( 'storefront' );

		switch_theme( 'twentytwentytwo' );
		check_theme_switched();

		$this->assertTrue( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Test a plugin update with a classic theme.
	 */
	public function test_plugin_update_with_a_classic_theme_should_not_use_blockified_templates() {
		switch_theme( 'storefront' );

		$this->update_plugin();

		$this->assertFalse( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Test a plugin update with a block theme.
	 */
	public function test_plugin_update_with_a_block_theme_should_not_use_blockified_templates() {
		switch_theme( 'twentytwentytwo' );

		$this->update_plugin();

		$this->assertFalse( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Test a plugin update with a classic theme switching to a block theme.
	 */
	public function test_plugin_update_with_a_classic_theme_switching_to_a_block_should_use_blockified_templates() {
		switch_theme( 'storefront' );

		$this->update_plugin();

		switch_theme( 'twentytwentytwo' );
		check_theme_switched();

		$this->assertTrue( BlockTemplateUtils::should_use_blockified_product_grid_templates() );
	}

	/**
	 * Runs the migration that happen after a plugin update
	 *
	 * @return void
	 */
	public function update_plugin(): void {
		update_option( Options::WC_BLOCK_VERSION, 1 );
		Migration::wc_blocks_update_1030_blockified_product_grid_block();
	}

	/**
	 * Creates a post with a theme term.
	 *
	 * @param array  $post Post data.
	 * @param string $theme Theme name.
	 *
	 * @return WP_Post
	 */
	private function createPost( $post, $theme ) {
		$term = wp_insert_term( $theme, 'wp_theme' );

		$post_id = wp_insert_post( $post );
		wp_set_post_terms( $post_id, array( $term['term_id'] ), 'wp_theme' );

		return get_post( $post_id );
	}
}
