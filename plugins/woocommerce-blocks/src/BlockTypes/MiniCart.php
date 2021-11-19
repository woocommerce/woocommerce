<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

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

		$payment_method_registry = Package::container()->get( PaymentMethodRegistry::class );
		$payment_methods         = $payment_method_registry->get_all_active_payment_method_script_dependencies();

		foreach ( $payment_methods as $payment_method ) {
			$payment_method_script = $this->get_script_from_handle( $payment_method );

			if ( ! is_null( $payment_method_script ) ) {
				$this->append_script_and_deps_src( $payment_method_script );
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

		/**
		 * Fires after cart block data is registered.
		 */
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
	 * @return \_WP_Dependency|null Object containing the script data if found, or null.
	 */
	protected function get_script_from_handle( $handle ) {
		$wp_scripts = wp_scripts();
		foreach ( $wp_scripts->registered as $script ) {
			if ( $script->handle === $handle ) {
				return $script;
			}
		}
		return null;
	}

	/**
	 * Recursively appends a scripts and its dependencies into the scripts_to_lazy_load array.
	 *
	 * @param \_WP_Dependency $script Object containing script data.
	 */
	protected function append_script_and_deps_src( $script ) {
		$wp_scripts = wp_scripts();

		// This script and its dependencies have already been appended.
		if ( ! $script || array_key_exists( $script->handle, $this->scripts_to_lazy_load ) ) {
			return;
		}

		if ( count( $script->deps ) ) {
			foreach ( $script->deps as $dep ) {
				if ( ! array_key_exists( $dep, $this->scripts_to_lazy_load ) ) {
					$dep_script = $this->get_script_from_handle( $dep );

					if ( ! is_null( $dep_script ) ) {
						$this->append_script_and_deps_src( $dep_script );
					}
				}
			}
		}
		if ( ! $script->src ) {
			return;
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
		return $content . $this->get_markup( $attributes );
	}

	/**
	 * Render the markup for the Mini Cart block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string The HTML markup.
	 */
	protected function get_markup( $attributes ) {
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

		$wrapper_classes = 'wc-block-mini-cart';
		$classes         = '';
		$style           = '';

		if ( ! isset( $attributes['transparentButton'] ) || $attributes['transparentButton'] ) {
			$wrapper_classes .= ' is-transparent';
		}

		/**
		 * Get the color class and inline style.
		 *
		 * @todo refactor the logic of color class and style using StyleAttributesUtils.
		 */
		if ( ! empty( $attributes['textColor'] ) ) {
			$classes .= sprintf(
				' has-%s-color has-text-color',
				esc_attr( $attributes['textColor'] )
			);
		} elseif ( ! empty( $attributes['style']['color']['text'] ) ) {
			$style   .= 'color: ' . esc_attr( $attributes['style']['color']['text'] ) . ';';
			$classes .= ' has-text-color';
		}

		if ( ! empty( $attributes['backgroundColor'] ) ) {
			$classes .= sprintf(
				' has-%s-background-color has-background',
				esc_attr( $attributes['backgroundColor'] )
			);
		} elseif ( ! empty( $attributes['style']['color']['background'] ) ) {
			$style   .= 'background-color: ' . esc_attr( $attributes['style']['color']['background'] ) . ';';
			$classes .= ' has-background';
		}

		$aria_label = sprintf(
			/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
			_n(
				'%1$d item in cart, total price of %2$s',
				'%1$d items in cart, total price of %2$s',
				$cart_contents_count,
				'woo-gutenberg-products-block'
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
				'woo-gutenberg-products-block'
			),
			$cart_contents_count
		);
		$icon        = '<svg class="wc-block-mini-cart__icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g clip-path="url(#clip0)">
				<path d="M7.50008 18.3332C7.96032 18.3332 8.33341 17.9601 8.33341 17.4998C8.33341 17.0396 7.96032 16.6665 7.50008 16.6665C7.03984 16.6665 6.66675 17.0396 6.66675 17.4998C6.66675 17.9601 7.03984 18.3332 7.50008 18.3332Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M16.6666 18.3332C17.1268 18.3332 17.4999 17.9601 17.4999 17.4998C17.4999 17.0396 17.1268 16.6665 16.6666 16.6665C16.2063 16.6665 15.8333 17.0396 15.8333 17.4998C15.8333 17.9601 16.2063 18.3332 16.6666 18.3332Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M0.833252 0.833496H4.16658L6.39992 11.9918C6.47612 12.3755 6.68484 12.7201 6.98954 12.9654C7.29424 13.2107 7.6755 13.341 8.06658 13.3335H16.1666C16.5577 13.341 16.9389 13.2107 17.2436 12.9654C17.5483 12.7201 17.757 12.3755 17.8333 11.9918L19.1666 5.00016H4.99992" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</g>
			<defs>
				<clipPath id="clip0">
					<rect width="20" height="20" fill="white"/>
				</clipPath>
			</defs>
		</svg>';
		$button_html = '<span class="wc-block-mini-cart__amount">' . esc_html( wp_strip_all_tags( wc_price( $cart_contents_total ) ) ) . '</span>
		<span class="wc-block-mini-cart__quantity-badge">
			' . $icon . '
			<span class="wc-block-mini-cart__badge ' . $classes . '" style="' . $style . '">' . $cart_contents_count . '</span>
		</span>';

		if ( is_cart() || is_checkout() ) {
			return '<div class="' . $wrapper_classes . '">
				<button class="wc-block-mini-cart__button ' . $classes . '" aria-label="' . esc_attr( $aria_label ) . '" style="' . $style . '" disabled>' . $button_html . '</button>
			</div>';
		}

		$part                   = 'mini-cart';
		$template_part          = gutenberg_get_block_template( get_stylesheet() . '//' . $part, 'wp_template_part' );
		$template_part_contents = '';
		if ( $template_part && ! empty( $template_part->content ) ) {
			$template_part_contents = do_blocks( $template_part->content );
		}

		return '<div class="' . $wrapper_classes . '">
			<button class="wc-block-mini-cart__button ' . $classes . '" aria-label="' . esc_attr( $aria_label ) . '" style="' . $style . '">' . $button_html . '</button>
			<div class="wc-block-mini-cart__drawer is-loading is-mobile wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden" aria-hidden="true">
				<div class="components-modal__frame wc-block-components-drawer">
					<div class="components-modal__content">
						<div class="components-modal__header">
							<div class="components-modal__header-heading-container">
								<h1 id="components-modal-header-1" class="components-modal__header-heading">' . wp_kses_post( $title ) . '</h1>
							</div>
						</div>
						<div class="wc-block-mini-cart__template-part">'
						. $template_part_contents .
						'</div>
					</div>
				</div>
			</div>
		</div>';
	}
}
