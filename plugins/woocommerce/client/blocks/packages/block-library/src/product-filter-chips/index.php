<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-chips` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;

/**
 * Adds Product Filter Chips settings to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_product_filter_chips_asset_data(): void {
	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

	if ( ! $asset_data_registry->exists( 'globalStylesColors' ) ) {
		$asset_data_registry->add( 'globalStylesColors', wp_get_global_styles( array( 'color' ) ) );
	}
}

add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_product_filter_chips_asset_data' );

/**
 * Check whether any selectable item has visual swatch data.
 *
 * @since 11.0.0
 *
 * @param array $items Selectable items.
 * @return bool Whether any item has visual swatch data.
 */
function block_woocommerce_product_filter_chips_has_visual_swatches( array $items ): bool {
	foreach ( $items as $item ) {
		if ( is_array( $item ) && array_key_exists( 'visual', $item ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Build inline swatch style from item visual data.
 *
 * @since 11.0.0
 *
 * @param array $item Selectable item data.
 * @return string Inline swatch style.
 */
function block_woocommerce_product_filter_chips_get_item_swatch_style( array $item ): string {
	$visual = isset( $item['visual'] ) && is_array( $item['visual'] ) ? $item['visual'] : array();

	return VisualAttributeTermMeta::get_swatch_style( $visual );
}

/**
 * Renders the `woocommerce/product-filter-chips` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_chips( array $attributes, string $content, $block ): string {
	if ( ! $block instanceof WP_Block || empty( $block->context['woocommerce/selectableItems'] ) ) {
		return '';
	}

	$block_context   = is_array( $block->context['woocommerce/selectableItems'] ) ? $block->context['woocommerce/selectableItems'] : array();
	$items           = is_array( $block_context['items'] ?? null ) ? $block_context['items'] : array();
	$store_namespace = is_string( $block_context['storeNamespace'] ?? null ) ? $block_context['storeNamespace'] : 'woocommerce/product-filters';
	$display_limit   = 'woocommerce/product-filters' === $store_namespace ? 15 : 30;
	$classes         = '';
	$style           = '';

	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-chips' ) ) ) {
		$classes = $tags->get_attribute( 'class' );
		$style   = $tags->get_attribute( 'style' );
	}

	$context_json = wp_json_encode(
		array(
			'storeNamespace' => $store_namespace,
			'displayLimit'   => $display_limit,
			'isExpanded'     => false,
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive'  => 'woocommerce/product-filter-chips',
		'data-wp-init--colors' => 'callbacks.initColors',
		'data-wp-context'      => false === $context_json ? '{}' : $context_json,
		'class'                => is_string( $classes ) ? esc_attr( $classes ) : '',
	);

	if ( ! empty( $style ) && is_string( $style ) ) {
		$wrapper_attributes['style'] = esc_attr( $style ) . ';';
	}

	$attribute_id       = $block->context['woocommerce/attributeId'] ?? '';
	$has_external_label = is_string( $attribute_id ) && '' !== $attribute_id;
	$selection_mode     = $block_context['selectionMode'] ?? 'multiple';
	$selection_mode     = 'single' === $selection_mode ? 'single' : 'multiple';

	if ( $has_external_label ) {
		$wrapper_attributes['role']            = 'single' === $selection_mode ? 'radiogroup' : 'group';
		$wrapper_attributes['aria-labelledby'] = esc_attr( $attribute_id . '_label' );
	}

	$first_items             = array_slice( $items, 0, $display_limit, true );
	$overflow_items          = array_slice( $items, $display_limit );
	$overflow_selected_items = array_filter(
		$overflow_items,
		function ( $item ): bool {
			return is_array( $item ) && ! empty( $item['selected'] );
		}
	);
	$visible_items           = array_merge( $first_items, $overflow_selected_items );
	$hidden_count            = count( $items ) - count( $visible_items );

	$first_item          = reset( $items );
	$show_counts         = is_array( $first_item ) && array_key_exists( 'count', $first_item );
	$has_visual_swatches = block_woocommerce_product_filter_chips_has_visual_swatches( $items );
	$button_role         = 'single' === $selection_mode ? 'radio' : 'checkbox';
	$group_label         = is_scalar( $block_context['groupLabel'] ?? null ) ? (string) $block_context['groupLabel'] : '';

	if ( $has_visual_swatches && is_string( $classes ) && ! str_contains( $classes, 'is-style-swatch' ) ) {
		$classes                    .= ' is-style-swatch';
		$wrapper_attributes['class'] = esc_attr( $classes );
	}

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<fieldset class="wc-block-product-filter-chips__fieldset">
			<?php if ( '' !== $group_label && ! $has_external_label ) : ?>
				<legend class="screen-reader-text"><?php echo esc_html( $group_label ); ?></legend>
			<?php endif; ?>
			<div class="wc-block-product-filter-chips__items">
				<?php
				foreach ( $visible_items as $item ) :
					if ( ! is_array( $item ) ) {
						continue;
					}

					$item_id         = is_scalar( $item['id'] ?? null ) ? (string) $item['id'] : '';
					$item_aria_label = is_scalar( $item['ariaLabel'] ?? null ) ? (string) $item['ariaLabel'] : '';
					$item_label      = is_scalar( $item['label'] ?? null ) ? (string) $item['label'] : '';
					$item_value      = is_scalar( $item['value'] ?? null ) ? (string) $item['value'] : '';
					$item_count      = is_scalar( $item['count'] ?? null ) ? (string) $item['count'] : '';
					?>
					<button
						class="wc-block-product-filter-chips__item"
						type="button"
						role="<?php echo esc_attr( $button_role ); ?>"
						id="<?php echo esc_attr( $item_id ); ?>"
						<?php if ( '' !== $item_aria_label ) : ?>
							aria-label="<?php echo esc_attr( $item_aria_label ); ?>"
						<?php endif; ?>
						<?php if ( $has_visual_swatches ) : ?>
							title="<?php echo esc_attr( $item_label ); ?>"
						<?php endif; ?>
						value="<?php echo esc_attr( $item_value ); ?>"
						aria-checked="<?php echo ! empty( $item['selected'] ) ? 'true' : 'false'; ?>"
						<?php disabled( ! empty( $item['disabled'] ) ); ?>
						data-wp-each-child
						<?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-wp-bind--aria-checked="context.item.selected"
						data-wp-bind--disabled="context.item.disabled"
						data-wp-bind--hidden="context.item.hidden"
						data-wp-on--click="actions.toggle"
					>
						<span class="wc-block-product-filter-chips__label">
							<?php if ( $has_visual_swatches ) : ?>
								<?php
								$swatch_style = block_woocommerce_product_filter_chips_get_item_swatch_style( $item );
								$has_visual   = '' !== $swatch_style;
								?>
								<span
									class="wc-block-product-filter-chips__swatch<?php echo ! $has_visual ? ' wc-block-product-filter-chips__swatch--no-color' : ''; ?>"
									<?php if ( $has_visual ) : ?>
										style="<?php echo esc_attr( $swatch_style ); ?>"
									<?php endif; ?>
									aria-hidden="true"
								></span>
							<?php endif; ?>
							<span class="wc-block-product-filter-chips__text">
								<?php echo esc_html( $item_label ); ?>
							</span>
							<?php if ( isset( $item['count'] ) ) : ?>
								<span class="wc-block-product-filter-chips__count">
									(<span data-wp-text="context.item.count"><?php echo esc_html( $item_count ); ?></span>)
								</span>
							<?php endif; ?>
						</span>
					</button>
				<?php endforeach; ?>
				<template
					data-wp-each--item="state.items"
					data-wp-each-key="context.item.id"
				>
					<button
						class="wc-block-product-filter-chips__item"
						type="button"
						role="<?php echo esc_attr( $button_role ); ?>"
						data-wp-bind--id="context.item.id"
						data-wp-bind--aria-label="context.item.ariaLabel"
						<?php if ( $has_visual_swatches ) : ?>
							data-wp-bind--title="context.item.label"
						<?php endif; ?>
						data-wp-bind--value="context.item.value"
						data-wp-bind--aria-checked="context.item.selected"
						data-wp-bind--disabled="context.item.disabled"
						data-wp-bind--hidden="context.item.hidden"
						data-wp-on--click="actions.toggle"
					>
						<span class="wc-block-product-filter-chips__label">
							<?php if ( $has_visual_swatches ) : ?>
								<span
									class="wc-block-product-filter-chips__swatch"
									data-wp-class--wc-block-product-filter-chips__swatch--no-color="woocommerce/product-filter-chips::state.swatchHidden"
									data-wp-bind--style="woocommerce/product-filter-chips::state.swatchStyle"
									aria-hidden="true"
								></span>
							<?php endif; ?>
							<span
								class="wc-block-product-filter-chips__text"
								data-wp-text="context.item.label"
							></span>
							<?php if ( $show_counts ) : ?>
								<span class="wc-block-product-filter-chips__count">
									(<span data-wp-text="context.item.count"></span>)
								</span>
							<?php endif; ?>
						</span>
					</button>
				</template>
			</div>
			<?php if ( $hidden_count > 0 ) : ?>
				<button
					type="button"
					class="wc-block-product-filter-chips__show-more"
					data-wp-on--click="actions.showAll"
					data-wp-bind--hidden="context.isExpanded"
				>
					<?php
					/* translators: %d: number of hidden items */
					echo esc_html( sprintf( __( '+%d more', 'woocommerce' ), $hidden_count ) );
					?>
				</button>
			<?php endif; ?>
		</fieldset>
	</div>
	<?php
	$output = ob_get_clean();
	return false === $output ? '' : $output;
}

/**
 * Registers the `woocommerce/product-filter-chips` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_chips(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_chips',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_chips' );
