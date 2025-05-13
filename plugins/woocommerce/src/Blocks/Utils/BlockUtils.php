<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use WP_Block_Type_Registry;
use WP_Block_Supports;

/**
 * Utility methods used for creating static WooCommerce Blocks on the server side.
 */
class BlockUtils {
	public static function get_block_wrapper_attributes( $block, $extra_wrapper_attributes = array() ) {
		$previous_block_to_render = WP_Block_Supports::$block_to_render;

		WP_Block_Supports::$block_to_render = $block;

		$block_wrapper_attributes = get_block_wrapper_attributes( $extra_wrapper_attributes );

		WP_Block_Supports::$block_to_render = $previous_block_to_render;

		return $block_wrapper_attributes;
	}

	public static function filter_block_attributes( $block_type_name, $attrs ) {
		$block_type            = WP_Block_Type_Registry::get_instance()->get_registered( $block_type_name );
		$known_attribute_names = $block_type->get_attributes();

		$known_attributes = array();

		foreach ( $known_attribute_names as $attribute_name => $attribute_definition ) {
			if ( isset( $attrs[ $attribute_name ] ) ) {
				$known_attributes[ $attribute_name ] = $attrs[ $attribute_name ];
			} elseif ( isset( $attribute_definition['default'] ) ) {
				$known_attributes[ $attribute_name ] = $attribute_definition['default'];
			}
		}
		return $known_attributes;
	}
}
