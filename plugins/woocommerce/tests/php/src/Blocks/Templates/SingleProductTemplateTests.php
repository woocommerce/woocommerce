<?php

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplate;
use WP_UnitTestCase;

/**
 * Tests the SingleProductTemplate class
 *
 */
class SingleProductTemplateTests extends WP_UnitTestCase {

	/**
	 * Test that the Product Catalog template content isn't updated mistakenly.
	 * In other words, make sure the Single Product template logic doesn't leak
	 * into other templates.
	 *
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
	 * Test that the Single Product template content isn't updated if it
	 * contains the Legacy Template block.
	 *
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
	 * Test that the Single Product template content is updated if it doesn't
	 * contain the Legacy Template block.
	 *
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

		$expected_single_product_template_without_whitespace = preg_replace(
			'/\s+/',
			'',
			$expected_single_product_template_content
		);
		$result_without_whitespace                           = preg_replace( '/\s+/', '', $result[0]->content );

		$this->assertEquals(
			$expected_single_product_template_without_whitespace,
			$result_without_whitespace
		);
	}

	/**
	 * Test that the Single Product template content isn't updated if it
	 * contains a pattern with the Legacy Template block.
	 *
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
	 * Test that the Single Product template content is updated if it doesn't
	 * contain the Legacy Template block.
	 *
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

		$expected_single_product_template_without_whitespace = preg_replace(
			'/\s+/',
			'',
			$expected_single_product_template_content
		);
		$result_without_whitespace                           = preg_replace( '/\s+/', '', $result[0]->content );

		$this->assertEquals(
			$expected_single_product_template_without_whitespace,
			$result_without_whitespace
		);
	}

	/**
	 * Test that the Single Product template installs the current product body classes.
	 *
	 * @dataProvider product_type_provider
	 *
	 * @param string $product_type Product type to create.
	 * @param string $type_class   Expected product type class.
	 */
	public function test_update_single_product_content_adds_product_body_classes( $product_type, $type_class ) {
		global $wp_filter;

		$body_class_filter_existed = isset( $wp_filter['body_class'] );
		$body_class_filter         = $body_class_filter_existed ? clone $wp_filter['body_class'] : null;
		$product_global_existed    = array_key_exists( 'product', $GLOBALS );
		$product_global            = $product_global_existed ? $GLOBALS['product'] : null;
		$post_global_existed       = array_key_exists( 'post', $GLOBALS );
		$post_global               = $post_global_existed ? $GLOBALS['post'] : null;
		$loop_global_existed       = array_key_exists( 'woocommerce_loop', $GLOBALS );
		$loop_global               = $loop_global_existed ? $GLOBALS['woocommerce_loop'] : null;
		$product                   = null;
		$variation_ids             = array();

		try {
			remove_all_filters( 'body_class' );

			$product = 'variable' === $product_type
				? \WC_Helper_Product::create_variation_product()
				: \WC_Helper_Product::create_simple_product();
			if ( $product instanceof \WC_Product_Variable ) {
				$variation_ids = $product->get_children();
			}

			$GLOBALS['product'] = $product;
			$GLOBALS['post']    = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The template reads the current post; finally restores the exact prior value.

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
			$single_product_template->update_single_product_content( array( $template ) );

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise the public filter installed by the template under test.
			$filtered_classes = apply_filters( 'body_class', $seed_classes );

			$this->assertSame( array_merge( $seed_classes, $product_classes ), $filtered_classes );
			$this->assertContains( 'product', $filtered_classes );
			$this->assertContains( $type_class, $filtered_classes );
		} finally {
			remove_all_filters( 'body_class' );
			if ( $body_class_filter_existed ) {
				$wp_filter['body_class'] = $body_class_filter; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact hook stack captured before the test.
			} else {
				unset( $wp_filter['body_class'] );
			}

			foreach ( array_reverse( $variation_ids ) as $variation_id ) {
				\WC_Helper_Product::delete_product( $variation_id );
			}

			if ( $product instanceof \WC_Product ) {
				\WC_Helper_Product::delete_product( $product->get_id() );
			}

			if ( $product_global_existed ) {
				$GLOBALS['product'] = $product_global;
			} else {
				unset( $GLOBALS['product'] );
			}

			if ( $post_global_existed ) {
				$GLOBALS['post'] = $post_global; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact frontend global captured before the test.
			} else {
				unset( $GLOBALS['post'] );
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

		$result_without_whitespace                           = preg_replace( '/\s+/', '', $result );
		$expected_single_product_template_without_whitespace = preg_replace(
			'/\s+/',
			'',
			$expected_single_product_template
		);

		$this->assertEquals(
			$result_without_whitespace,
			$expected_single_product_template_without_whitespace
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

		$result_without_whitespace                          = preg_replace( '/\s+/', '', $result );
		$result_without_whitespace_without_custom_pwbox_ids = preg_replace(
			'/pwbox-\d+/',
			'',
			$result_without_whitespace
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
			$result_without_whitespace_without_custom_pwbox_ids,
			$expected_single_product_template_without_whitespace_without_custom_pwbox_ids
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

		$result_without_whitespace                          = preg_replace( '/\s+/', '', $result );
		$result_without_whitespace_without_custom_pwbox_ids = preg_replace(
			'/pwbox-\d+/',
			'',
			$result_without_whitespace
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
			$result_without_whitespace_without_custom_pwbox_ids,
			$expected_single_product_template_without_whitespace_without_custom_pwbox_ids
		);
	}
}
