<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Mini Cart class.
 *
 * @internal
 */
class MiniCartContents extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-contents';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 *
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
	 * @param string $key Data to get, or default to everything.
	 *
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		// The frontend script is a dependency of the Mini Cart block so it's
		// already lazy-loaded.
		return null;
	}

	/**
	 * Render the markup for the Mini Cart contents block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 *
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		if ( is_admin() || WC()->is_rest_api_request() ) {
			// In the editor we will display the placeholder, so no need to
			// print the markup.
			return '';
		}

		return $content;
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_assets( array $attributes ) {
		parent::enqueue_assets( $attributes );
		$text_color = StyleAttributesUtils::get_text_color_class_and_style( $attributes );
		$bg_color   = StyleAttributesUtils::get_background_color_class_and_style( $attributes );

		$styles = array(
			array(
				'selector'   => '.wc-block-mini-cart__drawer .components-modal__header',
				'properties' => array(
					array(
						'property' => 'color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
				),
			),
			array(
				'selector'   => '.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-cart.wc-block-components-button',
				'properties' => array(
					array(
						'property' => 'color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
					array(
						'property' => 'border-color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
				),
			),
			array(
				'selector'   => '.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-checkout',
				'properties' => array(
					array(
						'property' => 'color',
						'value'    => $bg_color ? $bg_color['value'] : false,
					),
					array(
						'property' => 'border-color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
					array(
						'property' => 'background-color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
				),
			),
		);

		$parsed_style = '';

		foreach ( $styles as $style ) {
			$properties = array_filter(
				$style['properties'],
				function( $property ) {
					return $property['value'];
				}
			);

			if ( ! empty( $properties ) ) {
				$parsed_style .= $style['selector'] . '{' . PHP_EOL;
				foreach ( $properties as $property ) {
					$parsed_style .= $property['property'] . ':' . $property['value'] . ';' . PHP_EOL;
				}
				$parsed_style .= '}' . PHP_EOL;
			}
		}

		wp_add_inline_style(
			'wc-blocks-style',
			$parsed_style
		);
	}
}
