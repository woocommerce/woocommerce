<?php
/**
 * Server-side rendering of the `woocommerce/payment-method-icons` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Renders the `woocommerce/payment-method-icons` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered payment method icons block.
 */
function render_block_woocommerce_payment_method_icons( $attributes, $content, $block ) {
	$payment_methods = get_block_woocommerce_payment_method_icons_available_payment_methods();

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
	$output .= get_block_woocommerce_payment_method_icons_html( $attributes );
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}

/**
 * Renders the payment method icon elements.
 *
 * @since 11.0.0
 *
 * @param array $attributes Block attributes.
 * @return string Rendered payment method icon elements.
 */
function get_block_woocommerce_payment_method_icons_html( array $attributes ): string {
	$output              = '';
	$all_payment_methods = get_block_woocommerce_payment_method_icons_available_payment_methods();
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
 * Checks if WooPayments is enabled.
 *
 * @since 11.0.0
 *
 * @return bool WooPayments enabled.
 */
function is_block_woocommerce_payment_method_icons_woopayments_enabled(): bool {
	$payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();

	return isset( $payment_gateways['woocommerce_payments'] ) && 'yes' === $payment_gateways['woocommerce_payments']->enabled;
}

/**
 * Gets the enabled card types for WooPayments.
 *
 * Note: This uses hardcoded cards based on the default card types provided by
 * WooPayments. This should be updated when these icons can be accessed via an API.
 *
 * @since 11.0.0
 *
 * @return array Enabled card types.
 */
function get_block_woocommerce_payment_method_icons_enabled_card_types(): array {
	if ( ! is_block_woocommerce_payment_method_icons_woopayments_enabled() ) {
		return array();
	}

	$card_types = array(
		'visa'       => array(
			'name' => 'Visa',
			'icon' => get_block_woocommerce_payment_method_icons_card_type_icon_url( 'visa' ),
		),
		'mastercard' => array(
			'name' => 'Mastercard',
			'icon' => get_block_woocommerce_payment_method_icons_card_type_icon_url( 'mastercard' ),
		),
		'amex'       => array(
			'name' => 'American Express',
			'icon' => get_block_woocommerce_payment_method_icons_card_type_icon_url( 'amex' ),
		),
		'discover'   => array(
			'name' => 'Discover',
			'icon' => get_block_woocommerce_payment_method_icons_card_type_icon_url( 'discover' ),
		),
		'jcb'        => array(
			'name' => 'JCB',
			'icon' => get_block_woocommerce_payment_method_icons_card_type_icon_url( 'jcb' ),
		),
	);

	return $card_types;
}

/**
 * Gets the card type icon URL.
 *
 * @since 11.0.0
 *
 * @param string $card_type Card type.
 * @return string Card type icon URL.
 */
function get_block_woocommerce_payment_method_icons_card_type_icon_url( string $card_type ): string {
	$assets_path = 'assets/images/payment-methods-cards/';
	$icon_path   = WC_ABSPATH . $assets_path . $card_type . '.svg';
	$icon_url    = \plugins_url( $assets_path . $card_type . '.svg', WC_PLUGIN_FILE );

	return file_exists( $icon_path ) ? $icon_url : '';
}

/**
 * Gets other payment method icons from available gateways.
 *
 * @since 11.0.0
 *
 * @return array Other payment method icons.
 */
function get_block_woocommerce_payment_method_icons_other_payment_method_icons(): array {
	$available_gateways    = WC()->payment_gateways()->get_available_payment_gateways();
	$other_payment_methods = array();

	if ( empty( $available_gateways ) ) {
		return $other_payment_methods;
	}

	foreach ( $available_gateways as $gateway ) {
		if ( ! $gateway instanceof WC_Payment_Gateway ) {
			continue;
		}

		if ( 'yes' === $gateway->enabled ) {
			if ( 'woocommerce_payments' === $gateway->id ) {
				continue;
			}

			$icon_url = '';
			$callback = array( $gateway, 'get_icon_url' );
			if ( is_callable( $callback ) ) {
				$icon_url = call_user_func( $callback );
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
 * Gets the available payment methods.
 *
 * @since 11.0.0
 *
 * @return array Available payment methods.
 */
function get_block_woocommerce_payment_method_icons_available_payment_methods(): array {
	$enabled_cards   = array_values( get_block_woocommerce_payment_method_icons_enabled_card_types() );
	$payment_methods = array_merge( $enabled_cards, get_block_woocommerce_payment_method_icons_other_payment_method_icons() );
	return $payment_methods;
}

/**
 * Adds payment method icon settings to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_payment_method_icons_asset_data(): void {
	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );
	$asset_data_registry->add( 'availablePaymentMethods', get_block_woocommerce_payment_method_icons_available_payment_methods() );
}

/**
 * Registers the `woocommerce/payment-method-icons` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_payment_method_icons(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_payment_method_icons',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_payment_method_icons' );
add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_payment_method_icons_asset_data' );
