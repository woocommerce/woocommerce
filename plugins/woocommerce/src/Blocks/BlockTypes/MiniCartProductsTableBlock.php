<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
		$screen_reader_text = __( 'Products in cart', 'woocommerce' );
		$remove_item_label  = __( 'Remove item', 'woocommerce' );
		$head_product_label = __( 'Product', 'woocommerce' );
		$head_details_label = __( 'Details', 'woocommerce' );
		$head_total_label   = __( 'Total', 'woocommerce' );

		wp_interactivity_state(
			$this->get_full_block_name(),
			array(
				'cartItem' => function () {
					$context = wp_interactivity_get_context( 'woocommerce' );
					$cart_state = wp_interactivity_state( 'woocommerce' );
					$item_key = $context['cartItem']['key'];

					foreach ( $cart_state['cart']['items'] as $item ) {
						if ( $item['key'] === $item_key ) {
							return $item;
						}
					}

					return null;
				},
			)
		);

		// translators: %s is the name of the product in cart.
		$reduce_quantity_label = __( 'Reduce quantity of %s', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$increase_quantity_label = __( 'Increase quantity of %s', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$quantity_description_label = __( 'Quantity of %s in your cart.', 'woocommerce' );

		// translators: %s is the name of the product in cart.
		$remove_from_cart_label = __( 'Remove %s from cart', 'woocommerce' );

		/* translators: %s is the discount amount. */
		$save_format             = __( 'Save %s', 'woocommerce' );
		$line_item_discount_span = '<span data-wp-text="state.lineItemDiscount" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount"></span>';
		$line_item_save_badge    = sprintf( $save_format, $line_item_discount_span );

		$available_on_backorder_label = __( 'Available on backorder', 'woocommerce' );

		wp_interactivity_config(
			$this->get_full_block_name(),
			array(
				'reduceQuantityLabel'      => $reduce_quantity_label,
				'increaseQuantityLabel'    => $increase_quantity_label,
				'quantityDescriptionLabel' => $quantity_description_label,
				'removeFromCartLabel'      => $remove_from_cart_label,
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
						<tr
							class="wc-block-cart-items__row"
							data-wp-bind--hidden="!state.cartItem.key"
							data-wp-run="callbacks.filterCartItemClass"
							tabindex="-1"
						>
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
									<div class="wc-block-cart-item__prices">
										<span data-wp-bind--hidden="!state.cartItemHasDiscount" class="price wc-block-components-product-price">
											<span data-wp-text="state.beforeItemPrice"></span>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Previous price:', 'woocommerce' ); ?>
											</span>
											<del data-wp-text="state.priceWithoutDiscount" class="wc-block-components-product-price__regular"></del>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Discounted price:', 'woocommerce' ); ?>
											</span>
											<ins data-wp-text="state.itemPrice" class="wc-block-components-product-price__value is-discounted"></ins>
											<span data-wp-text="state.afterItemPrice"></span>
										</span>
										<span data-wp-bind--hidden="state.cartItemHasDiscount" class="price wc-block-components-product-price">
											<span data-wp-text="state.beforeItemPrice"></span>
											<span data-wp-text="state.itemPrice" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
											</span>
											<span data-wp-text="state.afterItemPrice"></span>
										</span>
									</div>
									<div class="wc-block-components-product-metadata">
										<div data-wp-watch="callbacks.itemShortDescription" >
											<div class="wc-block-components-product-metadata__description"></div>
										</div>
										<?php echo $this->render_product_details_markup( 'item_data' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>										
										<?php echo $this->render_product_details_markup( 'variation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>																				
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
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
												<path fill-rule="evenodd" clip-rule="evenodd" d="M12 5.5A2.25 2.25 0 0 0 9.878 7h4.244A2.251 2.251 0 0 0 12 5.5ZM12 4a3.751 3.751 0 0 0-3.675 3H5v1.5h1.27l.818 8.997a2.75 2.75 0 0 0 2.739 2.501h4.347a2.75 2.75 0 0 0 2.738-2.5L17.73 8.5H19V7h-3.325A3.751 3.751 0 0 0 12 4Zm4.224 4.5H7.776l.806 8.861a1.25 1.25 0 0 0 1.245 1.137h4.347a1.25 1.25 0 0 0 1.245-1.137l.805-8.861Z"/>
											</svg>
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
										data-wp-bind--hidden="!state.cartItemHasDiscount" 
										class="wc-block-components-product-badge wc-block-components-sale-badge"
									>
									<?php
										echo wp_kses(
											$line_item_save_badge,
											array(
												'span' => array(
													'data-wp-text' => true,
													'class'        => true,
												),
											)
										);
									?>
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
	protected function render_product_details_markup( $property ) {
		$context = array( 'dataProperty' => $property );

		// If the property is item_data, so not a variation, we need to skip the text directive.
		$is_item_data = 'item_data' === $context['dataProperty'];

		ob_start();
		?>
		<div
			<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			class="wc-block-components-product-details"
			data-wp-bind--hidden="state.shouldHideProductDetails"
		>
			<template
				data-wp-each--item-data="state.cartItem.<?php echo esc_attr( $property ); ?>"
				data-wp-each-key="state.cartItemDataKey"
			>
				<?php echo $this->render_product_details_item_markup( $is_item_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</template>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render markup for a single product detail item.
	 *
	 * @param bool $is_item_data Whether the item is of item_data type.
	 * @return string Rendered product detail item output based on item type.
	 */
	private function render_product_details_item_markup( $is_item_data = false ) {
		ob_start();
		?>
		<span
			data-wp-bind--hidden="state.cartItemDataAttrHidden"
			data-wp-bind--class="state.cartItemDataAttr.className"
		>
		<?php if ( $is_item_data ) : ?>
			<span class="wc-block-components-product-details__name" data-wp-watch="callbacks.itemDataNameInnerHTML"></span>
			<span class="wc-block-components-product-details__value" data-wp-watch="callbacks.itemDataValueInnerHTML"></span>
		<?php else : ?>
			<span class="wc-block-components-product-details__name" data-wp-text="state.cartItemDataAttr.name"></span>
			<span class="wc-block-components-product-details__value" data-wp-text="state.cartItemDataAttr.value"></span>
		<?php endif; ?>
			<span aria-hidden="true" data-wp-bind--hidden="state.isLastCartItemDataAttr"> / </span>
		</span>
		<?php
		return ob_get_clean();
	}
}
