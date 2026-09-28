<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductDataUtils;
use Automattic\WooCommerce\Blocks\Utils\Utils as BlocksUtils;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * SingleProduct class.
 */
class SingleProduct extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'single-product';

	/**
	 * Post temporarily replaced while rendering a product title or excerpt.
	 *
	 * @var \WP_Post|null|false
	 */
	private $previous_post = false;

	/**
	 * Initialize the block and Hook into the `render_block_context` filter
	 * to update the context with the correct data.
	 *
	 * @var string
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'render_block_context', [ $this, 'update_context' ], 10, 3 );
		add_filter( 'render_block_core/post-excerpt', [ $this, 'restore_global_post' ], 10, 3 );
		add_filter( 'render_block_core/post-title', [ $this, 'restore_global_post' ], 10, 3 );
	}

	/**
	 * Restore the post that was current before rendering a product title or excerpt.
	 *
	 * @param  string    $block_content  The block content.
	 * @param  array     $parsed_block  The full block, including name and attributes.
	 * @param  \WP_Block $block_instance  The block instance.
	 *
	 * @return mixed
	 */
	public function restore_global_post( $block_content, $parsed_block, $block_instance ) {
		if ( false !== $this->previous_post ) {
			global $post;
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact enclosing post, including secondary queries.
			$post                = $this->previous_post;
			$this->previous_post = false;
		}

		return $block_content;
	}

	/**
	 * Set the selected product context and let WordPress propagate it to inner blocks.
	 *
	 * @param array    $context Block context.
	 * @param array    $block Block attributes.
	 * @param WP_Block $parent_block Block instance.
	 *
	 * @return array Updated block context.
	 */
	public function update_context( $context, $block, $parent_block ) {
		if ( 'woocommerce/single-product' === $block['blockName']
			&& isset( $block['attrs']['productId'] ) ) {
			$context['postId']        = $block['attrs']['productId'];
			$context['postType']      = 'product';
			$context['singleProduct'] = true;
		}

		$this->replace_post_for_single_product_inner_block( $block, $context );

		return $context;
	}

	/**
	 * Use the resolved product context for core blocks that read the global post.
	 *
	 * @param array $block Block attributes.
	 * @param array $context Block context.
	 */
	protected function replace_post_for_single_product_inner_block( $block, &$context ) {
		if ( ! in_array( $block['blockName'], array( 'core/post-title', 'core/post-excerpt' ), true ) ) {
			return;
		}

		global $post;
		$this->previous_post = $post;
		if ( empty( $context['postId'] ) ) {
			return;
		}

		$product_post = get_post( $context['postId'] );
		if ( ! $product_post instanceof \WP_Post || 'product' !== $product_post->post_type ) {
			return;
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Core Post Title reads get_the_title() without a post ID.
		$post = $product_post;
	}

	/**
	 * Render the Single Product block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$product = wc_get_product( $block->context['postId'] );

		if (
			! $product instanceof \WC_Product ||
			! $product->is_viewable()
		) {
			return '';
		}

		$product_id = $product->get_id();

		if ( post_password_required( $product_id ) ) {
			$password_form = get_the_password_form( $product_id );
			$html          = new \WP_HTML_Tag_Processor( $password_form );
			$current_url   = BlocksUtils::get_current_page_url();

			while ( $html->next_tag( array( 'tag_name' => 'input' ) ) ) {
				if ( 'redirect_to' !== $html->get_attribute( 'name' ) ) {
					continue;
				}

				$html->set_attribute( 'value', $current_url );
				break;
			}

			return $html->get_updated_html();
		}

		// Load product into the shared products store.
		wc_interactivity_api_load_product(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			$product_id
		);

		$interactivity_context = array(
			'productId'   => $product_id,
			'variationId' => null,
		);

		$html = new \WP_HTML_Tag_Processor( $content );

		if ( $html->next_tag( array( 'tag_name' => 'div' ) ) ) {
			$html->set_attribute( 'data-wp-interactive', $this->get_full_block_name() );
			$html->set_attribute( 'data-wp-context', 'woocommerce/products::' . wp_json_encode( $interactivity_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
		}

		$updated_html = $html->get_updated_html();

		return parent::render( $attributes, $updated_html, $block );
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 *
	 * @return null This block has no frontend script.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
