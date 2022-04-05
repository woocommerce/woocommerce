<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Classic Single Product class
 *
 * @internal
 */
class ClassicTemplate extends AbstractDynamicBlock {
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
	 * Initialize this block.
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'render_block', array( $this, 'add_alignment_class_to_wrapper' ), 10, 2 );
	}

	/**
	 * Render method for the Classic Template block. This method will determine which template to render.
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

		/**
		 * We need to load the scripts here because when using block templates wp_head() gets run after the block
		 * template. As a result we are trying to enqueue required scripts before we have even registered them.
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/5328#issuecomment-989013447
		 */
		if ( class_exists( 'WC_Frontend_Scripts' ) ) {
			$frontend_scripts = new \WC_Frontend_Scripts();
			$frontend_scripts::load_scripts();
		}

		$archive_templates = array( 'archive-product', 'taxonomy-product_cat', 'taxonomy-product_tag' );

		if ( 'single-product' === $attributes['template'] ) {
			return $this->render_single_product();
		} elseif ( in_array( $attributes['template'], $archive_templates, true ) ) {
			// We need to set this so that our product filters can detect if it's a PHP template.
			$this->asset_data_registry->add(
				'is_rendering_php_template',
				true,
				null
			);
			return $this->render_archive_product();
		} else {
			ob_start();

			echo "You're using the ClassicTemplate block";

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
		 * Hook: woocommerce_before_main_content
		 *
		 * Called before rendering the main content for a product.
		 *
		 * @see woocommerce_output_content_wrapper() Outputs opening DIV for the content (priority 10)
		 * @see woocommerce_breadcrumb() Outputs breadcrumb trail to the current product (priority 20)
		 * @see WC_Structured_Data::generate_website_data() Outputs schema markup (priority 30)
		 */
		do_action( 'woocommerce_before_main_content' );

		while ( have_posts() ) :

			the_post();
			wc_get_template_part( 'content', 'single-product' );

		endwhile;

		/**
		 * Hook: woocommerce_after_main_content
		 *
		 * Called after rendering the main content for a product.
		 *
		 * @see woocommerce_output_content_wrapper_end() Outputs closing DIV for the content (priority 10)
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
		 * Hook: woocommerce_before_main_content
		 *
		 * Called before rendering the main content for a product.
		 *
		 * @see woocommerce_output_content_wrapper() Outputs opening DIV for the content (priority 10)
		 * @see woocommerce_breadcrumb() Outputs breadcrumb trail to the current product (priority 20)
		 * @see WC_Structured_Data::generate_website_data() Outputs schema markup (priority 30)
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
			 * @see woocommerce_taxonomy_archive_description() Renders the taxonomy archive description (priority 10)
			 * @see woocommerce_product_archive_description() Renders the product archive description (priority 10)
			 */
			do_action( 'woocommerce_archive_description' );
			?>
		</header>
		<?php
		if ( woocommerce_product_loop() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @see woocommerce_output_all_notices() Render error notices (priority 10)
			 * @see woocommerce_result_count() Show number of results found (priority 20)
			 * @see woocommerce_catalog_ordering() Show form to control sort order (priority 30)
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
			 * @see woocommerce_pagination() Renders pagination (priority 10)
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @see wc_no_products_found() Default no products found content (priority 10)
			 */
			do_action( 'woocommerce_no_products_found' );
		}

		/**
		 * Hook: woocommerce_after_main_content
		 *
		 * Called after rendering the main content for a product.
		 *
		 * @see woocommerce_output_content_wrapper_end() Outputs closing DIV for the content (priority 10)
		 */
		do_action( 'woocommerce_after_main_content' );

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Get HTML markup with the right classes by attributes.
	 * This function appends the classname at the first element that have the class attribute.
	 * Based on the experience, all the wrapper elements have a class attribute.
	 *
	 * @param string $content Block content.
	 * @param array  $block Parsed block data.
	 * @return string Rendered block type output.
	 */
	public function add_alignment_class_to_wrapper( string $content, array $block ) {
		if ( ( 'woocommerce/' . $this->block_name ) !== $block['blockName'] ) {
			return $content;
		}

		$attributes            = (array) $block['attrs'];
		$align_class_and_style = StyleAttributesUtils::get_align_class_and_style( $attributes );

		if ( ! isset( $align_class_and_style['class'] ) ) {
			return $content;
		}

		// Find the first tag.
		$first_tag = '<[^<>]+>';
		$matches   = array();
		preg_match( $first_tag, $content, $matches );

		// If there is a tag, but it doesn't have a class attribute, add the class attribute.
		if ( isset( $matches[0] ) && strpos( $matches[0], ' class=' ) === false ) {
			$pattern_before_tag_closing = '/.+?(?=>)/';
			return preg_replace( $pattern_before_tag_closing, '$0 class="' . $align_class_and_style['class'] . '"', $content, 1 );
		}

		// If there is a tag, and it has a class already, add the class attribute.
		$pattern_get_class = '/(?<=class=\"|\')[^"|\']+(?=\"|\')/';
		return preg_replace( $pattern_get_class, '$0 ' . $align_class_and_style['class'], $content, 1 );
	}


}
