<?php

namespace Automattic\WooCommerce\Blocks\Tests\Templates;

use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplate;
use \WP_UnitTestCase;

/**
 * Tests the SingleProductTemplateCompatibility class
 *
 */
class SingleProductTemplateTests extends WP_UnitTestCase {

	/**
	 * Test that the password form isn't added to the Single Product Template.
	 *
	 */
	public function test_no_remove_block_when_no_single_product_is_in_the_template() {
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
	<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
	<div class="wp-block-group">
	<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
	</div>
	<!-- /wp:group -->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$result = SingleProductTemplate::add_password_form(
			$default_single_product_template
		);

		$result_without_withespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace(
			'/\s+/',
			'',
			$expected_single_product_template
		);

		$this->assertEquals(
			$result_without_withespace,
			$expected_single_product_template_without_whitespace,
			''
		);
	}

	/**
	 * Test that the password form is added to the Single Product Template.
	 */
	public function test_replace_single_product_blocks_with_input_form() {
		$default_single_product_template = '
	<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
	<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
	<div class="wp-block-group">
	<!-- wp:woocommerce/product-image-gallery {"layout":{"inherit":true,"type":"constrained"}} /-->
	</div>
	<!-- /wp:group -->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = sprintf(
			'
	<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
	<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:html -->%s<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->',
			get_the_password_form()
		);

		$result = SingleProductTemplate::add_password_form(
			$default_single_product_template
		);

		$result_without_withespace                          = preg_replace( '/\s+/', '', $result );
		$result_without_withespace_without_custom_pwbox_ids = preg_replace(
			'/pwbox-\d+/',
			'',
			$result_without_withespace
		);

		$expected_single_product_template_without_whitespace = preg_replace(
			'/\s+/',
			'',
			$expected_single_product_template
		);

		$expected_single_product_template_without_whitespace_without_custom_pwbox_ids = preg_replace(
			'/pwbox-\d+/',
			'',
			$expected_single_product_template_without_whitespace
		);

		$this->assertEquals(
			$result_without_withespace_without_custom_pwbox_ids,
			$expected_single_product_template_without_whitespace_without_custom_pwbox_ids,
			''
		);
	}

	/**
	 * Test that the password form is added to the Single Product Template with the default template.
	 */
	public function test_replace_default_template_single_product_blocks_with_input_form() {
		$default_single_product_template = '
		<!-- wp:template-part {"slug":"header"} /-->

		<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:woocommerce/breadcrumbs /-->
			<!-- wp:woocommerce/store-notices /-->

			<!-- wp:columns {"align":"wide"} -->
			<div class="wp-block-columns alignwide">
				<!-- wp:column {"width":"512px"} -->
				<div class="wp-block-column" style="flex-basis:512px">
					<!-- wp:woocommerce/product-image-gallery /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:post-title {"level": 1, "__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

					<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true} /-->

					<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true, "fontSize":"large"} /-->

					<!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-query/product-summary"} /-->

					<!-- wp:woocommerce/add-to-cart-form /-->

					<!-- wp:woocommerce/product-meta -->
					<div class="wp-block-woocommerce-product-meta">
						<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
						<div class="wp-block-group">
							<!-- wp:woocommerce/product-sku {"isDescendentOfSingleProductTemplate":true} /-->

							<!-- wp:post-terms {"term":"product_cat","prefix":"Category: "} /-->

							<!-- wp:post-terms {"term":"product_tag","prefix":"Tags: "} /-->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:woocommerce/product-meta -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->

			<!-- wp:woocommerce/product-details {"align":"wide"} /-->

			<!-- wp:woocommerce/related-products {"align":"wide"} -->
			<div class="wp-block-woocommerce-related-products alignwide">
				<!-- wp:query {"queryId":0,"query":{"perPage":5,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"flex","columns":5},"namespace":"woocommerce/related-products","lock":{"remove":true,"move":true}} -->
				<div class="wp-block-query">
					<!-- wp:heading -->
					<h2 class="wp-block-heading">Related products</h2>
					<!-- /wp:heading -->

					<!-- wp:post-template {"className":"products-block-post-template","__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
					<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->

					<!-- wp:post-title {"textAlign":"center","level":3,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

					<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->

					<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
					<!-- /wp:post-template -->
				</div>
				<!-- /wp:query -->
			</div>
			<!-- /wp:woocommerce/related-products -->
		</div>
		<!-- /wp:group -->

		<!-- wp:template-part {"slug":"footer"} /-->

		';

		$expected_single_product_template = sprintf(
			'
			<!-- wp:template-part {"slug":"header"} /-->
			<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
			<div class="wp-block-group">
			<!-- wp:woocommerce/breadcrumbs /-->
			   <!-- wp:woocommerce/store-notices /-->
			   <!-- wp:columns {"align":"wide"} -->
			   <div class="wp-block-columns alignwide">
				  <!-- wp:column {"width":"512px"} -->
				  <div class="wp-block-column" style="flex-basis:512px">
				  <!-- wp:html -->%s<!-- /wp:html -->
				  </div>
				  <!-- /wp:column -->
				  <!-- wp:column -->
				  <div class="wp-block-column">
					 <!-- wp:post-title {"level": 1, "__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->
					 <!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-query/product-summary"} /-->
				  </div>
				  <!-- /wp:column -->
			   </div>
			   <!-- /wp:columns -->
			</div>
			<!-- /wp:group -->
			<!-- wp:template-part {"slug":"footer"} /-->',
			get_the_password_form()
		);

		$result = SingleProductTemplate::add_password_form(
			$default_single_product_template
		);

		$result_without_withespace                          = preg_replace( '/\s+/', '', $result );
		$result_without_withespace_without_custom_pwbox_ids = preg_replace(
			'/pwbox-\d+/',
			'',
			$result_without_withespace
		);

		$expected_single_product_template_without_whitespace = preg_replace(
			'/\s+/',
			'',
			$expected_single_product_template
		);

		$expected_single_product_template_without_whitespace_without_custom_pwbox_ids = preg_replace(
			'/pwbox-\d+/',
			'',
			$expected_single_product_template_without_whitespace
		);

		$this->assertEquals(
			$result_without_withespace_without_custom_pwbox_ids,
			$expected_single_product_template_without_whitespace_without_custom_pwbox_ids,
			''
		);
	}
}
