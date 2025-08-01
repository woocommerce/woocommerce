<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;

/**
 * ProductButton class.
 */
class ProductButton extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;
	use BlocksSharedState;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-button';


	/**
	 * Cart.
	 *
	 * @var array
	 */
	private static $cart = null;

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return array( 'query', 'queryId', 'postId' );
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
		if ( wp_is_block_theme() ) {
			add_action(
				'wp_enqueue_scripts',
				array( $this, 'dequeue_add_to_cart_scripts' )
			);
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
		// This workaround ensures that WordPress loads the core/button block styles.
		// For more details, see https://github.com/woocommerce/woocommerce/pull/53052.
		( new \WP_Block( array( 'blockName' => 'core/button' ) ) )->render();

		global $product;
		$previous_product = $product;

		// Try to load the product from the block context, if not available,
		// use the global $product.
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$post    = $post_id ? wc_get_product( $post_id ) : null;
		if ( $post instanceof \WC_Product ) {
			$product = $post;
		} elseif ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$is_descendant_of_add_to_cart_form = isset( $block->context['woocommerce/isDescendantOfAddToCartWithOptions'] ) ? $block->context['woocommerce/isDescendantOfAddToCartWithOptions'] : false;

		if ( $is_descendant_of_add_to_cart_form && Utils::is_not_purchasable_product( $product ) ) {
			$product = $previous_product;

			return '';
		}

		$this->register_cart_interactivity( 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce' );

		$number_of_items_in_cart  = $this->get_cart_item_quantities_by_product_id( $product->get_id() );
		$is_product_purchasable   = $this->is_product_purchasable( $product );
		$cart_redirect_after_add  = get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes';
		$ajax_add_to_cart_enabled = get_option( 'woocommerce_enable_ajax_add_to_cart' ) === 'yes';
		$is_ajax_button           = $ajax_add_to_cart_enabled && ! $cart_redirect_after_add && ( $is_descendant_of_add_to_cart_form || $product->supports( 'ajax_add_to_cart' ) ) && $is_product_purchasable;
		$html_element             = $is_ajax_button || ( $is_descendant_of_add_to_cart_form && 'external' !== $product->get_type() ) ? 'button' : 'a';
		$styles_and_classes       = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$classname                = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );
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

		if ( ! $is_descendant_of_add_to_cart_form ) {
			/**
			 * Filters the change the quantity to add to cart.
			 *
			 * @since 8.5.0
			 * @param number $default_quantity The default quantity.
			 * @param number $product_id The product id.
			 */
			$default_quantity = apply_filters( 'woocommerce_add_to_cart_quantity', $default_quantity, $product->get_id() );
		}

		$add_to_cart_text = null !== $product->add_to_cart_text() ? $product->add_to_cart_text() : __( 'Add to cart', 'woocommerce' );

		if ( $is_descendant_of_add_to_cart_form && null !== $product->single_add_to_cart_text() ) {
			$add_to_cart_text = $product->single_add_to_cart_text();
		}

		$context = array(
			'quantityToAdd'    => $default_quantity,
			'productId'        => $product->get_id(),
			'productType'      => $product->get_type(),
			'addToCartText'    => $add_to_cart_text,
			'tempQuantity'     => $number_of_items_in_cart,
			'animationStatus'  => 'IDLE',
			'inTheCartText'    => $this->get_in_the_cart_text( $product ),
			'noticeId'         => '',
			'hasPressedButton' => false,
		);

		if ( $product->is_type( 'grouped' ) ) {
			$context['groupedProductIds'] = $product->get_children();
		}

		$attributes = array(
			'type' => $is_descendant_of_add_to_cart_form ? 'submit' : 'button',
		);

		if ( 'a' === $html_element ) {
			$attributes = array(
				'href' => esc_url( $product->add_to_cart_url() ),
				'rel'  => 'nofollow',
			);
		}

		wp_interactivity_state(
			'woocommerce/product-button',
			array(
				'addToCartText' => function () {
					$context = wp_interactivity_get_context();
					$quantity = $context['tempQuantity'];
					$add_to_cart_text = $context['addToCartText'];

					return $quantity > 0 ? sprintf(
						/* translators: %s: product number. */
						__( '%s in cart', 'woocommerce' ),
						$quantity
					) : $add_to_cart_text;
				},
			)
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
				'attributes' => array_merge(
					$attributes,
					array(
						'data-product_id'  => $product->get_id(),
						'data-product_sku' => $product->get_sku(),
						'aria-label'       => ! $is_descendant_of_add_to_cart_form || 'simple' === $product->get_type() ? $product->add_to_cart_description() : null,
					),
				),
			),
			$product
		);

		if ( isset( $args['attributes']['aria-label'] ) ) {
			$args['attributes']['aria-label'] = wp_strip_all_tags( $args['attributes']['aria-label'] );
		}

		$div_directives = '
			data-wp-interactive="woocommerce/product-button"
			data-wp-init="actions.refreshCartItems"
		';

		$context_directives = wp_interactivity_data_wp_context( $context );

		$button_directives = $is_descendant_of_add_to_cart_form ?
			'data-wp-class--disabled="woocommerce/add-to-cart-with-options::!state.isFormValid"' :
			'data-wp-on--click="actions.addCartItem"';
		$anchor_directive  = $is_descendant_of_add_to_cart_form ? '' : 'data-wp-on--click="woocommerce/product-collection::actions.viewProduct"';

		$span_button_directives = '
			data-wp-text="state.addToCartText"
			data-wp-class--wc-block-slide-in="state.slideInAnimation"
			data-wp-class--wc-block-slide-out="state.slideOutAnimation"
			data-wp-on--animationend="actions.handleAnimationEnd"
			data-wp-watch="callbacks.startAnimation"
			data-wp-run="callbacks.syncTempQuantityOnLoad"
			data-wp-on--click="actions.handlePressedState"
		';

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode(
					' ',
					array_filter(
						array(
							'wp-block-button wc-block-components-product-button',
							esc_attr( $classname . ' ' . $custom_width_classes . ' ' . $custom_align_classes ),
						)
					)
				),
			)
		);

		$button_classes = isset( $args['class'] ) ? esc_attr( $args['class'] . ' wc-interactive' ) : 'wc-interactive';
		if ( $is_descendant_of_add_to_cart_form ) {
			$button_classes             .= ' single_add_to_cart_button';
			$args['attributes']['value'] = $product->get_id();
		}

		$html = strtr(
			'<div {wrapper_attributes}
					{div_directives}
					{context_directives}
				>
					<{html_element}
						class="{button_classes}"
						style="{button_styles}"
						{attributes}
						{button_directives}
					>
					<span {span_button_directives}>{add_to_cart_text}</span>
					</{html_element}>
					{view_cart_html}
				</div>',
			array(
				'{wrapper_attributes}'     => $wrapper_attributes,
				'{html_element}'           => $html_element,
				'{button_classes}'         => $button_classes,
				'{context_directives}'     => $context_directives,
				'{button_styles}'          => esc_attr( $styles_and_classes['styles'] ),
				'{attributes}'             => isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
				'{add_to_cart_text}'       => $is_ajax_button ? '' : $add_to_cart_text,
				'{div_directives}'         => $is_ajax_button ? $div_directives : '',
				'{button_directives}'      => $is_ajax_button ? $button_directives : $anchor_directive,
				'{span_button_directives}' => $is_ajax_button ? $span_button_directives : '',
				'{view_cart_html}'         => $is_ajax_button && CartCheckoutUtils::has_cart_page() ? $this->get_view_cart_html() : '',
			)
		);

		if ( ! $is_descendant_of_add_to_cart_form ) {
			/**
			 * Filters the add to cart button class.
			 *
			 * @since 8.7.0
			 *
			 * @param string $class The class.
			 */
			$html = apply_filters(
				'woocommerce_loop_add_to_cart_link',
				$html,
				$product,
				$args
			);
		}

		$product = $previous_product;

		return $html;
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
	 * Check if a product is purchasable.
	 *
	 * @param \WC_Product $product The product.
	 * @return boolean The product is purchasable.
	 */
	private function is_product_purchasable( $product ) {
		if ( $product->is_type( 'grouped' ) ) {
			$grouped_product_ids = $product->get_children();
			foreach ( $grouped_product_ids as $child ) {
				$child_product = wc_get_product( $child );
				if ( ! $child_product instanceof \WC_Product ) {
					continue;
				}
				if ( $child_product->is_purchasable() && $child_product->is_in_stock() ) {
					return true;
				}
			}

			return false;
		}

		return $product->is_purchasable() && $product->is_in_stock();
	}

	/**
	 * Get the inTheCartText text for a given product.
	 *
	 * @param \WC_Product $product The product.
	 * @return string The inTheCartText string.
	 */
	private function get_in_the_cart_text( $product ) {
		if ( $product->is_type( 'grouped' ) ) {
			return __( 'Added to cart', 'woocommerce' );
		}

		return sprintf(
			/* translators: %s: product number. */
			__( '%s in cart', 'woocommerce' ),
			'###'
		);
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
				data-wp-bind--hidden="!state.displayViewCart"
			>
				<a
					href="%1$s"
					class="added_to_cart wc_forward"
					title="%2$s"
				>
					%3$s
				</a>
			</span>',
			esc_url( wc_get_cart_url() ),
			esc_attr__( 'View cart', 'woocommerce' ),
			esc_html__( 'View cart', 'woocommerce' )
		);
	}
}
