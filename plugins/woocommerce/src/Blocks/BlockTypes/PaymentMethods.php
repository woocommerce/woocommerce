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
			if ( $gateway->enabled === 'yes' ) {
				if ( $gateway_id === 'woocommerce_payments' ) {
					$output .= $this->render_card_brands( $attributes );
				} else {
					$method_title = $gateway->get_title();
					$icon_url     = $this->get_payment_method_icon( $gateway_id, $gateway );

					$output .= '<div class="payment-method-item">';

					if ( $icon_url ) {
						$output .= '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $method_title ) . '" class="payment-method-icon">';
					}

					$output .= '</div>';
				}
			}
		}

		$output .= '</div>';

		return $output;
	}

	private function render_card_brands( $attributes ) {
		$output = '';
		$enabled_card_types = $this->get_enabled_card_types();

		$number_of_icons = $attributes['numberOfIcons'] ?? 8;

		foreach ( $enabled_card_types as $card_type => $card_data ) {
			if ( $number_of_icons > 0 ) {
				$number_of_icons--;
			} else {
				break;
			}

			$output .= '<div class="payment-method-item">';
			$output .= '<span class="payment-method-icon" style="background-image: url(\'' . esc_url( $card_data['icon'] ) . '\');">' . esc_attr( $card_data['name'] ) . '</span>';

			$output .= '</div>';
		}

		return $output;
	}

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

		if ( class_exists( 'WC_Payments_Features' ) && method_exists( 'WC_Payments_Features', 'is_enabled' ) ) {
			$woopayments_gateway = WC()->payment_gateways->payment_gateways()['woocommerce_payments'] ?? null;

			if ( $woopayments_gateway && method_exists( $woopayments_gateway, 'get_option' ) ) {
				$enabled_card_types = $woopayments_gateway->get_option( 'enabled_card_types', array() );

				if ( ! empty( $enabled_card_types ) && is_array( $enabled_card_types ) ) {
					$filtered_cards = array();
					foreach ( $enabled_card_types as $card_type ) {
						if ( isset( $card_types[ $card_type ] ) ) {
							$filtered_cards[ $card_type ] = $card_types[ $card_type ];
						}
					}
					return ! empty( $filtered_cards ) ? $filtered_cards : $card_types;
				}
			}
		}

		return $card_types;
	}

	private function get_card_brand_icon_url( $card_type ) {
		$woopayments_url = \plugins_url() . '/woocommerce-payments/assets/images/payment-method-icons/';
		return $woopayments_url . $card_type . '.svg';
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block      The block object.
	 */
	protected function enqueue_assets( array $attributes, $content = '', $block = null ) {
		parent::enqueue_assets( $attributes, $content, $block );
	}
}
