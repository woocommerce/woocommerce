<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;

/**
 * PaymentMethods class.
 */
class PaymentMethodIcons extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'payment-method-icons';

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

		$output = '<div class="wp-block-woocommerce-payment-method-icons">';

		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( 'woocommerce_payments' === $gateway_id && 'yes' === $gateway->enabled && in_array( 'card', $gateway->get_option( 'upe_enabled_payment_method_ids' ), true ) ) {
				$output .= $this->render_card_types( $attributes );
			}
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the card types.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block type output.
	 */
	private function render_card_types( $attributes ) {
		$output             = '';
		$enabled_card_types = $this->get_enabled_card_types();
		$number_of_icons    = $attributes['numberOfIcons'] ?? 5;

		foreach ( $enabled_card_types as $card_type => $card_data ) {
			if ( $number_of_icons > 0 ) {
				--$number_of_icons;
			} else {
				break;
			}

			$output .= '<div class="wp-block-woocommerce-payment-method-icons__item">';
			$output .= '<span class="wp-block-woocommerce-payment-method-icons__icon" style="background-image: url(\'' . esc_url( $card_data['icon'] ) . '\');">' . esc_attr( $card_data['name'] ) . '</span>';

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
				'icon' => $this->get_card_type_icon_url( 'visa' ),
			),
			'mastercard' => array(
				'name' => 'Mastercard',
				'icon' => $this->get_card_type_icon_url( 'mastercard' ),
			),
			'amex'       => array(
				'name' => 'American Express',
				'icon' => $this->get_card_type_icon_url( 'amex' ),
			),
			'discover'   => array(
				'name' => 'Discover',
				'icon' => $this->get_card_type_icon_url( 'discover' ),
			),
			'jcb'        => array(
				'name' => 'JCB',
				'icon' => $this->get_card_type_icon_url( 'jcb' ),
			),
		);

		return $card_types;
	}

	/**
	 * Get the card type icon URL.
	 *
	 * @param string $card_type Card type.
	 * @return string Card type icon URL.
	 */
	private function get_card_type_icon_url( $card_type ) {
		$woopayments_url = \plugins_url() . '/woocommerce-payments/assets/images/payment-method-icons/';
		return $woopayments_url . $card_type . '.svg';
	}
}
