<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Dropdown block (native select).
 */
final class Dropdown extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'dropdown';

	/**
	 * Plain-text label for a select option (no HTML in `<option>`).
	 *
	 * @param array $item Selectable item from context.
	 * @return string
	 */
	private function get_option_text( array $item ): string {
		if ( isset( $item['label'] ) && is_string( $item['label'] ) ) {
			return wp_strip_all_tags( $item['label'] );
		}
		if ( ! empty( $item['ariaLabel'] ) && is_string( $item['ariaLabel'] ) ) {
			return $item['ariaLabel'];
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
		if ( empty( $block->context['woocommerce/selectableItems'] ) ) {
			return '';
		}

		$selectable_items = $block->context['woocommerce/selectableItems'];
		$items            = is_array( $selectable_items['items'] ?? null ) ? $selectable_items['items'] : array();
		$store_namespace  = is_string( $selectable_items['storeNamespace'] ?? null ) ? $selectable_items['storeNamespace'] : 'woocommerce/add-to-cart-with-options';

		if ( empty( $items ) ) {
			return '';
		}

		$attribute_id       = $block->context['woocommerce/attributeId'] ?? '';
		$has_external_label = is_string( $attribute_id ) && '' !== $attribute_id;
		$select_id          = $has_external_label
			? $attribute_id
			: wp_unique_id( 'wc-block-dropdown-' );

		$wrapper_attributes = array(
			'class'               => 'wc-block-dropdown',
			'data-wp-interactive' => 'woocommerce/dropdown',
			'data-wp-context'     => (string) wp_json_encode(
				array(
					'storeNamespace' => $store_namespace,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
		);

		$aria_label = isset( $selectable_items['groupLabel'] ) && is_string( $selectable_items['groupLabel'] ) ? $selectable_items['groupLabel'] : __( 'Choose an option', 'woocommerce' );

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<fieldset class="wc-block-dropdown__fieldset">
				<?php if ( ! $has_external_label ) : ?>
					<legend class="screen-reader-text"><?php echo esc_html( __( 'Choose an option', 'woocommerce' ) ); ?></legend>
				<?php endif; ?>
				<select
					class="wc-block-dropdown__select"
					id="<?php echo esc_attr( $select_id ); ?>"
					<?php if ( $has_external_label ) : ?>
						aria-labelledby="<?php echo esc_attr( $attribute_id . '_label' ); ?>"
					<?php else : ?>
						aria-label="<?php echo esc_attr( $aria_label ); ?>"
					<?php endif; ?>
					data-wp-bind--value="state.selectValue"
					data-wp-on--change="actions.onDropdownChange"
				>
					<option value="">
						<?php echo esc_html( __( 'Choose an option', 'woocommerce' ) ); ?>
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
						$item_id    = isset( $item['id'] ) && is_string( $item['id'] ) ? $item['id'] : '';
						$item_value = isset( $item['value'] ) && is_string( $item['value'] ) ? $item['value'] : '';
						?>
						<option
							id="<?php echo esc_attr( $item_id ); ?>"
							value="<?php echo esc_attr( $item_value ); ?>"
							<?php disabled( ! empty( $item['disabled'] ) ); ?>
							<?php
							if ( ! empty( $item['hidden'] ) ) :
								?>
							hidden
								<?php
							endif;
							if ( ! empty( $item['selected'] ) ) :
								?>
								selected
								<?php
							endif;
							?>
							data-wp-each-child
						>
							<?php echo esc_html( $option_label ); ?>
						</option>
					<?php endforeach; ?>
					<template
						data-wp-interactive="<?php echo esc_attr( $store_namespace ); ?>"
						data-wp-each--item="state.selectableItems"
						data-wp-each-key="context.item.id"
					>
						<option
							data-wp-bind--value="context.item.value"
							data-wp-bind--disabled="context.item.disabled"
							data-wp-bind--hidden="context.item.hidden"
							data-wp-text="context.item.label"
						></option>
					</template>
				</select>
			</fieldset>
		</div>
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}
}
