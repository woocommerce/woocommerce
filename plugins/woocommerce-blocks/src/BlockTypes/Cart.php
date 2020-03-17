<?php
/**
 * Cart block.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;

defined( 'ABSPATH' ) || exit;

/**
 * Cart class.
 */
class Cart extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'cart';

	/**
	 * Registers the block type with WordPress.
	 */
	public function register_block_type() {
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => 'wc-' . $this->block_name . '-block',
				'editor_style'    => 'wc-block-editor',
				'style'           => [ 'wc-block-style', 'wc-block-vendors-style' ],
				'script'          => 'wc-' . $this->block_name . '-block-frontend',
			)
		);
	}

	/**
	 * Append frontend scripts when rendering the Cart block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		$data_registry = Package::container()->get(
			\Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
		);
		if ( ! empty( $attributes['checkoutPageId'] ) && ! $data_registry->exists( 'page-' . $attributes['checkoutPageId'] ) ) {
			$permalink = get_permalink( $attributes['checkoutPageId'] );
			if ( $permalink ) {
				$data_registry->add( 'page-' . $attributes['checkoutPageId'], get_permalink( $attributes['checkoutPageId'] ) );
			}
		}
		if ( ! $data_registry->exists( 'shippingCountries' ) ) {
			$data_registry->add( 'shippingCountries', WC()->countries->get_shipping_countries() );
		}
		if ( ! $data_registry->exists( 'shippingStates' ) ) {
			$data_registry->add( 'shippingStates', WC()->countries->get_shipping_country_states() );
		}
		if ( ! $data_registry->exists( 'cartData' ) ) {
			$data_registry->add( 'cartData', WC()->api->get_endpoint_data( '/wc/store/cart' ) );
		}
		if ( ! $data_registry->exists( 'quantitySelectLimit' ) ) {
			/**
			 * Note: this filter will be deprecated if/when quantity select limits
			 * are added at the product level.
			 *
			 * @return {integer} $max_quantity_limit Maximum quantity of products that can be selected in the cart.
			 */
			$max_quantity_limit = apply_filters( 'woocommerce_maximum_quantity_selected_cart', 99 );
			$data_registry->add( 'quantitySelectLimit', $max_quantity_limit );
		}
		\Automattic\WooCommerce\Blocks\Assets::register_block_script(
			$this->block_name . '-frontend',
			$this->block_name . '-block-frontend'
		);
		return $content . $this->get_skeleton();
	}

	/**
	 * Render skeleton markup for the cart block.
	 */
	protected function get_skeleton() {
		return '
			<div class="wc-block-sidebar-layout wc-block-cart wc-block-cart--is-loading wc-block-cart--skeleton" aria-hidden="true">
				<div class="wc-block-main wc-block-cart__main">
					<h2><span></span></h2>
					<table class="wc-block-cart-items">
						<thead>
							<tr class="wc-block-cart-items__header">
								<th class="wc-block-cart-items__header-image"><span /></th>
								<th class="wc-block-cart-items__header-product"><span /></th>
								<th class="wc-block-cart-items__header-quantity"><span /></th>
								<th class="wc-block-cart-items__header-total"><span /></th>
							</tr>
						</thead>
						<tbody>
							<tr class="wc-block-cart-items__row">
								<td class="wc-block-cart-item__image">
									<div><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=" width="1" height="1" /></div>
								</td>
								<td class="wc-block-cart-item__product">
									<div class="wc-block-cart-item__product-name"></div>
									<div class="wc-block-cart-item__product-metadata"></div>
								</td>
								<td class="wc-block-cart-item__quantity">
								<div class="wc-block-quantity-selector">
									<input class="wc-block-quantity-selector__input" type="number" step="1" min="0" value="1" />
									<button class="wc-block-quantity-selector__button wc-block-quantity-selector__button--minus">－</button>
									<button class="wc-block-quantity-selector__button wc-block-quantity-selector__button--plus">＋</button>
								</div>
								</td>
								<td class="wc-block-cart-item__total">
									<div class="wc-block-cart-item__price"></div>
								</td>
							</tr>
							<tr class="wc-block-cart-items__row">
								<td class="wc-block-cart-item__image">
									<div><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=" width="1" height="1" /></div>
								</td>
								<td class="wc-block-cart-item__product">
									<div class="wc-block-cart-item__product-name">&nbsp;</div>
									<div class="wc-block-cart-item__product-metadata">&nbsp;</div>
								</td>
								<td class="wc-block-cart-item__quantity">
								<div class="wc-block-quantity-selector">
									<input class="wc-block-quantity-selector__input" type="number" step="1" min="0" value="1" />
									<button class="wc-block-quantity-selector__button wc-block-quantity-selector__button--minus">－</button>
									<button class="wc-block-quantity-selector__button wc-block-quantity-selector__button--plus">＋</button>
								</div>
								</td>
								<td class="wc-block-cart-item__total">
									<div class="wc-block-cart-item__price"></div>
								</td>
							</tr>
							<tr class="wc-block-cart-items__row">
								<td class="wc-block-cart-item__image">
									<div><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=" width="1" height="1" /></div>
								</td>
								<td class="wc-block-cart-item__product">
									<div class="wc-block-cart-item__product-name"></div>
									<div class="wc-block-cart-item__product-metadata"></div>
								</td>
								<td class="wc-block-cart-item__quantity">
								<div class="wc-block-quantity-selector">
									<input class="wc-block-quantity-selector__input" type="number" step="1" min="0" value="1" />
									<button class="wc-block-quantity-selector__button wc-block-quantity-selector__button--minus">－</button>
									<button class="wc-block-quantity-selector__button wc-block-quantity-selector__button--plus">＋</button>
								</div>
								</td>
								<td class="wc-block-cart-item__total">
									<div class="wc-block-cart-item__price"></div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="wc-block-sidebar wc-block-cart__sidebar">
					<div class="components-card"></div>
				</div>
			</div>
		';
	}
}
