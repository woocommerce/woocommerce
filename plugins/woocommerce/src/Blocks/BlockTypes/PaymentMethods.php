<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Block;
use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;

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
		$payment_methods_from_server = PaymentUtils::get_enabled_payment_gateways();
		$formatted_payment_methods = array_reduce(
			$payment_methods_from_server,
			function ( $acc, $method ) {
				if ( $method->get_title() === 'Cards' ) {
					//var_dump( $method );
				}
				$acc[] = [
					'id'          => $method->id,
					'title'       => $method->get_title() !== '' ? $method->get_title() : $method->get_method_title(),
					'icon'       => $method->get_icon(),
				];
				return $acc;
			},
			[]
		);

		//$payment_methods = $attributes['formattedPaymentMethods'];
		$output = '';
		$show_as_icons = isset( $attributes['showAsIcons'] ) ? $attributes['showAsIcons'] : false;

		if ( ! empty( $formatted_payment_methods ) ) {
			$wrapper_attributes = get_block_wrapper_attributes( [ 'class' => 'wc-block-payment-methods' ] );
			$output .= sprintf( '<div %s>', $wrapper_attributes );
			$output .= '<ul class="wc-block-payment-methods__list">';
			foreach ( $formatted_payment_methods as $method ) {
				if ( $show_as_icons && ! empty( $method['icon'] ) ) {
					$output .= sprintf(
						'<li class="wc-block-payment-methods__list-item">%s</li>',
						$method['icon']
					);
				} else {
					$output .= sprintf(
						'<li class="wc-block-payment-methods__list-item">%s</li>',
						esc_html( $method['title'] )
					);
				}
			}
			$output .= '</ul>';
			$output .= '</div>';
		} else {
			$wrapper_attributes = get_block_wrapper_attributes( [ 'class' => 'wc-block-payment-methods wc-block-payment-methods--empty' ] );
			$output .= sprintf( '<div %s>', $wrapper_attributes );
			$output .= '</div>';
		}

		return $output;
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
