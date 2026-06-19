<?php
/**
 * Server-side rendering of the `woocommerce/product-specifications` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductType;

/**
 * Renders the `woocommerce/product-specifications` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_specifications( $attributes, $content, $block ): string {
	if ( ! $block instanceof WP_Block || ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$product = wc_get_product( (int) $block->context['postId'] );

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$product_data    = array();
	$show_weight     = $attributes['showWeight'] ?? true;
	$show_dimensions = $attributes['showDimensions'] ?? true;
	$show_attributes = $attributes['showAttributes'] ?? true;

	if ( $show_weight && $product->has_weight() ) {
		$product_data['weight'] = array(
			'label'     => __( 'Weight', 'woocommerce' ),
			'value'     => wc_format_weight( (float) $product->get_weight() ),
			'api_field' => 'formatted_weight',
		);
	}

	if ( $show_dimensions && $product->has_dimensions() ) {
		$dimensions = $product->get_dimensions( false );

		if ( is_array( $dimensions ) ) {
			$product_data['dimensions'] = array(
				'label'     => __( 'Dimensions', 'woocommerce' ),
				'value'     => wc_format_dimensions( $dimensions ),
				'api_field' => 'formatted_dimensions',
			);
		}
	}

	$is_interactive = $product->is_type( ProductType::VARIABLE );

	if ( $is_interactive ) {
		wp_enqueue_script_module( 'woocommerce/product-elements' );
	}

	if ( $show_attributes ) {
		foreach ( $product->get_attributes() as $attribute ) {
			$values = array();

			if ( $attribute->is_taxonomy() ) {
				$attribute_taxonomy = $attribute->get_taxonomy_object();
				$attribute_values   = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

				foreach ( $attribute_values as $attribute_value ) {
					$value_name = esc_html( $attribute_value->name );
					$term_link  = get_term_link( $attribute_value->term_id, $attribute->get_name() );

					if ( $attribute_taxonomy && $attribute_taxonomy->attribute_public && ! is_wp_error( $term_link ) ) {
						$values[] = '<a href="' . esc_url( $term_link ) . '" rel="tag">' . $value_name . '</a>';
					} else {
						$values[] = $value_name;
					}
				}
			} else {
				$values = $attribute->get_options();

				foreach ( $values as &$value ) {
					$value = make_clickable( esc_html( $value ) );
				}
			}

			$product_data[ 'attribute_' . sanitize_title_with_dashes( $attribute->get_name() ) ] = array(
				'label' => wc_attribute_label( $attribute->get_name() ),
				'value' => wpautop( wptexturize( implode( ', ', $values ) ) ),
			);
		}
	}

	if ( empty( $product_data ) ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array( 'class' => 'wp-block-table' )
	);

	ob_start();
	?>
	<figure <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<table>
			<thead class="screen-reader-text">
				<tr>
					<th><?php esc_html_e( 'Attributes', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Value', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $product_data as $product_attribute_key => $product_attribute ) : ?>
					<tr class="wp-block-product-specifications-item wp-block-product-specifications-item-<?php echo esc_attr( $product_attribute_key ); ?>">
						<th scope="row" class="wp-block-product-specifications-item__label">
							<?php echo wp_kses_post( $product_attribute['label'] ); ?>
						</th>
						<?php if ( $is_interactive && isset( $product_attribute['api_field'] ) ) : ?>
							<td class="wp-block-product-specifications-item__value" data-wp-interactive="woocommerce/products" data-wp-text="state.productInContext.<?php echo esc_attr( $product_attribute['api_field'] ); ?>">
								<?php echo wp_kses_post( $product_attribute['value'] ); ?>
							</td>
						<?php else : ?>
							<td class="wp-block-product-specifications-item__value">
								<?php echo wp_kses_post( $product_attribute['value'] ); ?>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</figure>
	<?php

	$output = ob_get_clean();

	return is_string( $output ) ? $output : '';
}

/**
 * Registers the `woocommerce/product-specifications` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_specifications(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_specifications',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_specifications' );
