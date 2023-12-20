<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * SingleProductTemplae class.
 *
 * @internal
 */
class SingleProductTemplate {

	/**
	 * Replace the first single product template block with the password form. Remove all other single product template blocks.
	 *
	 * @param array   $parsed_blocks Array of parsed block objects.
	 * @param boolean $is_already_replaced If the password form has already been added.
	 * @return array Parsed blocks
	 */
	private static function replace_first_single_product_template_block_with_password_form( $parsed_blocks, $is_already_replaced ) {
		// We want to replace the first single product template block with the password form. We also want to remove all other single product template blocks.
		// This array doesn't contains all the blocks. For example, it missing the breadcrumbs blocks: it doesn't make sense replace the breadcrumbs with the password form.
		$single_product_template_blocks = array( 'woocommerce/product-image-gallery', 'woocommerce/product-details', 'woocommerce/add-to-cart-form', 'woocommerce/product-meta', 'woocommerce/product-rating', 'woocommerce/product-price', 'woocommerce/related-products' );
		return array_reduce(
			$parsed_blocks,
			function( $carry, $block ) use ( $single_product_template_blocks ) {
				if ( in_array( $block['blockName'], $single_product_template_blocks, true ) ) {
					if ( $carry['is_already_replaced'] ) {
						return array(
							'blocks'              => $carry['blocks'],
							'html_block'          => null,
							'removed'             => true,
							'is_already_replaced' => true,

						);
					}

					return array(
						'blocks'              => $carry['blocks'],
						'html_block'          => parse_blocks( '<!-- wp:html -->' . get_the_password_form() . '<!-- /wp:html -->' )[0],
						'removed'             => false,
						'is_already_replaced' => $carry['is_already_replaced'],
					);

				}

				if ( isset( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) > 0 ) {
					$index              = 0;
					$new_inner_blocks   = array();
					$new_inner_contents = $block['innerContent'];
					foreach ( $block['innerContent'] as $inner_content ) {
						// Don't process the closing tag of the block.
						if ( count( $block['innerBlocks'] ) === $index ) {
							break;
						}

						$blocks                       = self::replace_first_single_product_template_block_with_password_form( array( $block['innerBlocks'][ $index ] ), $carry['is_already_replaced'] );
						$new_blocks                   = $blocks['blocks'];
						$html_block                   = $blocks['html_block'];
						$is_removed                   = $blocks['removed'];
						$carry['is_already_replaced'] = $blocks['is_already_replaced'];

						if ( isset( $html_block ) ) {
							$new_inner_blocks             = array_merge( $new_inner_blocks, $new_blocks, array( $html_block ) );
							$carry['is_already_replaced'] = true;
						} else {
							$new_inner_blocks = array_merge( $new_inner_blocks, $new_blocks );
						}

						if ( $is_removed ) {
							unset( $new_inner_contents[ $index ] );
							// The last element of the inner contents contains the closing tag of the block. We don't want to remove it.
							if ( $index + 1 < count( $new_inner_contents ) ) {
								unset( $new_inner_contents[ $index + 1 ] );
							}
							$new_inner_contents = array_values( $new_inner_contents );
						}

						$index++;
					}

					$block['innerBlocks']  = $new_inner_blocks;
					$block['innerContent'] = $new_inner_contents;

					return array(
						'blocks'              => array_merge( $carry['blocks'], array( $block ) ),
						'html_block'          => null,
						'removed'             => false,
						'is_already_replaced' => $carry['is_already_replaced'],
					);
				}

				return array(
					'blocks'              => array_merge( $carry['blocks'], array( $block ) ),
					'html_block'          => null,
					'removed'             => false,
					'is_already_replaced' => $carry['is_already_replaced'],
				);
			},
			array(
				'blocks'              => array(),
				'html_block'          => null,
				'removed'             => false,
				'is_already_replaced' => $is_already_replaced,
			)
		);
	}

	/**
	 * Add password form to the Single Product Template.
	 *
	 * @param string $content The content of the template.
	 * @return string
	 */
	public static function add_password_form( $content ) {
		$parsed_blocks     = parse_blocks( $content );
		$blocks            = self::replace_first_single_product_template_block_with_password_form( $parsed_blocks, false );
		$serialized_blocks = serialize_blocks( $blocks['blocks'] );

		return $serialized_blocks;
	}
}
