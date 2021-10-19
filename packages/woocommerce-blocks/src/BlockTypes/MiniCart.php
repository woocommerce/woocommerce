<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * Mini Cart class.
 *
 * @internal
 */
class MiniCart extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart';

	/**
	 * Array of scripts that will be lazy loaded when interacting with the block.
	 *
	 * @var string[]
	 */
	protected $scripts_to_lazy_load = array();

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string;
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		if ( is_cart() || is_checkout() ) {
			return;
		}

		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		if ( is_cart() || is_checkout() ) {
			return;
		}

		parent::enqueue_data( $attributes );

		// Hydrate the following data depending on admin or frontend context.
		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->hydrate_from_api();
		}

		$script_data = $this->asset_api->get_script_data( 'build/mini-cart-component-frontend.js' );

		$num_dependencies = count( $script_data['dependencies'] );
		$wp_scripts       = wp_scripts();

		for ( $i = 0; $i < $num_dependencies; $i++ ) {
			$dependency = $script_data['dependencies'][ $i ];

			foreach ( $wp_scripts->registered as $script ) {
				if ( $script->handle === $dependency ) {
					$this->append_script_and_deps_src( $script );
					break;
				}
			}
		}

		$this->scripts_to_lazy_load['wc-block-mini-cart-component-frontend'] = array(
			'src'     => $script_data['src'],
			'version' => $script_data['version'],
		);

		$this->asset_data_registry->add(
			'mini_cart_block_frontend_dependencies',
			$this->scripts_to_lazy_load,
			true
		);

		$this->asset_data_registry->add(
			'displayCartPricesIncludingTax',
			'incl' === get_option( 'woocommerce_tax_display_cart' ),
			true
		);

		do_action( 'woocommerce_blocks_cart_enqueue_data' );
	}

	/**
	 * Hydrate the cart block with data from the API.
	 */
	protected function hydrate_from_api() {
		$this->asset_data_registry->hydrate_api_request( '/wc/store/cart' );
	}

	/**
	 * Returns the script data given its handle.
	 *
	 * @param string $handle Handle of the script.
	 *
	 * @return array Array containing the script data.
	 */
	protected function get_script_from_handle( $handle ) {
		$wp_scripts = wp_scripts();
		foreach ( $wp_scripts->registered as $script ) {
			if ( $script->handle === $handle ) {
				return $script;
			}
		}

		return '';
	}

	/**
	 * Recursively appends a scripts and its dependencies into the
	 * scripts_to_lazy_load array.
	 *
	 * @param string $script Array containing script data.
	 */
	protected function append_script_and_deps_src( $script ) {
		$wp_scripts = wp_scripts();
		// This script and its dependencies have already been appended.
		if ( array_key_exists( $script->handle, $this->scripts_to_lazy_load ) ) {
			return;
		}

		if ( count( $script->deps ) > 0 ) {
			foreach ( $script->deps as $dep ) {
				if ( ! array_key_exists( $dep, $this->scripts_to_lazy_load ) ) {
					$dep_script = $this->get_script_from_handle( $dep );
					$this->append_script_and_deps_src( $dep_script );
				}
			}
		}
		$this->scripts_to_lazy_load[ $script->handle ] = array(
			'src'          => $script->src,
			'version'      => $script->ver,
			'before'       => $wp_scripts->print_inline_script( $script->handle, 'before', false ),
			'after'        => $wp_scripts->print_inline_script( $script->handle, 'after', false ),
			'translations' => $wp_scripts->print_translations( $script->handle, false ),
		);
	}

	/**
	 * Append frontend scripts when rendering the Mini Cart block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 *
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		return $this->inject_html_data_attributes( $content . $this->get_markup(), $attributes );
	}

	/**
	 * Render the markup for the Mini Cart block.
	 *
	 * @return string The HTML markup.
	 */
	protected function get_markup() {
		if ( is_admin() || WC()->is_rest_api_request() ) {
			// In the editor we will display the placeholder, so no need to load
			// real cart data and to print the markup.
			return '';
		}
		$cart_controller     = new CartController();
		$cart                = $cart_controller->get_cart_instance();
		$cart_contents_count = $cart->get_cart_contents_count();
		$cart_contents       = $cart->get_cart();
		$cart_contents_total = $cart->get_subtotal();

		if ( $cart->display_prices_including_tax() ) {
			$cart_contents_total += $cart->get_subtotal_tax();
		}

		$button_text = sprintf(
			/* translators: %d is the number of products in the cart. */
			_n(
				'%d item',
				'%d items',
				$cart_contents_count,
				'woocommerce'
			),
			$cart_contents_count
		);
		$aria_label = sprintf(
			/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
			_n(
				'%1$d item in cart, total price of %2$s',
				'%1$d items in cart, total price of %2$s',
				$cart_contents_count,
				'woocommerce'
			),
			$cart_contents_count,
			wp_strip_all_tags( wc_price( $cart_contents_total ) )
		);
		$title = sprintf(
			/* translators: %d is the count of items in the cart. */
			_n(
				'Your cart (%d item)',
				'Your cart (%d items)',
				$cart_contents_count,
				'woocommerce'
			),
			$cart_contents_count
		);

		if ( is_cart() || is_checkout() ) {
			return '<div class="wc-block-mini-cart">
				<button class="wc-block-mini-cart__button" aria-label="' . $aria_label . '" disabled>' . $button_text . '</button>
			</div>';
		}

		return '<div class="wc-block-mini-cart">
			<button class="wc-block-mini-cart__button" aria-label="' . $aria_label . '">' . $button_text . '</button>
			<div class="wc-block-mini-cart__drawer is-loading is-mobile wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden" aria-hidden="true">
				<div class="components-modal__frame wc-block-components-drawer">
					<div class="components-modal__content">
						<div class="components-modal__header">
							<div class="components-modal__header-heading-container">
								<h1 id="components-modal-header-1" class="components-modal__header-heading">' . $title . '</h1>
							</div>
						</div>'
						. $this->get_cart_contents_markup( $cart_contents ) .
					'</div>
				</div>
			</div>
		</div>';
	}

	/**
	 * Render the markup of the Cart contents.
	 *
	 * @param array $cart_contents Array of contents in the cart.
	 *
	 * @return string The HTML markup.
	 */
	protected function get_cart_contents_markup( $cart_contents ) {
		// Force mobile styles.
		return '<table class="wc-block-cart-items">
			<thead>
				<tr class="wc-block-cart-items__header">
					<th class="wc-block-cart-items__header-image"><span /></th>
					<th class="wc-block-cart-items__header-product"><span /></th>
					<th class="wc-block-cart-items__header-total"><span /></th>
				</tr>
			</thead>
			<tbody>' . implode( array_map( array( $this, 'get_cart_item_markup' ), $cart_contents ) ) . '</tbody>
		</table>';
	}

	/**
	 * Render the skeleton of a Cart item.
	 *
	 * @return string The skeleton HTML markup.
	 */
	protected function get_cart_item_markup() {
		return '<tr class="wc-block-cart-items__row">
			<td class="wc-block-cart-item__image">
				<a href=""><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=" width="1" height="1" /></a>
			</td>
			<td class="wc-block-cart-item__product">
				<div class="wc-block-components-product-name"></div>
				<div class="wc-block-components-product-price"></div>
				<div class="wc-block-components-product-metadata"></div>
				<div class="wc-block-cart-item__quantity">
					<div class="wc-block-components-quantity-selector">
						<input class="wc-block-components-quantity-selector__input" type="number" step="1" min="0" value="1" />
						<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">－</button>
						<button class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">＋</button>
					</div>
					<button class="wc-block-cart-item__remove-link"></button>
				</div>
			</td>
			<td class="wc-block-cart-item__total">
				<div class="wc-block-cart-item__total-price-and-sale-badge-wrapper">
					<div class="wc-block-components-product-price"></div>
				</div>
			</td>
		</tr>';
	}
}
