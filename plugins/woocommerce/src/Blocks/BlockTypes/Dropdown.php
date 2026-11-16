<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;

/**
 * Dropdown block for single or multiple selection.
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
			return $item['label'];
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

		if ( empty( $items ) ) {
			return '';
		}

		$styles = $attributes['style'] ?? array();
		foreach ( array(
			'textColor'       => 'text',
			'backgroundColor' => 'background',
		) as $attribute => $property ) {
			if ( ! empty( $attributes[ $attribute ] ) ) {
				$styles['color'][ $property ] = 'var:preset|color|' . $attributes[ $attribute ];
			}
		}
		if ( ! empty( $attributes['borderColor'] ) ) {
			$styles['border']['color'] = 'var:preset|color|' . $attributes['borderColor'];
		}
		$field_props = wp_parse_args(
			wp_style_engine_get_styles( $styles ),
			array(
				'css'        => '',
				'classnames' => '',
			)
		);

		if ( 'multiple' === ( $selectable_items['selectionMode'] ?? 'single' ) ) {
			return $this->render_multiple( $selectable_items, $field_props );
		}

		return $this->render_single( $selectable_items, $block, $field_props );
	}

	/**
	 * Render a native single-selection dropdown.
	 *
	 * @param array     $selectable_items Selectable items context.
	 * @param \WP_Block $block            Block instance.
	 * @param array     $field_props      Field classes and styles.
	 * @return string
	 */
	private function render_single( array $selectable_items, \WP_Block $block, array $field_props ): string {
		$items              = $selectable_items['items'];
		$store_namespace    = is_string( $selectable_items['storeNamespace'] ?? null ) ? $selectable_items['storeNamespace'] : 'woocommerce/add-to-cart-with-options';
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
					class="<?php echo esc_attr( trim( 'wc-block-dropdown__select ' . $field_props['classnames'] ) ); ?>"
					style="<?php echo esc_attr( $field_props['css'] ); ?>"
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

	/**
	 * Summarize the first two selected labels and the remaining count.
	 *
	 * @param array $items Selectable items.
	 * @return string
	 */
	private function get_selection_summary( array $items ): string {
		$selected = array_values( array_filter( $items, static fn( $item ) => ! empty( $item['selected'] ) ) );
		$summary  = implode( ', ', array_map( array( $this, 'get_option_text' ), array_slice( $selected, 0, 2 ) ) );
		if ( count( $selected ) > 2 ) {
			return $summary . ' +' . ( count( $selected ) - 2 );
		}
		return '' !== $summary ? $summary : __( 'Choose options', 'woocommerce' );
	}

	/**
	 * Render a checkbox dropdown without changing the parent's selection behavior.
	 *
	 * @param array $selectable_items Selectable items context.
	 * @param array $field_props Field classes and styles.
	 * @return string
	 */
	private function render_multiple( array $selectable_items, array $field_props ): string {
		$items = $selectable_items['items'];
		$id    = wp_unique_id( 'wc-block-dropdown-' );

		wp_interactivity_state(
			'woocommerce/dropdown',
			array(
				'triggerStyle'       => static function () {
					$context = wp_interactivity_get_context();
					return $context['fieldStyle'] . ( $context['isOpen'] ? 'border-bottom-left-radius:0;border-bottom-right-radius:0;' : '' );
				},
				'items'              => static function () {
					return wp_interactivity_get_context()['items'];
				},
				'selectionSummary'   => function () {
					$context = wp_interactivity_get_context();
					$parent  = wp_interactivity_get_context( $context['storeNamespace'] );
					$items   = $context['items'];
					if ( isset( $parent['activeFilters'], $parent['filterType'] ) ) {
						$labels = array_column( $items, 'label', 'value' );
						$items = array_map(
							static fn( $item ) => array(
								'label'    => $item['label'] ?? $labels[ $item['value'] ] ?? $item['activeLabel'],
								'selected' => true,
							),
							array_filter( $parent['activeFilters'], static fn( $item ) => $item['type'] === $parent['filterType'] )
						);
					}
					return $this->get_selection_summary( $items );
				},
				'colorSwatchStyle'   => static function () {
					return VisualAttributeTermMeta::get_swatch_style( wp_interactivity_get_context()['item']['visual'] ?? array() );
				},
				'isColorSwatchEmpty' => static function () {
					return empty( wp_interactivity_get_context()['item']['visual']['value'] );
				},
			)
		);

		$wrapper_attributes = array(
			'class'                      => 'wc-block-dropdown wc-block-dropdown--multiple',
			'data-wp-interactive'        => 'woocommerce/dropdown',
			'data-wp-on--keydown'        => 'actions.onKeydown',
			'data-wp-on--focusout'       => 'actions.closeOnFocusOutside',
			'data-wp-on-document--click' => 'actions.closeOnClickOutside',
			'data-wp-init'               => 'callbacks.initColors',
			'data-wp-context'            => (string) wp_json_encode(
				array(
					'storeNamespace' => $selectable_items['storeNamespace'],
					'placeholder'    => __( 'Choose options', 'woocommerce' ),
					'items'          => $items,
					'isOpen'         => false,
					'fieldStyle'     => $field_props['css'],
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
		);
		$has_visual         = count( array_filter( $items, static fn( $item ) => isset( $item['visual'] ) ) ) > 0;
		$has_counts         = count( array_filter( $items, static fn( $item ) => isset( $item['count'] ) ) ) > 0;

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<button type="button" class="<?php echo esc_attr( trim( 'wc-block-dropdown__trigger ' . $field_props['classnames'] ) ); ?>" style="<?php echo esc_attr( $field_props['css'] ); ?>" data-wp-bind--style="state.triggerStyle" id="<?php echo esc_attr( $id ); ?>" aria-expanded="false" data-wp-bind--aria-expanded="context.isOpen" aria-controls="<?php echo esc_attr( $id . '-options' ); ?>" data-wp-on--click="actions.togglePanel">
				<span class="screen-reader-text"><?php echo esc_html( ( $selectable_items['groupLabel'] ?? __( 'Filter options', 'woocommerce' ) ) . ': ' ); ?></span>
				<span class="wc-block-dropdown__summary" data-wp-text="state.selectionSummary"><?php echo esc_html( $this->get_selection_summary( $items ) ); ?></span>
				<span class="wc-block-dropdown__chevron" aria-hidden="true"></span>
			</button>
			<div class="<?php echo esc_attr( trim( 'wc-block-dropdown__panel wc-block-product-filter-checkbox-list ' . $field_props['classnames'] ) ); ?>" style="<?php echo esc_attr( $field_props['css'] . 'border-top-width:0;border-top-left-radius:0;border-top-right-radius:0;' ); ?>" id="<?php echo esc_attr( $id . '-options' ); ?>" hidden data-wp-bind--hidden="!context.isOpen">
				<fieldset>
					<legend class="screen-reader-text"><?php echo esc_html( $selectable_items['groupLabel'] ?? __( 'Filter options', 'woocommerce' ) ); ?></legend>
					<template data-wp-each--item="state.items" data-wp-each-key="context.item.id">
						<div class="wc-block-product-filter-checkbox-list__item" data-wp-bind--hidden="context.item.hidden">
							<label class="wc-block-product-filter-checkbox-list__label">
								<span class="wc-block-product-filter-checkbox-list__input-wrapper">
									<input type="checkbox" class="wc-block-product-filter-checkbox-list__input" data-wp-bind--value="context.item.value" data-wp-bind--checked="context.item.selected" data-wp-bind--disabled="context.item.disabled" data-wp-on--change="actions.toggleItem" />
									<svg class="wc-block-product-filter-checkbox-list__mark" viewBox="0 0 10 8" fill="none" aria-hidden="true"><path d="M9.25 1.19922L3.75 6.69922L1 3.94922" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</span>
								<span class="wc-block-product-filter-checkbox-list__text-wrapper">
									<?php if ( $has_visual ) : ?>
										<span class="wc-block-product-filter-checkbox-list__color-swatch" data-wp-bind--style="state.colorSwatchStyle" data-wp-class--is-empty="state.isColorSwatchEmpty" aria-hidden="true"></span>
									<?php endif; ?>
									<span class="wc-block-product-filter-checkbox-list__text" data-wp-text="context.item.label"></span>
									<?php if ( $has_counts ) : ?>
										<span class="wc-block-product-filter-checkbox-list__count">(<span data-wp-text="context.item.count"></span>)</span>
									<?php endif; ?>
								</span>
							</label>
						</div>
					</template>
				</fieldset>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
