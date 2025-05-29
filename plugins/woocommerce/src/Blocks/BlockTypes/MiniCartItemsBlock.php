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
		return $this->get_mini_cart_items_markup();
	}

	/**
	 * Return the main instance of WC_Cart class.
	 *
	 * @return \WC_Cart CartController class instance.
	 */
	protected function get_cart_instance() {
		$cart = WC()->cart;

		if ( $cart && $cart instanceof \WC_Cart ) {
			return $cart;
		}

		return null;
	}


	protected function get_mini_cart_items_markup() {
		$cart       = $this->get_cart_instance();
		$cart_items = $cart->get_cart();

		ob_start();
		?>
		<div class="wp-block-woocommerce-mini-cart-items-block wc-block-mini-cart__items" tabindex="-1">
			<div class="wp-block-woocommerce-mini-cart-products-table-block wc-block-mini-cart__products-table">
				<table class="wc-block-cart-items wc-block-mini-cart-items" tabindex="-1">
					<caption class="screen-reader-text">
						<h2>Products in cart</h2>
					</caption>
					<thead>
						<tr class="wc-block-cart-items__header">
							<th class="wc-block-cart-items__header-image">
								<span>Product</span>
							</th>
							<th class="wc-block-cart-items__header-product">
								<span>Details</span>
							</th>
							<th class="wc-block-cart-items__header-total">
								<span>Total</span>
							</th>
						</tr>
					</thead>
					<tbody>
						<template
							data-wp-each="state.cart.items"
						>
							<tr class="wc-block-cart-items__row" tabindex="-1">
								<td class="wc-block-cart-item__image" aria-hidden="true">
									<a href="http://localhost:1234/?product=hat" tabindex="-1">
										<img src="http://localhost:1234/wp-content/uploads/2025/03/167113811-0be977aa-edfe-4a09-b36d-a62f02de4a29-324x324.jpeg" alt="Hat">
									</a>
								</td>
								<td class="wc-block-cart-item__product">
									<div class="wc-block-cart-item__wrap">
										<a class="wc-block-components-product-name" href="http://localhost:1234/?product=hat">Hat</a>
										<div class="wc-block-cart-item__prices">
											<span class="price wc-block-components-product-price">
												<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
													$12.00
												</span>
											</span>
										</div>
										<div class="wc-block-components-product-metadata">
											<div class="wc-block-components-product-metadata__description">
												<p>This is a simple product.</p>
											</div>
										</div>
										<div class="wc-block-cart-item__quantity">
											<div class="wc-block-components-quantity-selector">
												<input class="wc-block-components-quantity-selector__input" type="number" step="1" min="1" max="9999" aria-label="Quantity of Hat in your cart." value="3">
												<button aria-label="Reduce quantity of Hat" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">－</button>
												<button aria-label="Increase quantity of Hat" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">＋</button>
											</div>
											<button class="wc-block-cart-item__remove-link" aria-label="Remove Hat from cart">Remove item</button>
										</div>
									</div>
								</td>
								<td class="wc-block-cart-item__total">
									<div class="wc-block-cart-item__total-price-and-sale-badge-wrapper">
										<span class="price wc-block-components-product-price">
											<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-product-price__value">
												$36.00
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
