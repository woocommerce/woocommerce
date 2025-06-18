<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartItemsBlock class.
 */
class MiniCartItemsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-items-block';

	/**
	 * Render the markup for the Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_iapi_markup( $attributes, $content, $block );
		}

		return $content;
	}

	/**
	 * Render experimental iAPI block markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_markup( $attributes, $content, $block ) {
		$screen_reader_text = __( 'Products in cart', 'woocommerce' );
		$remove_item_label  = __( 'Remove item', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$reduce_quantity_label = __( 'Reduce quantity of %s', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$increase_quantity_label = __( 'Increase quantity of %s', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$quantity_description_label = __( 'Quantity of %s in your cart', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$remove_from_cart_label = __( 'Remove %s from cart', 'woocommerce' );

		wp_interactivity_config(
			$this->get_full_block_name(),
			array(
				'reduceQuantityLabel'      => $reduce_quantity_label,
				'increaseQuantityLabel'    => $increase_quantity_label,
				'quantityDescriptionLabel' => $quantity_description_label,
				'removeFromCartLabel'      => $remove_from_cart_label,
			)
		);

		wp_interactivity_state(
			$this->get_full_block_name(),
			array(
				'cartItems' => wp_interactivity_state( 'woocommerce' )['cart']['items'] ?? [],
			)
		);

		ob_start();
		?>
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div data-wp-interactive="<?php echo esc_attr( $this->get_full_block_name() ); ?>" class="wp-block-woocommerce-mini-cart-items-block wc-block-mini-cart__items" tabindex="-1">
			<div class="wp-block-woocommerce-mini-cart-products-table-block wc-block-mini-cart__products-table">
				<table class="wc-block-cart-items wc-block-mini-cart-items" tabindex="-1">
					<caption class="screen-reader-text">
						<h2>
							<?php echo esc_html( $screen_reader_text ); ?>
						</h2>
					</caption>
					<tbody>
						<template
							data-wp-each--cart-item="state.cartItems"
							data-wp-each-key="context.cartItem.id"
						>
							<tr class="wc-block-cart-items__row" tabindex="-1">
								<td class="wc-block-cart-item__image" aria-hidden="true">
									<a data-wp-bind--href="context.cartItem.permalink" tabindex="-1">
										<img data-wp-bind--src="state.itemThumbnail" data-wp-bind--alt="state.cartItemName">	
									</a>									
								</td>
								<td class="wc-block-cart-item__product">
									<div class="wc-block-cart-item__wrap">
										<a data-wp-text="context.cartItemName" data-wp-bind--href="context.cartItem.permalink" class="wc-block-components-product-name"></a>
										<div class="wc-block-cart-item__prices">
											<span class="price wc-block-components-product-price">
												<span data-wp-text="context.itemPrice" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
												</span>
											</span>
										</div>
										<div class="wc-block-components-product-metadata">
											<div data-wp-watch="state.itemShortDescription" >
												<div class="wc-block-components-product-metadata__description"></div>
											</div>
										</div>
										<div class="wc-block-cart-item__quantity">
											<div class="wc-block-components-quantity-selector">
												<input data-wp-bind--aria-label="state.quantityDescriptionLabel" data-wp-bind--value="context.cartItem.quantity" class="wc-block-components-quantity-selector__input" type="number" step="1" min="1" max="9999" >
												<button data-wp-on--click="actions.decrementQuantity" data-wp-bind--aria-label="state.reduceQuantityLabel" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">－</button>
												<button data-wp-on--click="actions.incrementQuantity" data-wp-bind--aria-label="state.increaseQuantityLabel" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">＋</button>
											</div>
											<button data-wp-bind--aria-label="state.removeFromCartLabel" class="wc-block-cart-item__remove-link" >
												<?php echo esc_html( $remove_item_label ); ?>
											</button>
										</div>
									</div>
								</td>
								<td class="wc-block-cart-item__total">
									<div class="wc-block-cart-item__total-price-and-sale-badge-wrapper">
										<span class="price wc-block-components-product-price">
											<span data-wp-text="state.lineItemTotal" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
											</span>
										</span>
									</div>
								</td>
							</tr>
						</template>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
