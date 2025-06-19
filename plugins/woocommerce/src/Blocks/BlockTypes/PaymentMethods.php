<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;

/**
 * PaymentMethods class.
 */
class PaymentMethods extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'payment-methods';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), [ 'wc-blocks-packages-style' ] );
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! class_exists( 'WC_Payments' ) ) {
			return;
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( empty( $available_gateways ) ) {
			return;
		}

		$output = '<div class="wp-block-woocommerce-payment-methods">';

		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				if ( 'woocommerce_payments' === $gateway_id ) {
					$output .= $this->render_card_brands( $attributes );
				}
			}
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the card brands.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block type output.
	 */
	private function render_card_brands( $attributes ) {
		$output             = '';
		$enabled_card_types = $this->get_enabled_card_types();
		$number_of_icons    = $attributes['numberOfIcons'] ?? 5;

		foreach ( $enabled_card_types as $card_type => $card_data ) {
			if ( $number_of_icons > 0 ) {
				--$number_of_icons;
			} else {
				break;
			}

			$output .= '<div class="payment-method-item">';
			$output .= '<span class="payment-method-icon" style="background-image: url(\'' . esc_url( $card_data['icon'] ) . '\');">' . esc_attr( $card_data['name'] ) . '</span>';

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get the enabled card types.
	 *
	 * @return array Enabled card types.
	 */
	private function get_enabled_card_types() {
		$card_types = array(
			'visa'       => array(
				'name' => 'Visa',
				'icon' => $this->get_card_brand_icon_url( 'visa' ),
			),
			'mastercard' => array(
				'name' => 'Mastercard',
				'icon' => $this->get_card_brand_icon_url( 'mastercard' ),
			),
			'amex'       => array(
				'name' => 'American Express',
				'icon' => $this->get_card_brand_icon_url( 'amex' ),
			),
			'discover'   => array(
				'name' => 'Discover',
				'icon' => $this->get_card_brand_icon_url( 'discover' ),
			),
			'jcb'        => array(
				'name' => 'JCB',
				'icon' => $this->get_card_brand_icon_url( 'jcb' ),
			),
		);

		return $card_types;
	}

	/**
	 * Get the card brand icon URL.
	 *
	 * @param string $card_type Card type.
	 * @return string Card brand icon URL.
	 */
	private function get_card_brand_icon_url( $card_type ) {
		$woopayments_url = \plugins_url() . '/woocommerce-payments/assets/images/payment-method-icons/';
		return $woopayments_url . $card_type . '.svg';
	}
}
