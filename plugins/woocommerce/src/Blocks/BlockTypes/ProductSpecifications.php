<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Enums\ProductType;

/**
 * ProductSpecifications class.
 */
class ProductSpecifications extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-specifications';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! isset( $block->context['postId'] ) ) {
			return '';
		}

		$product = wc_get_product( $block->context['postId'] );

		if ( ! $product ) {
			return '';
		}

		$product_data = array();

		// Get display settings with defaults.
		$show_weight     = isset( $attributes['showWeight'] ) ? $attributes['showWeight'] : true;
		$show_dimensions = isset( $attributes['showDimensions'] ) ? $attributes['showDimensions'] : true;
		$show_attributes = isset( $attributes['showAttributes'] ) ? $attributes['showAttributes'] : true;

		if ( $show_weight && $product->has_weight() ) {
			$product_data['weight'] = array(
				'label' => __( 'Weight', 'woocommerce' ),
				'value' => wc_format_weight( $product->get_weight() ),
			);
		}

		if ( $show_dimensions && $product->has_dimensions() ) {
			$product_data['dimensions'] = array(
				'label' => __( 'Dimensions', 'woocommerce' ),
				'value' => wc_format_dimensions( $product->get_dimensions( false ) ),
			);
		}

		$is_interactive = $product->is_type( ProductType::VARIABLE );

		if ( $is_interactive ) {
			$variations                = $product->get_available_variations( 'objects' );
			$formatted_variations_data = array();
			foreach ( $variations as $variation ) {
				$formatted_variations_data[ $variation->get_id() ] = array(
					'weight'     => wc_format_weight( $variation->get_weight() ),
					'dimensions' => html_entity_decode( wc_format_dimensions( $variation->get_dimensions( false ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				);
			}

			wp_interactivity_config(
				'woocommerce',
				array(
					'products' => array(
						$product->get_id() => array(
							'weight'     => $product_data['weight']['value'] ?? '',
							'dimensions' => html_entity_decode( $product_data['dimensions']['value'] ?? '', ENT_QUOTES, get_bloginfo( 'charset' ) ),
							'variations' => $formatted_variations_data,
						),
					),
				)
			);
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

						if ( $attribute_taxonomy->attribute_public ) {
							$values[] = '<a href="' . esc_url( get_term_link( $attribute_value->term_id, $attribute->get_name() ) ) . '" rel="tag">' . $value_name . '</a>';
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

		ob_start();

		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => 'wp-block-table' )
		);
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
							<?php if ( $is_interactive && in_array( $product_attribute_key, array( 'weight', 'dimensions' ), true ) ) : ?>
								<td class="wp-block-product-specifications-item__value" data-wp-interactive="woocommerce/product-elements" data-wp-text="state.productData.<?php echo esc_attr( $product_attribute_key ); ?>">
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

		return ob_get_clean();
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		$deps = parent::get_block_type_style();

		if ( ! is_array( $deps ) ) {
			return array( 'wp-block-table' );
		}

		return array_merge( array( 'wp-block-table' ), $deps );
	}
}
