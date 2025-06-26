<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

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
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'cardIcons', $this->get_enabled_card_types() );
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
		$enabled_card_types    = $this->get_enabled_card_types();
		$other_payment_methods = $this->get_other_payment_method_icons();

		if ( empty( $enabled_card_types ) && empty( $other_payment_methods ) ) {
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
		$output .= '<div class="wp-block-woocommerce-payment-method-icons">';
		$output .= $this->render_card_types( $attributes );
		$output .= '</div>';
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
		$output = '';

		$enabled_card_types         = array_values( $this->get_enabled_card_types() );
		$other_payment_method_icons = $this->get_other_payment_method_icons();
		$all_payment_methods        = array_merge( $enabled_card_types, $other_payment_method_icons );
		$number_of_icons            = $attributes['numberOfIcons'] ?? 0;
		$number_of_icons            = 0 === $number_of_icons ? count( $all_payment_methods ) : max( 0, min( intval( $number_of_icons ), count( $all_payment_methods ) ) );

		if ( ! empty( $enabled_card_types ) ) {
			for ( $i = 0; $i < $number_of_icons; $i++ ) {
				$payment_method = $all_payment_methods[ $i ];
				$output        .= '<div class="wp-block-woocommerce-payment-method-icons__item">';
				$output        .= '<span class="wp-block-woocommerce-payment-method-icons__icon" style="background-image: url(\'' . \esc_url( $payment_method['icon'] ) . '\');" role="img" aria-label="' . \esc_attr( $payment_method['name'] ) . '"></span>';
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
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

		return isset( $payment_gateways['woocommerce_payments'] ) &&
				$payment_gateways['woocommerce_payments']->enabled === 'yes';
	}

	/**
	 * Get the enabled card types.
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
		$plugin_path = 'woocommerce-payments/assets/images/payment-method-icons/';
		if ( ! \is_dir( \WP_PLUGIN_DIR . '/' . \dirname( $plugin_path ) ) ) {
			return '';
		}

		$icon_url = \plugins_url( $plugin_path . $card_type . '.svg' );
		return $icon_url;
	}

	/**
	 * Get other payment method icons from available gateways.
	 *
	 * @return array Other payment method icons.
	 */
	private function get_other_payment_method_icons() {
		$available_gateways    = WC()->payment_gateways->payment_gateways();
		$other_payment_methods = array();

		if ( empty( $available_gateways ) ) {
			return $other_payment_methods;
		}

		foreach ( $available_gateways as $gateway ) {
			if ( 'woocommerce_payments' === $gateway->id ) {
				continue;
			}

			if ( 'yes' === $gateway->enabled ) {
				$icon_url = $gateway->get_icon_url();
				if ( ! empty( $icon_url ) ) {
					$other_payment_methods[] = array(
						'name' => $gateway->get_title(),
						'icon' => $icon_url,
					);
				}
			}
		}

		return $other_payment_methods;
	}

	/**
	 * Check if WooPayments is enabled and has card types enabled.
	 *
	 * @return bool WooPayments enabled and has card types enabled.
	 */
	private function is_woo_payments_enabled() {
		if ( ! class_exists( 'WC_Payments' ) ) {
			return false;
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( empty( $available_gateways ) ) {
			return false;
		}

		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( 'woocommerce_payments' === $gateway_id && 'yes' === $gateway->enabled && in_array( 'card', $gateway->get_option( 'upe_enabled_payment_method_ids' ), true ) ) {
				return true;
			}
		}

		return false;
	}
}
