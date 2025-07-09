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

		// translators: Save as in "Save $x".
		$save_label = __( 'Save', 'woocommerce' );

		$available_on_backorder_label = __( 'Available on backorder', 'woocommerce' );

		/* translators: %d stock amount (number of items in stock for product) */
		$low_in_stock_label = __( '%d left in stock', 'woocommerce' );

		wp_interactivity_config(
			$this->get_full_block_name(),
			array(
				'reduceQuantityLabel'      => $reduce_quantity_label,
				'increaseQuantityLabel'    => $increase_quantity_label,
				'quantityDescriptionLabel' => $quantity_description_label,
				'removeFromCartLabel'      => $remove_from_cart_label,
				'lowInStockLabel'          => $low_in_stock_label,
			)
		);

		ob_start();
		?>
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
							data-wp-each--cart-item="woocommerce::state.cart.items"
							data-wp-each-key="state.cartItem.key"
						>
							<tr class="wc-block-cart-items__row" tabindex="-1">
								<td class="wc-block-cart-item__image" aria-hidden="true">
									<img data-wp-bind--hidden="!state.isProductHiddenFromCatalog" data-wp-bind--src="state.itemThumbnail" data-wp-bind--alt="state.cartItemName">
									<a data-wp-bind--hidden="state.isProductHiddenFromCatalog" data-wp-bind--href="state.cartItem.permalink" tabindex="-1">
										<img data-wp-bind--src="state.itemThumbnail" data-wp-bind--alt="state.cartItemName">	
									</a>
								</td>
								<td class="wc-block-cart-item__product">
									<div class="wc-block-cart-item__wrap">
										<span data-wp-bind--hidden="!state.isProductHiddenFromCatalog" data-wp-text="state.cartItemName" class="wc-block-components-product-name"></span>
										<a data-wp-bind--hidden="state.isProductHiddenFromCatalog" data-wp-text="state.cartItemName" data-wp-bind--href="state.cartItem.permalink" class="wc-block-components-product-name"></a>
										<div data-wp-bind--hidden="!state.cartItem.show_backorder_badge" class="wc-block-components-product-badge wc-block-components-product-backorder-badge">
											<?php echo esc_html( $available_on_backorder_label ); ?>
										</div>
										<div 
											class="wc-block-components-product-badge wc-block-components-product-low-stock-badge"
											data-wp-bind--hidden="!state.isLowInStockVisible"
											data-wp-text="state.lowInStockLabel"
										>
										</div>
										<div class="wc-block-cart-item__prices">
											<span data-wp-bind--hidden="!state.cartItemHasDiscount" class="price wc-block-components-product-price" hidden>
												<span class="screen-reader-text">
													<?php esc_html_e( 'Previous price:', 'woocommerce' ); ?>
												</span>
												<del data-wp-text="state.priceWithoutDiscount" class="wc-block-components-product-price__regular"></del>
												<span class="screen-reader-text">
													<?php esc_html_e( 'Discounted price:', 'woocommerce' ); ?>
												</span>
												<ins data-wp-text="state.itemPrice" class="wc-block-components-product-price__value is-discounted"></ins>
											</span>
											<span data-wp-bind--hidden="state.cartItemHasDiscount" class="price wc-block-components-product-price" hidden>
												<span data-wp-text="state.itemPrice" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
												</span>
											</span>
										</div>
										<div 
											data-wp-bind--hidden="!state.cartItemHasDiscount" 
											class="wc-block-components-product-badge wc-block-components-sale-badge"
											hidden
										>
											<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<?php echo $save_label; ?>
											<span
												data-wp-text="state.cartItemDiscount" 
												class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount"
											>
											</span>
										</div>
										<div class="wc-block-components-product-metadata">
											<div data-wp-watch="state.itemShortDescription" >
												<div class="wc-block-components-product-metadata__description"></div>
											</div>
										</div>
										<div class="wc-block-cart-item__quantity">
											<div class="wc-block-components-quantity-selector" data-wp-bind--hidden="state.cartItem.sold_individually">
												<input 
													data-wp-on--input="actions.overrideInvalidQuantity"
													data-wp-on--change="actions.changeQuantity" 
													data-wp-bind--aria-label="state.quantityDescriptionLabel" 
													data-wp-bind--min="state.cartItem.quantity_limits.minimum" 
													data-wp-bind--max="state.cartItem.quantity_limits.maximum"
													data-wp-bind--value="state.cartItem.quantity"
													data-wp-bind--readonly="!state.cartItem.quantity_limits.editable"
													class="wc-block-components-quantity-selector__input" 
													type="number" 
													step="1"
												>
												<button 
													data-wp-bind--disabled="state.minimumReached" 
													data-wp-on--click="actions.decrementQuantity" 
													data-wp-bind--aria-label="state.reduceQuantityLabel"
													data-wp-bind--hidden="!state.cartItem.quantity_limits.editable"
													class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus"
												>
													−
												</button>
												<button 
													data-wp-bind--disabled="state.maximumReached" 
													data-wp-on--click="actions.incrementQuantity" 
													data-wp-bind--aria-label="state.increaseQuantityLabel"
													data-wp-bind--hidden="!state.cartItem.quantity_limits.editable"
													class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus"
												>
													＋
												</button>
											</div>
											<button data-wp-on--click="actions.removeItemFromCart" data-wp-bind--aria-label="state.removeFromCartLabel" class="wc-block-cart-item__remove-link" >
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
										<div 
												data-wp-bind--hidden="!state.isLineItemTotalDiscountVisible" 
												class="wc-block-components-product-badge wc-block-components-sale-badge"
												hidden
											>
												<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<?php echo $save_label; ?>
												<span
													data-wp-text="state.lineItemDiscount" 
													class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount"
												>
												</span>
											</div>
									</div>
								</td>
							</tr>
						</template>
					</tbody>
				</table>
			</div>
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
