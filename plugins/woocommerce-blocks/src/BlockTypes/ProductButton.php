<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductButton class.
 */
class ProductButton extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-button';


	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-interactivity-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-interactivity-frontend' ),
			'dependencies' => [ 'wc-interactivity' ],
		];

		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		parent::enqueue_assets( $attributes, $content, $block );
		if ( wc_current_theme_is_fse_theme() ) {
			add_action(
				'wp_enqueue_scripts',
				array( $this, 'dequeue_add_to_cart_scripts' )
			);
		} else {
			$this->dequeue_add_to_cart_scripts();
		}
	}

	/**
	 * Dequeue the add-to-cart script.
	 * The block uses Interactivity API, it isn't necessary enqueue the add-to-cart script.
	 */
	public function dequeue_add_to_cart_scripts() {
		wp_dequeue_script( 'wc-add-to-cart' );
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		wc_initial_state(
			'woocommerce/product-button',
			array(
				'inTheCartText' => sprintf(
					/* translators: %s: product number. */
					__( '%s in cart', 'woo-gutenberg-products-block' ),
					'###'
				),
			)
		);

		if ( $product ) {
			$number_of_items_in_cart = $this->get_cart_item_quantities_by_product_id( $product->get_id() );
			$more_than_one_item      = $number_of_items_in_cart > 0;
			$initial_product_text    = $more_than_one_item ? sprintf(
			/* translators: %s: product number. */
				__( '%s in cart', 'woo-gutenberg-products-block' ),
				$number_of_items_in_cart
			) : $product->add_to_cart_text();
			$cart_redirect_after_add  = get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes';
			$ajax_add_to_cart_enabled = get_option( 'woocommerce_enable_ajax_add_to_cart' ) === 'yes';
			$is_ajax_button           = $ajax_add_to_cart_enabled && ! $cart_redirect_after_add && $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock();
			$html_element             = $is_ajax_button ? 'button' : 'a';
			$styles_and_classes       = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
			$classname                = $attributes['className'] ?? '';
			$custom_width_classes     = isset( $attributes['width'] ) ? 'has-custom-width wp-block-button__width-' . $attributes['width'] : '';
			$custom_align_classes     = isset( $attributes['textAlign'] ) ? 'align-' . $attributes['textAlign'] : '';
			$html_classes             = implode(
				' ',
				array_filter(
					array(
						'wp-block-button__link',
						'wp-element-button',
						'wc-block-components-product-button__button',
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
						$is_ajax_button ? 'ajax_add_to_cart' : '',
						'product_type_' . $product->get_type(),
						esc_attr( $styles_and_classes['classes'] ),
					)
				)
			);

			$default_quantity = 1;
			/**
			* Filters the change the quantity to add to cart.
			*
			* @since 10.9.0
			* @param number $default_quantity The default quantity.
			* @param number $product_id The product id.
			*/
			$quantity_to_add = apply_filters( 'woocommerce_add_to_cart_quantity', $default_quantity, $product->get_id() );

			$context = array(
				'quantityToAdd'          => $quantity_to_add,
				'productId'              => $product->get_id(),
				'addToCartText'          => null !== $product->add_to_cart_text() ? $product->add_to_cart_text() : __( 'Add to cart', 'woo-gutenberg-products-block' ),
				'temporaryNumberOfItems' => $number_of_items_in_cart,
				'animationStatus'        => 'IDLE',
			);

			/**
			 * Allow filtering of the add to cart button arguments.
			 *
			 * @since 9.7.0
			 */
			$args = apply_filters(
				'woocommerce_loop_add_to_cart_args',
				array(
					'class'      => $html_classes,
					'attributes' => array(
						'data-product_id'  => $product->get_id(),
						'data-product_sku' => $product->get_sku(),
						'aria-label'       => $product->add_to_cart_description(),
						'rel'              => 'nofollow',
					),
				),
				$product
			);

			if ( isset( $args['attributes']['aria-label'] ) ) {
				$args['attributes']['aria-label'] = wp_strip_all_tags( $args['attributes']['aria-label'] );
			}

			if ( isset( WC()->cart ) && ! WC()->cart->is_empty() ) {
				$this->prevent_cache();
			}

			$interactive = array(
				'namespace' => 'woocommerce/product-button',
			);

			$div_directives = '
				data-wc-interactive=\'' . wp_json_encode( $interactive, JSON_NUMERIC_CHECK ) . '\'
				data-wc-context=\'' . wp_json_encode( $context, JSON_NUMERIC_CHECK ) . '\'
			';

			$button_directives = '
				data-wc-on--click="actions.addToCart"
				data-wc-class--loading="context.isLoading"
			';

			$span_button_directives = '
				data-wc-text="state.addToCartText"
				data-wc-class--wc-block-slide-in="state.slideInAnimation"
				data-wc-class--wc-block-slide-out="state.slideOutAnimation"
				data-wc-on--animationend="actions.handleAnimationEnd"
				data-wc-watch="callbacks.startAnimation"
				data-wc-layout-init="callbacks.syncTemporaryNumberOfItemsOnLoad"
			';

			/**
			 * Filters the add to cart button class.
			 *
			 * @since 8.7.0
			 *
			 * @param string $class The class.
			 */
			return apply_filters(
				'woocommerce_loop_add_to_cart_link',
				strtr(
					'<div class="wp-block-button wc-block-components-product-button {classes} {custom_classes}"
						{div_directives}
					>
						<{html_element}
							href="{add_to_cart_url}"
							class="{button_classes}"
							style="{button_styles}"
							{attributes}
							{button_directives}
						>
						<span {span_button_directives}> {add_to_cart_text} </span>
						</{html_element}>
						{view_cart_html}
					</div>',
					array(
						'{classes}'                => esc_attr( $text_align_styles_and_classes['class'] ?? '' ),
						'{custom_classes}'         => esc_attr( $classname . ' ' . $custom_width_classes . ' ' . $custom_align_classes ),
						'{html_element}'           => $html_element,
						'{add_to_cart_url}'        => esc_url( $product->add_to_cart_url() ),
						'{button_classes}'         => isset( $args['class'] ) ? esc_attr( $args['class'] ) : '',
						'{button_styles}'          => esc_attr( $styles_and_classes['styles'] ),
						'{attributes}'             => isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
						'{add_to_cart_text}'       => esc_html( $initial_product_text ),
						'{div_directives}'         => $is_ajax_button ? $div_directives : '',
						'{button_directives}'      => $is_ajax_button ? $button_directives : '',
						'{span_button_directives}' => $is_ajax_button ? $span_button_directives : '',
						'{view_cart_html}'         => $is_ajax_button ? $this->get_view_cart_html() : '',
					)
				),
				$product,
				$args
			);
		}
	}

	/**
	 * Get the number of items in the cart for a given product id.
	 *
	 * @param number $product_id The product id.
	 * @return number The number of items in the cart.
	 */
	private function get_cart_item_quantities_by_product_id( $product_id ) {
		if ( ! isset( WC()->cart ) ) {
			return 0;
		}

		$cart = WC()->cart->get_cart_item_quantities();
		return isset( $cart[ $product_id ] ) ? $cart[ $product_id ] : 0;
	}

	/**
	 * Prevent caching on certain pages
	 */
	private function prevent_cache() {
		\WC_Cache_Helper::set_nocache_constants();
		nocache_headers();
	}

	/**
	 * Get the view cart link html.
	 *
	 * @return string The view cart html.
	 */
	private function get_view_cart_html() {
		return sprintf(
			'<span
				hidden
				data-wc-bind--hidden="!state.displayViewCart"
			>
				<a
					href="%1$s"
					class="added_to_cart wc_forward"
					title="%2$s"
				>
					%2$s
				</a>
			</span>',
			wc_get_cart_url(),
			__( 'View cart', 'woo-gutenberg-products-block' )
		);
	}
}
