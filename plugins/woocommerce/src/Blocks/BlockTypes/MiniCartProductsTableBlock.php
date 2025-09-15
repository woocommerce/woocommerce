<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartProductsTableBlock class.
 */
class MiniCartProductsTableBlock extends AbstractInnerBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-products-table-block';

	/**
	 * Render the markup for the Mini-Cart Products Table block.
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
		$head_product_label = __( 'Product', 'woocommerce' );
		$head_details_label = __( 'Details', 'woocommerce' );
		$head_total_label   = __( 'Total', 'woocommerce' );

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

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => 'wc-block-mini-cart__products-table',
				'data-wp-interactive' => $this->get_full_block_name(),
			)
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<table class="wc-block-cart-items wc-block-mini-cart-items" tabindex="-1">
				<caption class="screen-reader-text">
					<h2>
						<?php echo esc_html( $screen_reader_text ); ?>
					</h2>
				</caption>
				<thead>
					<tr class="wc-block-cart-items__header">
						<th class="wc-block-cart-items__header-image">
							<span><?php echo esc_html( $head_product_label ); ?></span>
						</th>
						<th class="wc-block-cart-items__header-product">
							<span><?php echo esc_html( $head_details_label ); ?></span>
						</th>
						<th class="wc-block-cart-items__header-total">
							<span><?php echo esc_html( $head_total_label ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody>
					<template
						data-wp-each--cart-item="woocommerce::state.cart.items"
						data-wp-each-key="state.cartItem.key"
					>
						<tr class="wc-block-cart-items__row" data-wp-run="callbacks.filterCartItemClass" tabindex="-1">
							<td data-wp-context='{ "isImageHidden": false }' class="wc-block-cart-item__image" aria-hidden="true">
								<img
									data-wp-bind--hidden="!state.isProductHiddenFromCatalog"
									data-wp-bind--src="state.itemThumbnail" 
									data-wp-bind--alt="state.cartItemName"
									data-wp-on--error="actions.hideImage"
								>
								<a data-wp-bind--hidden="state.isProductHiddenFromCatalog" data-wp-bind--href="state.cartItem.permalink" tabindex="-1">
									<img
										data-wp-bind--hidden="context.isImageHidden"
										data-wp-bind--src="state.itemThumbnail"
										data-wp-bind--alt="state.cartItemName"
										data-wp-on--error="actions.hideImage"
									>	
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
										<span data-wp-text="state.beforeItemPrice"></span>
										<span data-wp-bind--hidden="!state.cartItemHasDiscount" class="price wc-block-components-product-price">
											<span class="screen-reader-text">
												<?php esc_html_e( 'Previous price:', 'woocommerce' ); ?>
											</span>
											<del data-wp-text="state.priceWithoutDiscount" class="wc-block-components-product-price__regular"></del>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Discounted price:', 'woocommerce' ); ?>
											</span>
											<ins data-wp-text="state.itemPrice" class="wc-block-components-product-price__value is-discounted"></ins>
										</span>
										<span data-wp-bind--hidden="state.cartItemHasDiscount" class="price wc-block-components-product-price">
											<span data-wp-text="state.itemPrice" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
											</span>
										</span>
										<span data-wp-text="state.afterItemPrice"></span>
									</div>
									<div 
										data-wp-bind--hidden="!state.cartItemHasDiscount" 
										class="wc-block-components-product-badge wc-block-components-sale-badge"
									>
										<?php echo $save_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<span
											data-wp-text="state.cartItemDiscount" 
											class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount"
										>
										</span>
									</div>
									<div class="wc-block-components-product-metadata">
										<div data-wp-watch="callbacks.itemShortDescription" >
											<div class="wc-block-components-product-metadata__description"></div>
										</div>
										<?php echo $this->render_experimental_iapi_product_details_markup( 'variation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php echo $this->render_experimental_iapi_product_details_markup( 'item_data' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
										<button
											data-wp-bind--hidden="!state.itemShowRemoveItemLink"
											data-wp-on--click="actions.removeItemFromCart"
											data-wp-bind--aria-label="state.removeFromCartLabel"
											class="wc-block-cart-item__remove-link"
										>
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
		return ob_get_clean();
	}

	/**
	 * Render markup for product details.
	 *
	 * @param string $property The property to render in the product details markup.
	 * @return string Rendered product details output.
	 */
	protected function render_experimental_iapi_product_details_markup( $property ) {
		$context = array( 'dataProperty' => $property );

		ob_start();
		?>
		<div
			<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			class="wc-block-components-product-details"
			data-wp-bind--hidden="state.shouldHideSingleProductDetails"
		>
			<?php echo $this->render_experimental_iapi_product_details_item_markup( 'div' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<ul
			<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			class="wc-block-components-product-details"
			data-wp-bind--hidden="state.shouldHideMultipleProductDetails"
		>
			<template
				data-wp-each--item-data="state.cartItem.<?php echo esc_attr( $property ); ?>"
				data-wp-each-key="context.itemData.raw_attribute"
			>
				<?php echo $this->render_experimental_iapi_product_details_item_markup( 'li' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</template>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render markup for a single product detail item.
	 *
	 * @param string $tag_name The HTML tag to use for the item.
	 * @return string Rendered product detail item output.
	 */
	private function render_experimental_iapi_product_details_item_markup( $tag_name ) {
		ob_start();
		?>
		<<?php echo tag_escape( $tag_name ); ?>
			data-wp-bind--hidden="state.cartItemDataAttr.hidden"
			data-wp-bind--class="state.cartItemDataAttr.className"
		>
			<span class="wc-block-components-product-details__name" data-wp-text="state.cartItemDataAttr.name"></span>
			<span class="wc-block-components-product-details__value" data-wp-text="state.cartItemDataAttr.value"></span>
		</<?php echo tag_escape( $tag_name ); ?>>
		<?php
		return ob_get_clean();
	}
}
