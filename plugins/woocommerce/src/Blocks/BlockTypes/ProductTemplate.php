<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use WP_Block;

/**
 * ProductTemplate class.
 */
class ProductTemplate extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-template';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 * - Hook into pre_render_block to update the query.
	 */
	protected function initialize() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_block_type_metadata_settings' ), 10, 2 );
		parent::initialize();
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$query = ProductCollectionUtils::prepare_and_execute_query( $block );

		if ( ! $query->have_posts() ) {
			return '';
		}

		if ( $this->block_core_post_template_uses_featured_image( $block->inner_blocks ) ) {
			update_post_thumbnail_cache( $query );
		}

		$classnames = '';
		if ( isset( $block->context['displayLayout'] ) && isset( $block->context['query'] ) ) {
			$classnames = 'is-product-collection-layout-' . $block->context['displayLayout']['type'] . ' ';

			if ( isset( $block->context['displayLayout']['type'] ) && 'flex' === $block->context['displayLayout']['type'] ) {
				if ( isset( $block->context['displayLayout']['shrinkColumns'] ) && $block->context['displayLayout']['shrinkColumns'] ) {
					$classnames = "wc-block-product-template__responsive columns-{$block->context['displayLayout']['columns']}";
				} else {
					$classnames = "is-flex-container columns-{$block->context['displayLayout']['columns']}";
				}
			}
		}

		if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
			$classnames .= ' has-link-color';
		}

		$classnames .= ' wc-block-product-template';

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'              => trim( $classnames ),
				'data-wp-on--scroll' => 'actions.watchScroll',
				'data-wp-init'       => 'callbacks.initResizeObserver',
			)
		);

		// T9 DEMO (boundary-breaking use case E14/E48, PR #65570): does any
		// card carry a Product Quantity (stepper) block? When it does, each card
		// needs a seeded cart draft so the stepper edits that card's draft and the
		// Add to Cart button posts it — the same mechanics the Add to Cart +
		// Options form uses, but outside the form. The card's product identity
		// comes from the `<li>`'s `woocommerce/products` context (T12). Detected
		// once for the whole template (all cards share the same inner-block
		// structure).
		$has_quantity_stepper = $this->inner_blocks_contain_quantity_stepper( $block->inner_blocks );

		if ( $has_quantity_stepper ) {
			// Ensure the shared `woocommerce/cart` state (including the
			// `draftItems` slot) exists so per-card draft seeding has somewhere
			// to land (draft "birth"). Mirrors what the Add to Cart + Options
			// block relies on.
			BlocksSharedState::load_cart_state( 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce' );
		}

		$content = '';
		while ( $query->have_posts() ) {
			$query->the_post();

			// Get an instance of the current Post Template block.
			$block_instance = $block->parsed_block;
			$product_id     = (int) get_the_ID();
			$post_type      = get_post_type();

			// Set the block name to one that does not correspond to an existing registered block.
			// This ensures that for the inner instances of the Post Template block, we do not render any block supports.
			$block_instance['blockName'] = 'core/null';

			$filter_block_context = static function ( $context ) use ( $product_id, $post_type ) {
				$context['postType'] = $post_type;
				$context['postId']   = $product_id;
				return $context;
			};

			// Use an early priority so that other 'render_block_context' filters have access to the values.
			add_filter( 'render_block_context', $filter_block_context, 1 );
			// Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
			// `render_callback` and ensure that no wrapper markup is included.
			$block_content = (
				new WP_Block(
					$block_instance,
					$block->context
				)
			)->render( array( 'dynamic' => false ) );
			remove_filter( 'render_block_context', $filter_block_context, 1 );

			// Load product into the shared products store.
			wc_interactivity_api_load_product(
				'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
				$product_id
			);
			$product_context_directive = wp_interactivity_data_wp_context(
				array(
					'productId'   => $product_id,
					'variationId' => null,
				),
				'woocommerce/products'
			);

			$li_directives = '
				data-wp-interactive="woocommerce/product-collection"
				' . $product_context_directive . '
				data-wp-key="product-item-' . $product_id . '"
			';

			// T9 DEMO: when a stepper is present, seed each card's draft (min
			// quantity). The stepper edits this draft (keyed by the card's product
			// id — identity rule 3, landmine #2) and the Add to Cart button posts
			// it via `woocommerce/cart::actions.addItem()`.
			//
			// No shared-context wrapper is needed (T12): the `<li>` already carries
			// the `woocommerce/products::{ productId }` context, which is now THE
			// product-identity source. The stepper resolves the card's product id
			// via `mainProductInContext` (`getContextProductId()`), and the cart
			// store keys the draft off that derived id. This also removes the WP
			// ≤ 6.8 two-contexts-on-one-element hazard the earlier nested
			// `woocommerce` wrapper worked around.
			if ( $has_quantity_stepper ) {
				$this->seed_card_draft( $product_id );
			}

			// Wrap the render inner blocks in a `li` element with the appropriate post classes.
			$post_classes = implode( ' ', get_post_class( 'wc-block-product' ) );
			$content     .= strtr(
				'<li class="{classes}"
					{li_directives}
				>
					{content}
				</li>',
				array(
					'{classes}'       => esc_attr( $post_classes ),
					'{li_directives}' => $li_directives,
					'{content}'       => $block_content,
				)
			);
		}

		/*
		* Use this function to restore the context of the template tags
		* from a secondary query loop back to the main query loop.
		* Since we use two custom loops, it's safest to always restore.
		*/
		wp_reset_postdata();

		return sprintf(
			'<ul %1$s>%2$s</ul>',
			$wrapper_attributes,
			$content
		);
	}

	/**
	 * Determines whether a block list contains a block that uses the featured image.
	 *
	 * @param WP_Block_List $inner_blocks Inner block instance.
	 *
	 * @return bool Whether the block list contains a block that uses the featured image.
	 */
	protected function block_core_post_template_uses_featured_image( $inner_blocks ) {
		foreach ( $inner_blocks as $block ) {
			if ( 'core/post-featured-image' === $block->name ) {
				return true;
			}
			if (
			'core/cover' === $block->name &&
			! empty( $block->attributes['useFeaturedImage'] )
			) {
				return true;
			}
			if ( $block->inner_blocks && block_core_post_template_uses_featured_image( $block->inner_blocks ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * T9 DEMO: whether the card's inner blocks include the Product Quantity
	 * (stepper) block. Recurses so the stepper can sit anywhere in the card.
	 *
	 * When true, each card gets a seeded draft (see render()); the card's product
	 * identity comes from the `<li>`'s `woocommerce/products` context (T12).
	 * Detected once per template render — all cards share the same inner-block
	 * structure.
	 *
	 * @param \WP_Block_List|array $inner_blocks Inner block instances.
	 * @return bool True if a quantity-selector block is present.
	 */
	protected function inner_blocks_contain_quantity_stepper( $inner_blocks ): bool {
		foreach ( $inner_blocks as $inner_block ) {
			if ( 'woocommerce/add-to-cart-with-options-quantity-selector' === $inner_block->name ) {
				return true;
			}
			if ( $inner_block->inner_blocks && $this->inner_blocks_contain_quantity_stepper( $inner_block->inner_blocks ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * T9 DEMO: seed a card's cart draft into the shared `woocommerce/cart`
	 * interactivity state (draft "birth"), keyed by the product id (identity
	 * rule 3). Read-modify-write, mirroring
	 * AddToCartWithOptions::seed_cart_drafts: `wp_interactivity_state()` merges
	 * with `array_replace_recursive`, which would clobber sibling cards' drafts
	 * if we blind-seeded a fresh array. Index-by-id keeps it idempotent.
	 *
	 * Only simple, purchasable, in-stock products get a draft here — the demo
	 * targets the grocery/quick-add pattern. Products with options (variable,
	 * grouped) are out of scope for this stretch demo (they need the full form).
	 *
	 * @param int $product_id The card's product id.
	 * @return void
	 */
	protected function seed_card_draft( int $product_id ): void {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product || $product->has_options() || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		$draft = array(
			'id'       => $product_id,
			'quantity' => $product->get_min_purchase_quantity(),
		);

		$cart_state      = wp_interactivity_state( 'woocommerce/cart' );
		$existing_drafts = isset( $cart_state['draftItems'] ) && is_array( $cart_state['draftItems'] )
			? $cart_state['draftItems']
			: array();

		// Index existing drafts by product id so we replace-by-id (idempotent)
		// rather than duplicate when the same product renders more than once.
		$drafts_by_id = array();
		foreach ( $existing_drafts as $existing_draft ) {
			if ( isset( $existing_draft['id'] ) ) {
				$drafts_by_id[ (int) $existing_draft['id'] ] = $existing_draft;
			}
		}
		$drafts_by_id[ $product_id ] = $draft;

		wp_interactivity_state(
			'woocommerce/cart',
			array( 'draftItems' => array_values( $drafts_by_id ) )
		);
	}

	/**
	 * Product Template renders inner blocks manually so we need to skip default
	 * rendering routine for its inner blocks
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 * @return array
	 */
	public function add_block_type_metadata_settings( $settings, $metadata ) {
		if ( ! empty( $metadata['name'] ) && 'woocommerce/product-template' === $metadata['name'] ) {
			$settings['skip_inner_blocks'] = true;
		}
		return $settings;
	}
}
