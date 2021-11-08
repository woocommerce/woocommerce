<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Legacy Single Product class
 *
 * @internal
 */
class LegacyTemplate extends AbstractDynamicBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'legacy-template';

	/**
	 * API version.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * Render method for the Legacy Template block. This method will determine which template to render.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 *
	 * @return string | void Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		if ( null === $attributes['template'] ) {
			return;
		}

		$archive_templates = array( 'archive-product', 'taxonomy-product_cat', 'taxonomy-product_tag' );

		if ( 'single-product' === $attributes['template'] ) {
			return $this->render_single_product();
		} elseif ( in_array( $attributes['template'], $archive_templates, true ) ) {
			return $this->render_archive_product();
		} else {
			ob_start();

			echo "You're using the LegacyTemplate block";

			wp_reset_postdata();
			return ob_get_clean();
		}
	}

	/**
	 * Render method for the single product template and parts.
	 *
	 * @return string Rendered block type output.
	 */
	protected function render_single_product() {
		ob_start();

		/**
		 * Woocommerce_before_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		do_action( 'woocommerce_before_main_content' );

		while ( have_posts() ) :

			the_post();
			wc_get_template_part( 'content', 'single-product' );

		endwhile;

		/**
		 * Woocommerce_after_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Render method for the archive product template and parts.
	 *
	 * @return string Rendered block type output.
	 */
	protected function render_archive_product() {
		ob_start();

		/**
		 * Hook: woocommerce_before_main_content.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 * @hooked WC_Structured_Data::generate_website_data() - 30
		 */
		do_action( 'woocommerce_before_main_content' );

		?>
		<header class="woocommerce-products-header">
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>

			<?php
			/**
			 * Hook: woocommerce_archive_description.
			 *
			 * @hooked woocommerce_taxonomy_archive_description - 10
			 * @hooked woocommerce_product_archive_description - 10
			 */
			do_action( 'woocommerce_archive_description' );
			?>
		</header>
		<?php
		if ( woocommerce_product_loop() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @hooked woocommerce_output_all_notices - 10
			 * @hooked woocommerce_result_count - 20
			 * @hooked woocommerce_catalog_ordering - 30
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();

					/**
					 * Hook: woocommerce_shop_loop.
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			/**
			 * Hook: woocommerce_after_shop_loop.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );
		}

		/**
		 * Hook: woocommerce_after_main_content.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );

		wp_reset_postdata();
		return ob_get_clean();
	}
}
