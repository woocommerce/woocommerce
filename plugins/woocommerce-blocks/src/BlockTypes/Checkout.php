<?php
/**
 * Checkout block.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout class.
 */
class Checkout extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout';

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
				'style'           => 'wc-block-style',
				'script'          => 'wc-' . $this->block_name . '-block-frontend',
			)
		);
	}

	/**
	 * Append frontend scripts when rendering the block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		$data_registry = Package::container()->get(
			\Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
		);
		if ( ! empty( $attributes['cartPageId'] ) && ! $data_registry->exists( 'page-' . $attributes['cartPageId'] ) ) {
			$permalink = get_permalink( $attributes['cartPageId'] );
			if ( $permalink ) {
				$data_registry->add( 'page-' . $attributes['cartPageId'], get_permalink( $attributes['cartPageId'] ) );
			}
		}
		if ( ! $data_registry->exists( 'allowedCountries' ) ) {
			$data_registry->add( 'allowedCountries', WC()->countries->get_allowed_countries() );
		}
		if ( ! $data_registry->exists( 'shippingCountries' ) ) {
			$data_registry->add( 'shippingCountries', WC()->countries->get_shipping_countries() );
		}
		if ( ! $data_registry->exists( 'allowedStates' ) ) {
			$data_registry->add( 'allowedStates', WC()->countries->get_allowed_country_states() );
		}
		if ( ! $data_registry->exists( 'shippingStates' ) ) {
			$data_registry->add( 'shippingStates', WC()->countries->get_shipping_country_states() );
		}
		if ( ! $data_registry->exists( 'cartData' ) ) {
			$data_registry->add( 'cartData', WC()->api->get_endpoint_data( '/wc/store/cart' ) );
		}
		if ( ! $data_registry->exists( 'billingData' ) && WC()->customer instanceof \WC_Customer ) {
			$data_registry->add( 'billingData', WC()->customer->get_billing() );
		}
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && $screen->is_block_editor() && ! $data_registry->exists( 'shippingMethodsExist' ) ) {
				$methods_exist = wc_get_shipping_method_count() > 0;
				$data_registry->add( 'shippingMethodsExist', $methods_exist );
			}
		}
		\Automattic\WooCommerce\Blocks\Assets::register_block_script( $this->block_name . '-frontend', $this->block_name . '-block-frontend' );
		return $content . $this->get_skeleton();
	}

	/**
	 * Render skeleton markup for the checkout block.
	 */
	protected function get_skeleton() {
		return '
			<div class="wc-block-sidebar-layout wc-block-checkout wc-block-checkout--is-loading wc-block-checkout--skeleton" aria-hidden="true">
				<div class="wc-block-main">
					<div class="wc-block-component-express-checkout"></div>
					<div class="wc-block-component-express-checkout-continue-rule"><span></span></div>
					<form class="wc-block-checkout-form">
						<fieldset class="wc-block-checkout__contact-fields wc-block-checkout-step">
							<span></span>
						</fieldset>
						<fieldset class="wc-block-checkout__contact-fields wc-block-checkout-step">
							<span></span>
						</fieldset>
						<fieldset class="wc-block-checkout__contact-fields wc-block-checkout-step">
							<span></span>
						</fieldset>
						<fieldset class="wc-block-checkout__contact-fields wc-block-checkout-step">
							<span></span>
						</fieldset>
						<div class="wc-block-checkout__actions">
							<button class="components-button button wc-block-button wc-block-components-checkout-place-order-button">&nbsp;</button>
						</div>
					</form>
				</div>
				<div class="wc-block-sidebar wc-block-checkout__sidebar">
					<div class="components-card"></div>
				</div>
			</div>
		';
	}
}
