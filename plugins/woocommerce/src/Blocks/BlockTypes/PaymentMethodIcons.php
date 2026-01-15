<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * PaymentMethodIcons class.
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
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'availablePaymentMethods', $this->get_available_payment_methods() );
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
		$payment_methods = $this->get_available_payment_methods();

		if ( empty( $payment_methods ) ) {
			return '';
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => esc_attr( $classes_and_styles['classes'] ),
				'style' => esc_attr( $classes_and_styles['styles'] ),
			)
		);

		$output  = '<div ' . $wrapper_attributes . '>';
		$output .= '<div class="wc-block-payment-method-icons">';
		$output .= $this->render_payment_method_icons( $attributes );
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render payment method icons.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block type output.
	 */
	private function render_payment_method_icons( $attributes ) {
		$output = '';

		$all_payment_methods = $this->get_available_payment_methods();
		$number_of_icons     = $attributes['numberOfIcons'] ?? 0;

		if ( 0 === $number_of_icons ) {
			$number_of_icons = count( $all_payment_methods );
		} else {
			$number_of_icons = max( 0, min( intval( $number_of_icons ), count( $all_payment_methods ) ) );
		}

		if ( ! empty( $all_payment_methods ) ) {
			for ( $i = 0; $i < $number_of_icons; $i++ ) {
				$payment_method = $all_payment_methods[ $i ];
				$output        .= '<div class="wc-block-payment-method-icons__item">';
				$output        .= '<span class="wc-block-payment-method-icons__icon" style="background-image: url(\'' . \esc_url( $payment_method['icon'] ) . '\');" role="img" aria-label="' . \esc_attr( $payment_method['name'] ) . '"></span>';
				$output        .= '</div>';
			}
		}

		return $output;
	}

	/**
	 * Check if WooPayments is enabled.
	 *
	 * @return bool WooPayments enabled.
	 */
	private function is_woopayments_enabled() {
		$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

		return isset( $payment_gateways['woocommerce_payments'] ) && 'yes' === $payment_gateways['woocommerce_payments']->enabled;
	}

	/**
	 * Get the enabled card types for WooPayments.
	 *
	 * Note: This uses hardcoded cards based on the default card types provided by WooPayments. This should be updated when these icons can be accessed via an API.
	 *
	 * @return array Enabled card types.
	 */
	private function get_enabled_card_types() {
		if ( ! $this->is_woopayments_enabled() ) {
			return array();
		}

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
		$assets_path = 'assets/images/payment-methods-cards/';
		$icon_path   = WC_ABSPATH . $assets_path . $card_type . '.svg';
		$icon_url    = \plugins_url( $assets_path . $card_type . '.svg', WC_PLUGIN_FILE );

		return file_exists( $icon_path ) ? $icon_url : '';
	}

	/**
	 * Get other payment method icons from available gateways.
	 *
	 * @return array Other payment method icons.
	 */
	private function get_other_payment_method_icons() {
		$available_gateways    = WC()->payment_gateways->get_available_payment_gateways();
		$other_payment_methods = array();

		if ( empty( $available_gateways ) ) {
			return $other_payment_methods;
		}

		foreach ( $available_gateways as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				if ( 'woocommerce_payments' === $gateway->id ) {
					continue;
				}

				$icon_url = '';
				if ( is_callable( array( $gateway, 'get_icon_url' ) ) ) {
					$icon_url = $gateway->get_icon_url();
				}
				if ( ! empty( $icon_url ) ) {
					$other_payment_methods[] = array(
						'name' => $gateway->get_title(),
						'icon' => $icon_url,
					);
				}
			}
		}

		usort(
			$other_payment_methods,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return $other_payment_methods;
	}

	/**
	 * Get the available payment methods.
	 *
	 * @return array Available payment methods.
	 */
	private function get_available_payment_methods() {
		$enabled_cards   = array_values( $this->get_enabled_card_types() );
		$payment_methods = array_merge( $enabled_cards, $this->get_other_payment_method_icons() );
		return $payment_methods;
	}
}
