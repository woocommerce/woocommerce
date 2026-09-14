<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplate;
use WP_UnitTestCase;

/**
 * Tests the SingleProductTemplate class
 *
 */
class SingleProductTemplateTests extends WP_UnitTestCase {

	/**
	 * @testdox Should not update Product Catalog template content so Single Product template logic does not leak into other templates.
	 */
	public function test_dont_update_single_product_content_for_other_templates() {
		$single_product_template                  = new SingleProductTemplate();
		$default_product_catalog_template_content = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:woocommerce/product-image-gallery /-->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$template          = new \WP_Block_Template();
		$template->slug    = 'archive-product';
		$template->title   = 'Product Catalog';
		$template->content = $default_product_catalog_template_content;
		$template->type    = 'wp_template';

		$result = $single_product_template->update_single_product_content(
			array(
				$template,
			),
		);

		$this->assertEquals(
			$default_product_catalog_template_content,
			$result[0]->content
		);
	}

	/**
	 * @testdox Should not update Single Product template content when it contains the Legacy Template block.
	 */
	public function test_dont_update_single_product_content_with_legacy_template() {
		$single_product_template                 = new SingleProductTemplate();
		$default_single_product_template_content = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$template          = new \WP_Block_Template();
		$template->slug    = 'single-product';
		$template->title   = 'Single Product';
		$template->content = $default_single_product_template_content;
		$template->type    = 'wp_template';

		$result = $single_product_template->update_single_product_content(
			array(
				$template,
			),
		);

		$this->assertEquals(
			$default_single_product_template_content,
			$result[0]->content
		);
	}

	/**
	 * @testdox Should update Single Product template content when it does not contain the Legacy Template block.
	 */
	public function test_update_single_product_content_with_legacy_template() {
		$single_product_template                  = new SingleProductTemplate();
		$default_single_product_template_content  = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:woocommerce/product-image-gallery /-->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';
		$expected_single_product_template_content = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:group {"className":"woocommerce product","__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
			<div class="wp-block-group woocommerce product">
			<!-- wp:woocommerce/product-image-gallery /-->
			</div>
			<!-- /wp:group -->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$template          = new \WP_Block_Template();
		$template->slug    = 'single-product';
		$template->title   = 'Single Product';
		$template->content = $default_single_product_template_content;
		$template->type    = 'wp_template';

		$result = $single_product_template->update_single_product_content(
			array(
				$template,
			),
		);

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template_content ),
			TemplateContentUtils::strip_whitespace( $result[0]->content )
		);
	}

	/**
	 * @testdox Should not update Single Product template content when a pattern contains the Legacy Template block.
	 */
	public function test_dont_update_single_product_content_with_legacy_template_inside_a_pattern() {
		register_block_pattern(
			'test-pattern',
			array(
				'title'       => 'Test Pattern',
				'description' => 'Test Pattern Description',
				'content'     => '<!-- wp:woocommerce/legacy-template {"template":"single-product"} /-->',
			)
		);
		$single_product_template                 = new SingleProductTemplate();
		$default_single_product_template_content = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:pattern {"slug":"test-pattern"} /-->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$template          = new \WP_Block_Template();
		$template->slug    = 'single-product';
		$template->title   = 'Single Product';
		$template->content = $default_single_product_template_content;
		$template->type    = 'wp_template';

		$result = $single_product_template->update_single_product_content(
			array(
				$template,
			),
		);

		$this->assertEquals(
			$default_single_product_template_content,
			$result[0]->content
		);
	}

	/**
	 * @testdox Should update Single Product template content when a pattern does not contain the Legacy Template block.
	 */
	public function test_update_single_product_content_with_legacy_template_inside_a_pattern() {
		register_block_pattern(
			'test-pattern',
			array(
				'title'       => 'Test Pattern',
				'description' => 'Test Pattern Description',
				'content'     => '<!-- wp:woocommerce/product-image-gallery /-->',
			)
		);
		$single_product_template                  = new SingleProductTemplate();
		$default_single_product_template_content  = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:pattern {"slug":"test-pattern"} /-->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';
		$expected_single_product_template_content = '
			<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
			<!-- wp:group {"className":"woocommerce product","__wooCommerceIsFirstBlock":true,"__wooCommerceIsLastBlock":true} -->
			<div class="wp-block-group woocommerce product">
			<!-- wp:pattern {"slug":"test-pattern"} /-->
			</div>
			<!-- /wp:group -->
			<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$template          = new \WP_Block_Template();
		$template->slug    = 'single-product';
		$template->title   = 'Single Product';
		$template->content = $default_single_product_template_content;
		$template->type    = 'wp_template';

		$result = $single_product_template->update_single_product_content(
			array(
				$template,
			),
		);

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template_content ),
			TemplateContentUtils::strip_whitespace( $result[0]->content )
		);
	}

	/**
	 * @testdox Should add the $product_type product's body classes through the filter the template installs.
	 *
	 * @dataProvider product_type_provider
	 *
	 * @param string $product_type Product type to create.
	 * @param string $type_class   Expected product type class.
	 */
	public function test_update_single_product_content_adds_product_body_classes( $product_type, $type_class ) {
		$product_global_existed = array_key_exists( 'product', $GLOBALS );
		$product_global         = $product_global_existed ? $GLOBALS['product'] : null;
		$loop_global_existed    = array_key_exists( 'woocommerce_loop', $GLOBALS );
		$loop_global            = $loop_global_existed ? $GLOBALS['woocommerce_loop'] : null;

		try {
			// The template under test installs its own body_class callback, so clear the
			// stack first to isolate it. _restore_hooks() rebuilds the stack afterwards.
			remove_all_filters( 'body_class' );

			$product = 'variable' === $product_type
				? \WC_Helper_Product::create_variation_product()
				: \WC_Helper_Product::create_simple_product();

			$GLOBALS['product'] = $product;
			$GLOBALS['post']    = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The template reads the current post; tear_down() nulls it again.

			$template          = new \WP_Block_Template();
			$template->slug    = 'single-product';
			$template->title   = 'Single Product';
			$template->content = '<!-- wp:woocommerce/product-price /-->';
			$template->type    = 'wp_template';

			$seed_classes = array( 'existing-body-class' );
			wc_reset_loop();
			$product_classes = wc_get_product_class( '', $product );
			wc_reset_loop();

			$single_product_template = new SingleProductTemplate();

			// Calling the method directly is what makes the body_class assertions below
			// deterministic, but on its own it would still pass if init() stopped wiring
			// the method up at all. Pin the registration separately.
			$single_product_template->init();
			$this->assertSame(
				11,
				has_filter( 'get_block_templates', array( $single_product_template, 'update_single_product_content' ) ),
				'init() must register the callback that installs the body_class filter.'
			);

			$single_product_template->update_single_product_content( array( $template ) );

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise the public filter installed by the template under test.
			$filtered_classes = apply_filters( 'body_class', $seed_classes );

			$this->assertSame( array_merge( $seed_classes, $product_classes ), $filtered_classes );
			$this->assertContains( 'product', $filtered_classes );
			$this->assertContains( $type_class, $filtered_classes );
		} finally {
			// tear_down() nulls $GLOBALS['post'] and rolls back the fixtures; these two
			// globals it leaves exactly as the test left them.
			if ( $product_global_existed ) {
				$GLOBALS['product'] = $product_global;
			} else {
				unset( $GLOBALS['product'] );
			}

			if ( $loop_global_existed ) {
				$GLOBALS['woocommerce_loop'] = $loop_global;
			} else {
				unset( $GLOBALS['woocommerce_loop'] );
			}
		}
	}

	/**
	 * Product types for the Single Product body class contract.
	 *
	 * @return array<string, array<string>>
	 */
	public function product_type_provider() {
		return array(
			'simple product'   => array( 'simple', 'product-type-simple' ),
			'variable product' => array( 'variable', 'product-type-variable' ),
		);
	}

	/**
	 * @testdox Should not add the password form when the template has no Single Product blocks.
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

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace( $result )
		);
	}

	/**
	 * @testdox Should add the password form to the Single Product template.
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

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $result )
		);
	}

	/**
	 * @testdox Should add the password form to the default Single Product template.
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

					<!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-query/product-summary", "excerptLength":100} /-->

					<!-- wp:woocommerce/add-to-cart-form /-->

					<!-- wp:woocommerce/product-meta -->
					<div class="wp-block-woocommerce-product-meta">
						<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
						<div class="wp-block-group">
							<!-- wp:woocommerce/product-sku /-->

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

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $result )
		);
	}

	/**
	 * @testdox Adds the password form when product blocks are at the top level of the template.
	 */
	public function test_replace_top_level_single_product_blocks_with_input_form() {
		$default_single_product_template = '
	<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
	<!-- wp:woocommerce/product-image-gallery /-->
	<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true} /-->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = sprintf(
			'
	<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
	<!-- wp:html -->%s<!-- /wp:html -->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->',
			get_the_password_form()
		);

		$result = SingleProductTemplate::add_password_form(
			$default_single_product_template
		);

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $result ),
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $expected_single_product_template )
		);
	}

	/**
	 * @testdox Adds the password form when a pattern contains Single Product blocks.
	 */
	public function test_replace_pattern_with_single_product_blocks_with_input_form() {
		register_block_pattern(
			'test-single-product-pattern',
			array(
				'title'       => 'Test Single Product Pattern',
				'description' => 'Test Pattern Description',
				'content'     => '<!-- wp:woocommerce/product-price /-->',
			)
		);

		$default_single_product_template = '
	<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
	<!-- wp:pattern {"slug":"test-single-product-pattern"} /-->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->';

		$expected_single_product_template = sprintf(
			'
	<!-- wp:template-part {"slug":"header","theme":"twentytwentythree","tagName":"header"} /-->
	<!-- wp:html -->%s<!-- /wp:html -->
	<!-- wp:template-part {"slug":"footer","theme":"twentytwentythree","tagName":"footer"} /-->',
			get_the_password_form()
		);

		$result = SingleProductTemplate::add_password_form(
			$default_single_product_template
		);

		$this->assertEquals(
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $expected_single_product_template ),
			TemplateContentUtils::strip_whitespace_and_password_form_ids( $result )
		);
	}
}
