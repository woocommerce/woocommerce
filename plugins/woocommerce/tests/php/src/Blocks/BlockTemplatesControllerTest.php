<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use WC_Helper_Order;
use WC_Order;
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
	 * @testdox WooCommerce template part is resolvable through get_block_template.
	 */
	public function test_woocommerce_template_part_renders_content(): void {
		$this->create_template_part( 'test-woo-part', BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$template_part = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//test-woo-part', 'wp_template_part' );
		$this->assertSame( '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->', $template_part->content ?? null, 'Template part should contain the expected content.' );
	}

	/**
	 * @testdox Missing WooCommerce template part resolves to null.
	 */
	public function test_missing_woocommerce_template_part_resolves_to_null(): void {
		$template_part = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//non-existent-part-' . uniqid(), 'wp_template_part' );
		$this->assertNull( $template_part, 'Missing template part should resolve to null.' );
	}

	/**
	 * @testdox BC filters (render_block_data, render_block, render_block_core/template-part) fire for WooCommerce template parts.
	 */
	public function test_bc_filters_fire_for_woocommerce_template_part(): void {
		$this->sut->init();

		$this->create_template_part( 'test-filtered-part', BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$block_filter  = static fn( $block ) => array_merge_recursive( $block, ( 'core/template-part' === ( $block['blockName'] ?? null ) ? array( 'attrs' => array( 'className' => 'injected-by-filter' ) ) : array() ) );
		$output_filter = static fn( $content, $block ) => $content . ( 'core/template-part' === ( $block['blockName'] ?? null ) ? '<!-- filtered -->' : '' );
		add_filter( 'render_block_data', $block_filter, 10, 1 );
		add_filter( 'render_block', $output_filter, 10, 2 );
		add_filter( 'render_block_core/template-part', $output_filter, 10, 2 );

		$parsed_block = array(
			'blockName' => 'core/template-part',
			'attrs'     => array(
				'theme'   => 'woocommerce/woocommerce',
				'slug'    => 'test-filtered-part',
				'tagName' => 'span',
			),
		);
		$result       = $this->sut->pre_render_woocommerce_template_part( null, $parsed_block );

		remove_filter( 'render_block_data', $block_filter, 10 );
		remove_filter( 'render_block', $output_filter, 10 );
		remove_filter( 'render_block_core/template-part', $output_filter, 10 );

		$expected = '<span class="injected-by-filter wp-block-template-part"><p class="wp-block-paragraph">Test</p></span><!-- filtered --><!-- filtered -->';
		$this->assertSame( $expected, $result, 'render_block_data, render_block, and render_block_core/template-part filter output should all be included.' );
	}

	/**
	 * @testdox WooCommerce template part with className renders it in the wrapper.
	 */
	public function test_woocommerce_template_part_renders_classname_in_wrapper(): void {
		$this->sut->init();

		$this->create_template_part( 'test-classed-part', BlockTemplateUtils::PLUGIN_SLUG );
		$this->flush_block_template_caches();

		$markup = '<!-- wp:template-part {"slug":"test-classed-part","theme":"woocommerce/woocommerce","className":"my-custom-class"} /-->';
		$output = do_blocks( $markup );

		$expected = '<div class="my-custom-class wp-block-template-part"><p class="wp-block-paragraph">Test</p></div>';
		$this->assertSame( $expected, $output, 'Custom className should appear in the rendered wrapper.' );
	}

	/**
	 * @testdox The order page texturize check runs on template_redirect.
	 */
	public function test_init_registers_order_page_texturize_check(): void {
		$this->sut->init();

		$this->assertNotFalse(
			has_action( 'template_redirect', array( $this->sut, 'skip_address_texturize_on_order_pages' ) ),
			'The check should run on template_redirect, before the block template renders.'
		);
	}

	/**
	 * @testdox On the order received and view order pages, address content is not texturized, while other text is.
	 *
	 * @testWith ["order-received"]
	 *           ["view-order"]
	 *
	 * @param string $endpoint Order page endpoint.
	 */
	public function test_skips_address_texturize_on_order_pages( string $endpoint ): void {
		$this->go_to_order_page( $endpoint, WC_Helper_Order::create_order() );

		$this->sut->skip_address_texturize_on_order_pages();
		$text = $this->texturize_to_text( "<address>o'neil--shop@48x17.com</address><p>3x4 -- done</p>" );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'Address content should not be texturized.' );
		$this->assertStringContainsString( '3×4 — done', $text, 'Text outside the address should still be texturized.' );
	}

	/**
	 * @testdox Address content is texturized as usual on other pages.
	 */
	public function test_texturizes_address_on_other_pages(): void {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );

		$this->sut->skip_address_texturize_on_order_pages();

		$this->assertStringNotContainsString(
			"o'neil--shop@48x17.com",
			$this->texturize_to_text( "<address>o'neil--shop@48x17.com</address>" ),
			'Address content should be texturized.'
		);
	}

	/**
	 * @testdox On the view order page, the customer details template shows the billing email and address as entered, after kses and wptexturize().
	 */
	public function test_customer_details_template_survives_texturize_on_view_order_page(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_email( "o'neil--shop@48x17.com" );
		$order->set_billing_address_1( '12--14 Main St' );
		$order->save();
		$this->go_to_order_page( 'view-order', $order );

		$this->sut->skip_address_texturize_on_order_pages();
		$text = $this->texturize_to_text( wp_kses_post( wc_get_template_html( 'order/order-details-customer.php', array( 'order' => $order ) ) ) );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'The billing email should not be texturized.' );
		$this->assertStringContainsString( '12--14 Main St', $text, 'The billing address should not be texturized.' );
	}

	/**
	 * Load an order endpoint page, so the WooCommerce conditional tags match.
	 *
	 * @param string   $endpoint Order page endpoint.
	 * @param WC_Order $order    Order to view.
	 */
	private function go_to_order_page( string $endpoint, WC_Order $order ): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'order-received' === $endpoint ? 'woocommerce_checkout_page_id' : 'woocommerce_myaccount_page_id', $page_id );

		$this->go_to( add_query_arg( $endpoint, $order->get_id(), get_permalink( $page_id ) ) );
	}

	/**
	 * Texturize HTML and return its decoded text.
	 *
	 * @param string $html HTML to texturize.
	 * @return string
	 */
	private function texturize_to_text( string $html ): string {
		return html_entity_decode( wp_strip_all_tags( wptexturize( $html ) ), ENT_QUOTES | ENT_HTML5 );
	}

	/**
	 * Clears template ID caches so newly created posts are visible.
	 */
	private function flush_block_template_caches(): void {
		wp_cache_delete( 'wp_template-ids', 'woocommerce_blocks' );
		wp_cache_delete( 'wp_template_part-ids', 'woocommerce_blocks' );
	}
}
