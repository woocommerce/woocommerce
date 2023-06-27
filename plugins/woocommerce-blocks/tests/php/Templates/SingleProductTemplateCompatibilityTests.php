<?php

namespace Automattic\WooCommerce\Blocks\Tests\Templates;

use \WP_UnitTestCase;
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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
			  <!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->
			  <!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
			  <!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			  <!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
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
				<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->
				<!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
				<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
				<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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
		   <!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->
		   <!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
		   <!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
		   <!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
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
			  <!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->
			  <!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
			  <!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
			  <!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
	}

		/**
		 *  @group failing
		 * Test that the Single Product Template doesn't remove any blocks if those aren't grouped in a a core/group block
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

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace( '/\s+/', '', $expected_single_product_template );

		$this->assertEquals( $result_without_withespace, $expected_single_product_template_without_whitespace, '' );
	}
}
