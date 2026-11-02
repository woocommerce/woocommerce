<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Package;
use WP_UnitTestCase;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplateCompatibility;

/**
 * Tests the SingleProductTemplateCompatibility class
 *
 */
class SingleProductTemplateCompatibilityTests extends WP_UnitTestCase {

	/**
	 * Test that the default Single Product Template is not wrapped in a div.
	 *
	 */
	public function test_no_add_compatibility_layer_with_default_single_product_template() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}, "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group">
		   <!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template.
	 */
	public function test_add_compatibility_layer_if_contains_single_product_blocks() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		<div class="wp-block-group">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		   <div class="wp-block-group">
			  <!-- wp:woocommerce/product-image-gallery /-->
		   </div>
		   <!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template in a nested structure.
	 */
	public function test_add_compatibility_layer_if_contains_nested_single_product_blocks() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
		   <!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
		   <div class="wp-block-group alignwide">
			  <!-- wp:woocommerce/product-image-gallery /-->
		   </div>
		   <!-- /wp:group -->
		   <!-- wp:query {"queryId":2,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"displayLayout":{"type":"flex","columns":3},"namespace":"woocommerce/product-query"} -->
		   <div class="wp-block-query">
			  <!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
			  <!-- wp:woocommerce/product-image /-->
			  <!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
			  <!-- wp:woocommerce/product-price {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			  <!-- wp:woocommerce/product-button {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			  <!-- /wp:post-template -->
			  <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
			  <!-- wp:query-pagination-previous /-->
			  <!-- wp:query-pagination-numbers /-->
			  <!-- wp:query-pagination-next /-->
			  <!-- /wp:query-pagination -->
			  <!-- wp:query-no-results -->
			  <!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
			  <p></p>
			  <!-- /wp:paragraph -->
			  <!-- /wp:query-no-results -->
		   </div>
		   <!-- /wp:query -->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
			<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
			<div class="wp-block-group alignwide">
				<!-- wp:woocommerce/product-image-gallery /-->
			</div>
			<!-- /wp:group -->
			<!-- wp:query {"queryId":2,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"displayLayout":{"type":"flex","columns":3},"namespace":"woocommerce/product-query"} -->
			<div class="wp-block-query">
				<!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
				<!-- wp:woocommerce/product-image /-->
				<!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
				<!-- wp:woocommerce/product-price {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
				<!-- wp:woocommerce/product-button {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
				<!-- /wp:post-template -->
				<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
				<!-- wp:query-pagination-previous /-->
				<!-- wp:query-pagination-numbers /-->
				<!-- wp:query-pagination-next /-->
				<!-- /wp:query-pagination -->
				<!-- wp:query-no-results -->
				<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
				<p></p>
				<!-- /wp:paragraph -->
				<!-- /wp:query-no-results -->
			</div>
			<!-- /wp:query -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a pattern that contains a block related to the Single Product Template.
	 */
	public function test_add_compatibility_layer_if_contains_pattern_with_single_product_blocks() {
		register_block_pattern(
			'test-pattern',
			array(
				'title'       => 'Test Pattern',
				'description' => 'Test Pattern Description',
				'content'     => '<!-- wp:woocommerce/product-image-gallery /-->',
			)
		);

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:pattern {"slug":"test-pattern"} /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
			<!-- wp:pattern {"slug":"test-pattern"} /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a pattern that contains an inner block related to the Single Product Template.
	 */
	public function test_add_compatibility_layer_if_contains_pattern_with_inner_single_product_blocks() {
		register_block_pattern(
			'test-pattern',
			array(
				'title'       => 'Test Pattern',
				'description' => 'Test Pattern Description',
				'content'     => '<!-- wp:group --><div class="wp-block-group"><!-- wp:woocommerce/product-image-gallery /--></div><!-- wp:group /-->',
			)
		);

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:pattern {"slug":"test-pattern"} /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
			<!-- wp:pattern {"slug":"test-pattern"} /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template in a nested structure.
	 */
	public function test_add_compatibility_layer_without_a_main_wrapper() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:woocommerce/product-image-gallery /-->
		<!-- wp:query {"queryId":2,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"displayLayout":{"type":"flex","columns":3},"namespace":"woocommerce/product-query"} -->
		<div class="wp-block-query">
		   <!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
		   <!-- wp:woocommerce/product-image /-->
		   <!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
		   <!-- wp:woocommerce/product-price {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
		   <!-- wp:woocommerce/product-button {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
		   <!-- /wp:post-template -->
		   <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
		   <!-- wp:query-pagination-previous /-->
		   <!-- wp:query-pagination-numbers /-->
		   <!-- wp:query-pagination-next /-->
		   <!-- /wp:query-pagination -->
		   <!-- wp:query-no-results -->
		   <!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
		   <p></p>
		   <!-- /wp:paragraph -->
		   <!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:woocommerce/product-image-gallery /-->
		   <!-- wp:query {"queryId":2,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"]},"displayLayout":{"type":"flex","columns":3},"namespace":"woocommerce/product-query"} -->
		   <div class="wp-block-query">
			  <!-- wp:post-template {"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
			  <!-- wp:woocommerce/product-image /-->
			  <!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
			  <!-- wp:woocommerce/product-price {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			  <!-- wp:woocommerce/product-button {"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			  <!-- /wp:post-template -->
			  <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
			  <!-- wp:query-pagination-previous /-->
			  <!-- wp:query-pagination-numbers /-->
			  <!-- wp:query-pagination-next /-->
			  <!-- /wp:query-pagination -->
			  <!-- wp:query-no-results -->
			  <!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
			  <p></p>
			  <!-- /wp:paragraph -->
			  <!-- /wp:query-no-results -->
		   </div>
		   <!-- /wp:query -->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template.
	 */
	public function test_add_compatibility_layer_with_multiple_header() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:woocommerce/product-image-gallery /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}


	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template.
	 */
	public function test_add_compatibility_layer_with_multiple_footer() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:woocommerce/product-image-gallery /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template.
	 */
	public function test_add_compatibility_layer_with_multiple_blocks_related_to_the_single_product_template() {

		$default_single_product_template = '
		<!-- wp:paragraph -->
			<p>test</p>
		<!-- /wp:paragraph -->
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:woocommerce/product-image-gallery /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->
		<!-- wp:woocommerce/product-image-gallery /-->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:paragraph {"__wooCommerceIsFirstBlock":true} -->
			<p>test</p>
		<!-- /wp:paragraph -->
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product"} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template is wrapped in a div with the correct class if it contains a block related to the Single Product Template and custom HTML isn't removed.
	 */
	public function test_add_compatibility_layer_if_contains_single_product_blocks_and_custom_HTML_not_removed() {

		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<span>Custom HTML</span>
		<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		<div class="wp-block-group">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<span>Custom HTML</span>
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		   <div class="wp-block-group">
			  <!-- wp:woocommerce/product-image-gallery /-->
		   </div>
		   <!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template doesn't remove any blocks if those aren't grouped in a core/group block
	 */
	public function test_add_compatibility_layer_if_contains_blocks_not_related_to_the_single_product_template_and_not_grouped() {
		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:paragraph -->
		<p>hello</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph -->
		<p>hello1</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph -->
		<p>hello2</p>
		<!-- /wp:paragraph -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:paragraph {"__wooCommerceIsFirstBlock":true} -->
		<p>hello</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph -->
		<p>hello1</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"__wooCommerceIsLastBlock":true} -->
		<p>hello2</p>
		<!-- /wp:paragraph -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * Test that the Single Product Template has the compatibility layer when queried using the `get_templates()` function.
	 */
	public function test_add_compatibility_layer_only_once() {
		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		<div class="wp-block-group">
		   <!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = '
		<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
		<!-- wp:group {"className":"woocommerce product", "__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
		<div class="wp-block-group woocommerce product">
		   <!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		   <div class="wp-block-group">
			  <!-- wp:woocommerce/product-image-gallery /-->
		   </div>
		   <!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		// Run `add_compatibility_layer` twice to make sure we only add it once.
		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $default_single_product_template );
		$result = SingleProductTemplateCompatibility::add_compatibility_layer( $result );

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * @testdox Renders single-product hooks once after repeated setup, template selection, and hook restoration.
	 */
	public function test_shared_compatibility_renders_hooks_once_after_reinitialization(): void {
		$product_id = self::factory()->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);
		$this->go_to( get_permalink( $product_id ) );
		$this->assertTrue( is_product(), 'The compatibility layer must run in a single-product request.' );

		$rendered_product_ids = array();
		add_action(
			'woocommerce_before_single_product',
			static function () use ( &$rendered_product_ids ) {
				$rendered_product_ids[] = get_the_ID();
				echo esc_html( '__SINGLE_PRODUCT_HOOK__' );
			},
			99
		);
		$hook_snapshot = array();
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $callbacks ) {
			$hook_snapshot[ $hook_name ] = clone $callbacks;
		}
		$sut    = Package::container()->get( BlockTemplatesRegistry::class );
		$markup = SingleProductTemplateCompatibility::add_compatibility_layer( '<!-- wp:paragraph --><p>__SINGLE_PRODUCT_CONTENT__</p><!-- /wp:paragraph -->' );

		foreach ( array( 'initial', 'repeated initialization', 'repeated template selection', 'hook reset' ) as $stage ) {
			if ( 'hook reset' === $stage ) {
				$GLOBALS['wp_filter'] = $hook_snapshot; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate the hook restoration between WordPress tests.
			}
			if ( 'repeated template selection' !== $stage ) {
				$sut->init_single_product_compatibility();
			}
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress owns this filter.
			apply_filters( 'template_include', 'index.php' );
			$rendered_product_ids = array();
			$output               = do_blocks( $markup );

			$this->assertSame( array( $product_id ), $rendered_product_ids, $stage . ': the hook should fire once for the current product.' );
			$this->assertSame( 1, substr_count( $output, '__SINGLE_PRODUCT_HOOK__' ), $stage . ': hook output should not repeat.' );
			$this->assertSame( 1, substr_count( $output, '__SINGLE_PRODUCT_CONTENT__' ), $stage . ': product content should remain present.' );
		}
	}
}
