<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Gold Miner Block.
 */
final class ProductFilterGoldMiner extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-gold-miner';

	const DISPLAY_LIMIT = 15;

	const NUGGET_POSITIONS = array(
		array( 'x' => 15, 'y' => 25 ),
		array( 'x' => 45, 'y' => 15 ),
		array( 'x' => 75, 'y' => 35 ),
		array( 'x' => 25, 'y' => 55 ),
		array( 'x' => 60, 'y' => 45 ),
		array( 'x' => 85, 'y' => 20 ),
		array( 'x' => 35, 'y' => 40 ),
		array( 'x' => 70, 'y' => 60 ),
		array( 'x' => 10, 'y' => 45 ),
		array( 'x' => 50, 'y' => 30 ),
		array( 'x' => 20, 'y' => 70 ),
		array( 'x' => 65, 'y' => 70 ),
		array( 'x' => 40, 'y' => 65 ),
		array( 'x' => 80, 'y' => 55 ),
		array( 'x' => 55, 'y' => 75 ),
	);

	const NUGGET_SIZES = array(
		'medium',
		'large',
		'small',
		'medium',
		'large',
		'small',
		'medium',
		'small',
		'large',
		'medium',
		'small',
		'medium',
		'large',
		'small',
		'medium',
	);

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
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
		$has_more_items  = count( $items ) > $display_limit;
		$hidden_count    = max( 0, count( $items ) - $display_limit );
		$classes         = '';
		$style           = '';

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-gold-miner' ) ) ) {
			$classes = $tags->get_attribute( 'class' );
			$style   = $tags->get_attribute( 'style' );
		}

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filter-gold-miner',
			'data-wp-context'     => (string) wp_json_encode(
				array(
					'storeNamespace'   => $store_namespace,
					'displayLimit'     => $display_limit,
					'clawState'        => 'swinging',
					'clawAngle'        => 0,
					'clawLength'       => 30,
					'targetNuggetIndex' => -1,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
			'data-wp-init'        => 'callbacks.initGame',
			'data-wp-on--click'   => 'actions.dropClaw',
			'class'               => esc_attr( $classes ?? '' ),
		);

		if ( ! empty( $style ) ) {
			$wrapper_attributes['style'] = esc_attr( $style ) . ';';
		}

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<fieldset>
				<?php if ( ! empty( $block_context['groupLabel'] ) ) : ?>
					<legend class="screen-reader-text"><?php echo esc_html( $block_context['groupLabel'] ); ?></legend>
				<?php endif; ?>

				<div class="wc-block-product-filter-gold-miner__sky">
					<div class="wc-block-product-filter-gold-miner__miner">
						<div class="wc-block-product-filter-gold-miner__claw-pivot">
							<div class="wc-block-product-filter-gold-miner__claw-arm">
								<div class="wc-block-product-filter-gold-miner__claw-hook"></div>
							</div>
						</div>
					</div>
					<div class="wc-block-product-filter-gold-miner__instruction">
						<?php esc_html_e( 'Click to drop the claw!', 'woocommerce' ); ?>
					</div>
				</div>

				<div
					class="wc-block-product-filter-gold-miner__ground"
					data-wp-interactive="<?php echo esc_attr( $store_namespace ); ?>"
				>
					<div class="wc-block-product-filter-gold-miner__items">
						<?php
						$visible_items = array_slice( $items, 0, $display_limit, true );
						foreach ( $visible_items as $index => $item ) :
							$context_item = array_merge( $item, array( 'index' => $index ) );
							$pos_index    = $index % count( self::NUGGET_POSITIONS );
							$size         = self::NUGGET_SIZES[ $index % count( self::NUGGET_SIZES ) ];
							$pos          = self::NUGGET_POSITIONS[ $pos_index ];
							$selected_class = ! empty( $item['selected'] ) ? 'is-selected' : '';
							?>
							<div
								class="wc-block-product-filter-gold-miner__nugget wc-block-product-filter-gold-miner__nugget--<?php echo esc_attr( $size ); ?> <?php echo esc_attr( $selected_class ); ?>"
								style="left: <?php echo esc_attr( $pos['x'] ); ?>%; top: <?php echo esc_attr( $pos['y'] ); ?>%;"
								data-wp-each-child
								<?php echo wp_interactivity_data_wp_context( array( 'item' => $context_item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								data-wp-bind--hidden="woocommerce/product-filter-gold-miner::state.itemHidden"
								title="<?php echo esc_attr( $item['label'] ?? '' ); ?>"
							>
								<input
									class="wc-block-product-filter-gold-miner__nugget-input"
									type="checkbox"
									id="<?php echo esc_attr( $item['id'] ); ?>"
									<?php if ( ! empty( $item['ariaLabel'] ) ) : ?>
										aria-label="<?php echo esc_attr( $item['ariaLabel'] ); ?>"
									<?php endif; ?>
									value="<?php echo esc_attr( $item['value'] ); ?>"
									<?php checked( ! empty( $item['selected'] ) ); ?>
									<?php disabled( ! empty( $item['disabled'] ) ); ?>
									data-wp-bind--checked="context.item.selected"
									data-wp-bind--disabled="context.item.disabled"
									data-wp-on--change="actions.toggle"
									tabindex="-1"
								>
								<span class="wc-block-product-filter-gold-miner__nugget-label">
									<?php echo esc_html( $item['label'] ?? '' ); ?>
								</span>
							</div>
						<?php endforeach; ?>

						<template
							data-wp-each--item="state.selectableItems"
							data-wp-each-key="context.item.id"
						>
							<div
								class="wc-block-product-filter-gold-miner__nugget wc-block-product-filter-gold-miner__nugget--medium"
								data-wp-bind--hidden="woocommerce/product-filter-gold-miner::state.itemHidden"
							>
								<input
									class="wc-block-product-filter-gold-miner__nugget-input"
									type="checkbox"
									data-wp-bind--id="context.item.id"
									data-wp-bind--aria-label="context.item.ariaLabel"
									data-wp-bind--value="context.item.value"
									data-wp-bind--checked="context.item.selected"
									data-wp-bind--disabled="context.item.disabled"
									data-wp-on--change="actions.toggle"
									tabindex="-1"
								>
								<span
									class="wc-block-product-filter-gold-miner__nugget-label"
									data-wp-text="context.item.label"
								></span>
							</div>
						</template>
					</div>

					<?php if ( $has_more_items ) : ?>
						<div class="wc-block-product-filter-gold-miner__show-more">
							<button
								type="button"
								class="wc-block-product-filter-gold-miner__show-more-button"
								data-wp-on--click="woocommerce/product-filter-gold-miner::actions.showAll"
								data-wp-bind--hidden="woocommerce/product-filter-gold-miner::state.isExpanded"
							>
								<?php
								/* translators: %d: number of hidden items */
								echo esc_html( sprintf( __( 'Show %d more', 'woocommerce' ), $hidden_count ) );
								?>
							</button>
						</div>
					<?php endif; ?>

					<div class="wc-block-product-filter-gold-miner__score">
						⛏️ Gold Miner Filter
					</div>
				</div>
			</fieldset>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Disable the style handle for this block type. We use block.json to load the style.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
