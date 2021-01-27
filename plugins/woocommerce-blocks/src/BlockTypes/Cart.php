<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

/**
 * Cart class.
 *
 * @internal
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
				'supports'        => [],
			)
		);
	}


	/**
	 * Append frontend scripts when rendering the Cart block.
	 *
	 * @param array|\WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @param string          $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		$block_attributes = is_a( $attributes, '\WP_Block' ) ? $attributes->attributes : $attributes;

		do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before' );
		$this->enqueue_assets( $block_attributes );
		do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after' );

		// Deregister core cart scripts and styles.
		wp_deregister_script( 'wc-cart' );
		wp_deregister_script( 'wc-password-strength-meter' );
		wp_deregister_script( 'selectWoo' );
		wp_deregister_style( 'select2' );

		return $this->inject_html_data_attributes( $content . $this->get_skeleton(), $block_attributes );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		$data_registry = Package::container()->get(
			AssetDataRegistry::class
		);

		if ( ! $data_registry->exists( 'shippingCountries' ) ) {
			$data_registry->add( 'shippingCountries', $this->deep_sort_with_accents( WC()->countries->get_shipping_countries() ) );
		}

		if ( ! $data_registry->exists( 'shippingStates' ) ) {
			$data_registry->add( 'shippingStates', $this->deep_sort_with_accents( WC()->countries->get_shipping_country_states() ) );
		}

		$permalink = ! empty( $attributes['checkoutPageId'] ) ? get_permalink( $attributes['checkoutPageId'] ) : false;

		if ( $permalink && ! $data_registry->exists( 'page-' . $attributes['checkoutPageId'] ) ) {
			$data_registry->add( 'page-' . $attributes['checkoutPageId'], $permalink );
		}

		// Hydrate the following data depending on admin or frontend context.
		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->hydrate_from_api( $data_registry );
		}

		do_action( 'woocommerce_blocks_cart_enqueue_data' );
	}

	/**
	 * Removes accents from an array of values, sorts by the values, then returns the original array values sorted.
	 *
	 * @param array $array Array of values to sort.
	 * @return array Sorted array.
	 */
	protected function deep_sort_with_accents( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return $array;
		}

		if ( is_array( reset( $array ) ) ) {
			return array_map( [ $this, 'deep_sort_with_accents' ], $array );
		}

		$array_without_accents = array_map( 'remove_accents', array_map( 'wc_strtolower', array_map( 'html_entity_decode', $array ) ) );
		asort( $array_without_accents );
		return array_replace( $array_without_accents, $array );
	}

	/**
	 * Register/enqueue scripts used for this block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_scripts( array $attributes = [] ) {
		Assets::register_block_script( $this->block_name . '-frontend', $this->block_name . '-block-frontend' );
	}

	/**
	 * Hydrate the cart block with data from the API.
	 *
	 * @param AssetDataRegistry $data_registry Data registry instance.
	 */
	protected function hydrate_from_api( AssetDataRegistry $data_registry ) {
		if ( ! $data_registry->exists( 'cartData' ) ) {
			$data_registry->add( 'cartData', WC()->api->get_endpoint_data( '/wc/store/cart' ) );
		}
	}

	/**
	 * Render skeleton markup for the cart block.
	 */
	protected function get_skeleton() {
		return '
			<div class="wc-block-skeleton wc-block-components-sidebar-layout wc-block-cart wc-block-cart--is-loading wc-block-cart--skeleton hidden" aria-hidden="true">
				<div class="wc-block-components-main wc-block-cart__main">
					<h2><span></span></h2>
					<table class="wc-block-cart-items">
						<thead>
							<tr class="wc-block-cart-items__header">
								<th class="wc-block-cart-items__header-image"><span /></th>
								<th class="wc-block-cart-items__header-product"><span /></th>
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
									<div class="wc-block-cart-item__individual-price"></div>
									<div class="wc-block-cart-item__product-metadata"></div>
									<div class="wc-block-components-quantity-selector">
										<input class="wc-block-components-quantity-selector__input" type="number" step="1" min="0" value="1" />
										<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">－</button>
										<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">＋</button>
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
									<div class="wc-block-cart-item__individual-price"></div>
									<div class="wc-block-cart-item__product-metadata"></div>
									<div class="wc-block-components-quantity-selector">
										<input class="wc-block-components-quantity-selector__input" type="number" step="1" min="0" value="1" />
										<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">－</button>
										<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">＋</button>
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
									<div class="wc-block-cart-item__individual-price"></div>
									<div class="wc-block-cart-item__product-metadata"></div>
									<div class="wc-block-components-quantity-selector">
										<input class="wc-block-components-quantity-selector__input" type="number" step="1" min="0" value="1" />
										<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">－</button>
										<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">＋</button>
									</div>
								</td>
								<td class="wc-block-cart-item__total">
									<div class="wc-block-cart-item__price"></div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="wc-block-components-sidebar wc-block-cart__sidebar">
					<div class="components-card"></div>
				</div>
			</div>
		' . $this->get_skeleton_inline_script();
	}
}
