<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-checkbox-list` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;

const WOOCOMMERCE_PRODUCT_FILTER_CHECKBOX_LIST_DISPLAY_LIMIT = 15;

/**
 * Check whether any selectable item has visual swatch data.
 *
 * @since 11.0.0
 *
 * @param array $items Selectable items.
 * @return bool Whether any item has visual swatch data.
 */
function woocommerce_product_filter_checkbox_list_has_visual_swatches( array $items ): bool {
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
function woocommerce_product_filter_checkbox_list_get_item_swatch_style( array $item ): string {
	$visual = isset( $item['visual'] ) && is_array( $item['visual'] ) ? $item['visual'] : array();

	return VisualAttributeTermMeta::get_swatch_style( $visual );
}

/**
 * Render the static checkbox mark SVG.
 *
 * @return string Checkbox mark SVG.
 */
function woocommerce_product_filter_checkbox_list_get_checkbox_svg(): string {
	return '<svg class="wc-block-product-filter-checkbox-list__mark" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.25 1.19922L3.75 6.69922L1 3.94922" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Render the static rating stars SVG.
 *
 * @return string Rating stars SVG.
 */
function woocommerce_product_filter_checkbox_list_get_stars_svg(): string {
	$star_path = '<path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>';

	return sprintf(
		'<svg class="wc-block-product-filter-checkbox-list__stars-svg" width="120" height="24" viewBox="0 0 120 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">%1$s<g transform="translate(24, 0)">%1$s</g><g transform="translate(48, 0)">%1$s</g><g transform="translate(72, 0)">%1$s</g><g transform="translate(96, 0)">%1$s</g></svg>',
		$star_path
	);
}

/**
 * Renders the `woocommerce/product-filter-checkbox-list` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_checkbox_list( array $attributes, string $content, $block ): string {
	if ( ! $block instanceof WP_Block || empty( $block->context['woocommerce/selectableItems'] ) ) {
		return '';
	}

	$block_context   = is_array( $block->context['woocommerce/selectableItems'] ) ? $block->context['woocommerce/selectableItems'] : array();
	$items           = is_array( $block_context['items'] ?? null ) ? $block_context['items'] : array();
	$store_namespace = is_string( $block_context['storeNamespace'] ?? null ) ? $block_context['storeNamespace'] : 'woocommerce/product-filters';
	$filter_type     = is_string( $block_context['filterType'] ?? null ) ? $block_context['filterType'] : '';
	$display_limit   = WOOCOMMERCE_PRODUCT_FILTER_CHECKBOX_LIST_DISPLAY_LIMIT;
	$is_rating       = 'rating' === $filter_type;
	$classes         = '';
	$style           = '';

	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-checkbox-list' ) ) ) {
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
		'data-wp-interactive' => 'woocommerce/product-filter-checkbox-list',
		'data-wp-context'     => false === $context_json ? '{}' : $context_json,
		'class'               => is_string( $classes ) ? esc_attr( $classes ) : '',
	);

	if ( is_string( $style ) && '' !== $style ) {
		$wrapper_attributes['style'] = esc_attr( $style ) . ';';
	}

	$checkbox_svg            = woocommerce_product_filter_checkbox_list_get_checkbox_svg();
	$stars_svg               = woocommerce_product_filter_checkbox_list_get_stars_svg();
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
	$first_item              = reset( $items );
	$show_counts             = is_array( $first_item ) && array_key_exists( 'count', $first_item );
	$has_visual_swatches     = woocommerce_product_filter_checkbox_list_has_visual_swatches( $items );
	$group_label             = is_scalar( $block_context['groupLabel'] ?? null ) ? (string) $block_context['groupLabel'] : '';

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<fieldset>
			<?php if ( '' !== $group_label ) : ?>
				<legend class="screen-reader-text"><?php echo esc_html( $group_label ); ?></legend>
			<?php endif; ?>
			<div class="wc-block-product-filter-checkbox-list__items">
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
					<div
						class="wc-block-product-filter-checkbox-list__item"
						data-wp-each-child
						<?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-wp-bind--hidden="context.item.hidden"
					>
						<label
							class="wc-block-product-filter-checkbox-list__label"
							for="<?php echo esc_attr( $item_id ); ?>"
						>
							<span class="wc-block-product-filter-checkbox-list__input-wrapper">
								<input
									id="<?php echo esc_attr( $item_id ); ?>"
									class="wc-block-product-filter-checkbox-list__input"
									type="checkbox"
									<?php if ( '' !== $item_aria_label ) : ?>
										aria-label="<?php echo esc_attr( $item_aria_label ); ?>"
									<?php endif; ?>
									value="<?php echo esc_attr( $item_value ); ?>"
									<?php checked( ! empty( $item['selected'] ) ); ?>
									<?php disabled( ! empty( $item['disabled'] ) ); ?>
									data-wp-bind--checked="context.item.selected"
									data-wp-bind--disabled="context.item.disabled"
									data-wp-on--change="actions.toggle"
								>
								<?php echo $checkbox_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="wc-block-product-filter-checkbox-list__text-wrapper">
								<?php if ( $is_rating ) : ?>
									<?php $rating_style = sprintf( 'width: %s%%', intval( $item_value ) * 20 ); ?>
									<span
										class="wc-block-product-filter-checkbox-list__stars"
										aria-label="<?php echo esc_attr( $item_aria_label ); ?>"
										style="<?php echo esc_attr( $rating_style ); ?>"
										data-wp-bind--style="woocommerce/product-filter-checkbox-list::state.ratingStyle"
									>
										<?php echo $stars_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
								<?php else : ?>
									<?php if ( $has_visual_swatches ) : ?>
										<?php
										$swatch_style = woocommerce_product_filter_checkbox_list_get_item_swatch_style( $item );
										$has_visual   = '' !== $swatch_style;
										?>
										<span
											class="wc-block-product-filter-checkbox-list__color-swatch<?php echo ! $has_visual ? ' is-empty' : ''; ?>"
											<?php if ( $has_visual ) : ?>
												style="<?php echo esc_attr( $swatch_style ); ?>"
											<?php endif; ?>
											aria-hidden="true"
										></span>
									<?php endif; ?>
									<span class="wc-block-product-filter-checkbox-list__text">
										<?php echo esc_html( $item_label ); ?>
									</span>
								<?php endif; ?>
								<?php if ( isset( $item['count'] ) ) : ?>
									<span class="wc-block-product-filter-checkbox-list__count">
										(<span data-wp-text="context.item.count"><?php echo esc_html( $item_count ); ?></span>)
									</span>
								<?php endif; ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
				<template
					data-wp-each--item="state.items"
					data-wp-each-key="context.item.id"
				>
					<div
						class="wc-block-product-filter-checkbox-list__item"
						data-wp-bind--hidden="context.item.hidden"
					>
						<label
							class="wc-block-product-filter-checkbox-list__label"
							data-wp-bind--for="context.item.id"
						>
							<span class="wc-block-product-filter-checkbox-list__input-wrapper">
								<input
									class="wc-block-product-filter-checkbox-list__input"
									type="checkbox"
									data-wp-bind--id="context.item.id"
									data-wp-bind--aria-label="context.item.ariaLabel"
									data-wp-bind--value="context.item.value"
									data-wp-bind--checked="context.item.selected"
									data-wp-bind--disabled="context.item.disabled"
									data-wp-on--change="actions.toggle"
								>
								<?php echo $checkbox_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="wc-block-product-filter-checkbox-list__text-wrapper">
								<?php if ( $is_rating ) : ?>
									<span
										class="wc-block-product-filter-checkbox-list__stars"
										data-wp-bind--aria-label="context.item.ariaLabel"
										data-wp-bind--style="woocommerce/product-filter-checkbox-list::state.ratingStyle"
									>
										<?php echo $stars_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
								<?php else : ?>
									<?php if ( $has_visual_swatches ) : ?>
										<span
											class="wc-block-product-filter-checkbox-list__color-swatch"
											data-wp-class--is-empty="woocommerce/product-filter-checkbox-list::state.isColorSwatchEmpty"
											data-wp-bind--style="woocommerce/product-filter-checkbox-list::state.colorSwatchStyle"
											aria-hidden="true"
										></span>
									<?php endif; ?>
									<span
										class="wc-block-product-filter-checkbox-list__text"
										data-wp-text="context.item.label"
									></span>
								<?php endif; ?>
								<?php if ( $show_counts ) : ?>
									<span class="wc-block-product-filter-checkbox-list__count">
										(<span data-wp-text="context.item.count"></span>)
									</span>
								<?php endif; ?>
							</span>
						</label>
					</div>
				</template>
			</div>
			<?php if ( $hidden_count > 0 ) : ?>
				<div class="wc-block-product-filter-checkbox-list__show-more">
					<button
						type="button"
						class="wc-block-product-filter-checkbox-list__show-more-button"
						data-wp-on--click="actions.showAll"
						data-wp-bind--hidden="context.isExpanded"
					>
						<?php
						/* translators: %d: number of hidden items */
						echo esc_html( sprintf( __( 'Show %d more', 'woocommerce' ), $hidden_count ) );
						?>
					</button>
				</div>
			<?php endif; ?>
		</fieldset>
	</div>
	<?php
	$output = ob_get_clean();
	return false === $output ? '' : $output;
}

/**
 * Registers the `woocommerce/product-filter-checkbox-list` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_checkbox_list(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_checkbox_list',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_checkbox_list' );
