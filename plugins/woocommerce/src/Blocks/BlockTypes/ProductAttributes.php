<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductAttributes class.
 */
class ProductAttributes extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-attributes';

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

		$product_attributes = array();

		if ( $product->has_weight() ) {
			$product_attributes['weight'] = array(
				'label' => __( 'Weight', 'woocommerce' ),
				'value' => wc_format_weight( $product->get_weight() ),
			);
		}

		if ( $product->has_dimensions() ) {
			$product_attributes['dimensions'] = array(
				'label' => __( 'Dimensions', 'woocommerce' ),
				'value' => wc_format_dimensions( $product->get_dimensions( false ) ),
			);
		}

		$attributes = $product->get_attributes();
		foreach ( $attributes as $attribute ) {
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

			$product_attributes[ 'attribute_' . sanitize_title_with_dashes( $attribute->get_name() ) ] = array(
				'label' => wc_attribute_label( $attribute->get_name() ),
				'value' => wpautop( wptexturize( implode( ', ', $values ) ) ),
			);
		}

		if ( empty( $product_attributes ) ) {
			return '';
		}

		ob_start();

		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => 'wc-block-product-attributes' )
		);
		?>
		<table <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Product Attributes', 'woocommerce' ); ?>">
			<tbody>
				<?php foreach ( $product_attributes as $product_attribute_key => $product_attribute ) : ?>
					<tr class="wc-block-product-attributes-item wc-block-product-attributes-item__<?php echo esc_attr( $product_attribute_key ); ?>" scope="row">
						<th class="wc-block-product-attributes-item__label">
							<?php echo wp_kses_post( $product_attribute['label'] ); ?>
						</th>
						<td class="wc-block-product-attributes-item__value">
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
