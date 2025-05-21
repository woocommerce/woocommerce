<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	public function render( $attributes, $content, $block ) {
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
			array( 'class' => 'wc-block-product-specifications' )
		);
		?>
		<table <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Product Specifications', 'woocommerce' ); ?>">
			<tbody>
				<?php foreach ( $product_data as $product_attribute_key => $product_attribute ) : ?>
					<tr class="wc-block-product-specifications-item wc-block-product-specifications-item__<?php echo esc_attr( $product_attribute_key ); ?>" scope="row">
						<th class="wc-block-product-specifications-item__label">
							<?php echo wp_kses_post( $product_attribute['label'] ); ?>
						</th>
						<td class="wc-block-product-specifications-item__value">
							<?php echo wp_kses_post( $product_attribute['value'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}
}
