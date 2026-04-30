<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Elements Block.
 *
 * Renders attribute terms as elemental cards (water, fire, earth, tree, metal)
 * with a themed animated background when selected. Element theme is matched
 * by the term value (slug or label segment).
 */
final class ProductFilterElements extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-elements';

	/**
	 * Default number of items to show before "Show more" button.
	 *
	 * @var int
	 */
	const DISPLAY_LIMIT = 15;

	/**
	 * Element keys recognised for theming.
	 *
	 * @var string[]
	 */
	const ELEMENT_KEYS = array( 'water', 'fire', 'earth', 'tree', 'metal' );

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

		$block_context   = $block->context['woocommerce/selectableItems'];
		$items           = is_array( $block_context['items'] ?? null ) ? $block_context['items'] : array();
		$store_namespace = $block_context['storeNamespace'] ?? 'woocommerce/product-filters';
		$display_limit   = self::DISPLAY_LIMIT;
		$classes         = '';
		$style           = '';

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-elements' ) ) ) {
			$classes = is_string( $tags->get_attribute( 'class' ) ) ? $tags->get_attribute( 'class' ) : '';
			$style   = is_string( $tags->get_attribute( 'style' ) ) ? $tags->get_attribute( 'style' ) : '';
		}

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filter-elements',
			'data-wp-context'     => (string) wp_json_encode(
				array(
					'storeNamespace' => $store_namespace,
					'displayLimit'   => $display_limit,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
			'class'               => esc_attr( $classes ),
		);

		if ( ! empty( $style ) ) {
			$wrapper_attributes['style'] = esc_attr( $style ) . ';';
		}

		$has_more_items = count( $items ) > $display_limit;
		$hidden_count   = max( 0, count( $items ) - $display_limit );
		$first_item     = reset( $items );
		$show_counts    = is_array( $first_item ) && array_key_exists( 'count', $first_item );

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<fieldset>
				<?php if ( ! empty( $block_context['groupLabel'] ) ) : ?>
					<legend class="screen-reader-text"><?php echo esc_html( $block_context['groupLabel'] ); ?></legend>
				<?php endif; ?>
				<div
					class="wc-block-product-filter-elements__items"
					data-wp-interactive="<?php echo esc_attr( $store_namespace ); ?>"
				>
					<?php
					$visible_items = array_slice( $items, 0, $display_limit, true );
					foreach ( $visible_items as $index => $item ) :
						$context_item = array_merge( $item, array( 'index' => $index ) );
						$element_key  = self::detect_element( (string) ( $item['value'] ?? '' ) );
						$item_class   = 'wc-block-product-filter-elements__item';
						if ( $element_key ) {
							$item_class .= ' is-element-' . $element_key;
						}
						?>
						<button
							class="<?php echo esc_attr( $item_class ); ?>"
							type="button"
							role="checkbox"
							id="<?php echo esc_attr( $item['id'] ); ?>"
							<?php if ( ! empty( $item['ariaLabel'] ) ) : ?>
								aria-label="<?php echo esc_attr( $item['ariaLabel'] ); ?>"
							<?php endif; ?>
							value="<?php echo esc_attr( $item['value'] ); ?>"
							aria-checked="<?php echo ! empty( $item['selected'] ) ? 'true' : 'false'; ?>"
							<?php disabled( ! empty( $item['disabled'] ) ); ?>
							<?php if ( $element_key ) : ?>
								data-element="<?php echo esc_attr( $element_key ); ?>"
							<?php endif; ?>
							data-wp-each-child
							<?php echo wp_interactivity_data_wp_context( array( 'item' => $context_item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							data-wp-bind--aria-checked="context.item.selected"
							data-wp-bind--disabled="context.item.disabled"
							data-wp-bind--hidden="woocommerce/product-filter-elements::state.itemHidden"
							data-wp-on--click="actions.toggle"
						>
							<span class="wc-block-product-filter-elements__label">
								<span class="wc-block-product-filter-elements__text">
									<?php echo esc_html( $item['label'] ); ?>
								</span>
								<?php if ( isset( $item['count'] ) ) : ?>
									<span class="wc-block-product-filter-elements__count">
										(<?php echo esc_html( $item['count'] ); ?>)
									</span>
								<?php endif; ?>
							</span>
							<span class="wc-block-product-filter-elements__effect" aria-hidden="true"></span>
						</button>
					<?php endforeach; ?>
					<template
						data-wp-each--item="state.selectableItems"
						data-wp-each-key="context.item.id"
					>
						<button
							class="wc-block-product-filter-elements__item"
							type="button"
							role="checkbox"
							data-wp-bind--id="context.item.id"
							data-wp-bind--aria-label="context.item.ariaLabel"
							data-wp-bind--value="context.item.value"
							data-wp-bind--aria-checked="context.item.selected"
							data-wp-bind--disabled="context.item.disabled"
							data-wp-bind--hidden="woocommerce/product-filter-elements::state.itemHidden"
							data-wp-on--click="actions.toggle"
						>
							<span class="wc-block-product-filter-elements__label">
								<span
									class="wc-block-product-filter-elements__text"
									data-wp-text="context.item.label"
								></span>
								<?php if ( $show_counts ) : ?>
									<span class="wc-block-product-filter-elements__count">
										(<span data-wp-text="context.item.count"></span>)
									</span>
								<?php endif; ?>
							</span>
							<span class="wc-block-product-filter-elements__effect" aria-hidden="true"></span>
						</button>
					</template>
				</div>
				<?php if ( $has_more_items ) : ?>
					<button
						type="button"
						class="wc-block-product-filter-elements__show-more"
						data-wp-on--click="actions.showAll"
						data-wp-bind--hidden="state.isExpanded"
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
		return (string) ob_get_clean();
	}

	/**
	 * Detect the element theme key from a term value.
	 *
	 * Matches by substring (case-insensitive) so admin slugs like
	 * "water-element" or labels like "Wood/Tree" still resolve.
	 *
	 * @param string $value Term value.
	 * @return string|null Element key or null if no match.
	 */
	private static function detect_element( string $value ): ?string {
		$needle = strtolower( $value );
		foreach ( self::ELEMENT_KEYS as $key ) {
			if ( '' !== $needle && false !== strpos( $needle, $key ) ) {
				return $key;
			}
		}
		return null;
	}
}
