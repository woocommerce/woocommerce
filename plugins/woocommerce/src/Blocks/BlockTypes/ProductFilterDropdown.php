<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Dropdown block (native select).
 */
final class ProductFilterDropdown extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-dropdown';

	/**
	 * Plain-text label for a select option (no HTML in `<option>`).
	 *
	 * @param array $item Selectable item from context.
	 * @return string
	 */
	private function get_option_text( array $item ): string {
		if ( ! empty( $item['ariaLabel'] ) && is_string( $item['ariaLabel'] ) ) {
			return $item['ariaLabel'];
		}
		if ( isset( $item['label'] ) && is_string( $item['label'] ) ) {
			return wp_strip_all_tags( $item['label'] );
		}
		return '';
	}

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( empty( $block->context['woocommerceSelectableItems'] ) ) {
			return '';
		}

		$selectable_items = $block->context['woocommerceSelectableItems'];
		$items            = is_array( $selectable_items['items'] ?? null ) ? $selectable_items['items'] : array();
		$store_namespace  = $selectable_items['storeNamespace'] ?? 'woocommerce/product-filters';

		if ( array() === $items ) {
			return '';
		}

		$first       = reset( $items );
		$filter_type = ( is_array( $first ) && ! empty( $first['type'] ) ) ? (string) $first['type'] : '';

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filter-dropdown',
			'data-wp-context'     => (string) wp_json_encode(
				array(
					'storeNamespace' => $store_namespace,
					'filterItemType' => $filter_type,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
		);

		$select_id   = wp_unique_id( 'wc-block-product-filter-dropdown-' );
		$show_counts = is_array( $first ) && array_key_exists( 'count', $first );
		$aria_label  = ! empty( $selectable_items['groupLabel'] )
			? (string) $selectable_items['groupLabel']
			: __( 'Product filter', 'woocommerce' );

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<fieldset class="wc-block-product-filter-dropdown__fieldset">
				<?php if ( ! empty( $selectable_items['groupLabel'] ) ) : ?>
					<legend class="screen-reader-text"><?php echo esc_html( $selectable_items['groupLabel'] ); ?></legend>
				<?php endif; ?>
				<select
					class="wc-block-product-filter-dropdown__select"
					id="<?php echo esc_attr( $select_id ); ?>"
					aria-label="<?php echo esc_attr( $aria_label ); ?>"
					data-wp-bind--value="woocommerce/product-filter-dropdown::state.selectValue"
					data-wp-on--change="actions.onDropdownChange"
				>
					<option value="">
						<?php esc_html_e( 'Select an option', 'woocommerce' ); ?>
					</option>
					<?php foreach ( $items as $item ) : ?>
						<?php
						if ( ! is_array( $item ) ) {
							continue;
						}
						$option_label = $this->get_option_text( $item );
						if ( empty( $option_label ) ) {
							continue;
						}
						?>
						<option
							value="<?php echo esc_attr( isset( $item['value'] ) ? (string) $item['value'] : '' ); ?>"
							<?php selected( ! empty( $item['selected'] ), true ); ?>
							<?php disabled( ! empty( $item['disabled'] ) ); ?>
						>
							<?php
							$option_output = $option_label;
							if ( $show_counts && isset( $item['count'] ) ) {
								$option_output .= ' (' . (string) $item['count'] . ')';
							}
							echo esc_html( $option_output );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</fieldset>
		</div>
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}
}
