<?php
/**
 * ProductFilterPriceSlider class.
 *
 * @package Automattic\WooCommerce\Blocks\BlockTypes
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductFilterPriceSlider class.
 */
class ProductFilterPriceSlider extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-price-slider';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( is_admin() || wp_doing_ajax() || empty( $block->context['filterData'] ) || empty( $block->context['filterData']['price'] ) ) {
			return '';
		}

		$actions    = $block->context['filterData']['actions'];
		$price_data = $block->context['filterData']['price'];
		$min_price  = $price_data['minPrice'];
		$max_price  = $price_data['maxPrice'];
		$min_range  = $price_data['minRange'];
		$max_range  = $price_data['maxRange'];

		if ( $min_range === $max_range ) {
			return;
		}

		$formatted_min_price = wc_price( $min_price, array( 'decimals' => 0 ) );
		$formatted_max_price = wc_price( $max_price, array( 'decimals' => 0 ) );

		$show_input_fields = $attributes['showInputFields'] ?? false;
		$inline_input      = $attributes['inlineInput'] ?? false;

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => 'wc-block-product-filter-price-slider',
				'data-wc-interactive' => wp_json_encode(
					array(
						'namespace' => $this->get_full_block_name(),
					),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				),
				'data-wc-context'     => wp_json_encode( $price_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			)
		);

		$content_class = 'wc-block-product-filter-price-slider__content';
		if ( $inline_input && $show_input_fields ) {
			$content_class .= ' wc-block-product-filter-price-slider__content--inline';
		}

		// CSS variables for the range bar style.
		$__low       = 100 * ( $min_price - $min_range ) / ( $max_range - $min_range );
		$__high      = 100 * ( $max_price - $min_range ) / ( $max_range - $min_range );
		$range_style = "--low: $__low%; --high: $__high%";

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="<?php echo esc_attr( $content_class ); ?>">
				<div class="wc-block-product-filter-price-slider__left text">
					<?php if ( $show_input_fields ) : ?>
						<input
							class="min"
							type="text"
							value="<?php echo esc_attr( $formatted_min_price ); ?>"
							data-wc-bind--value="state.formattedMinPrice"
							data-wc-on--focus="actions.selectInputContent"
							data-wc-on--change--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
							data-min-price="<?php echo esc_attr( $min_price ); ?>"
							data-wc-bind--data-min-price="context.minPrice"
						/>
					<?php else : ?>
						<span><?php echo wp_kses_post( $formatted_min_price ); ?></span>
					<?php endif; ?>
				</div>
				<div
					class="wc-block-product-filter-price-slider__range"
					style="<?php echo esc_attr( $range_style ); ?>"
					data-wc-bind--style="state.rangeStyle"
				>
					<div class="range-bar"></div>
					<input
						type="range"
						class="min"
						min="<?php echo esc_attr( $min_range ); ?>"
						max="<?php echo esc_attr( $max_range ); ?>"
						value="<?php echo esc_attr( $min_price ); ?>"
						data-wc-bind--value="context.minPrice"
						data-wc-bind--min="context.minRange"
						data-wc-bind--max="context.maxRange"
						data-wc-on--input="actions.updateRange"
						data-wc-on--mouseup--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
						data-wc-on--keyup--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
						data-wc-on--touchend--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
						data-min-price="<?php echo esc_attr( $min_price ); ?>"
						data-wc-bind--data-min-price="context.minPrice"
					/>
					<input
						type="range"
						class="max"
						min="<?php echo esc_attr( $min_range ); ?>"
						max="<?php echo esc_attr( $max_range ); ?>"
						value="<?php echo esc_attr( $max_price ); ?>"
						data-wc-bind--value="context.maxPrice"
						data-wc-bind--min="context.minRange"
						data-wc-bind--max="context.maxRange"
						data-wc-on--input="actions.updateRange"
						data-wc-on--mouseup--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
						data-wc-on--keyup--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
						data-wc-on--touchend--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
						data-max-price="<?php echo esc_attr( $max_price ); ?>"
						data-wc-bind--data-max-price="context.maxPrice"
					/>
				</div>
				<div class="wc-block-product-filter-price-slider__right text">
					<?php if ( $show_input_fields ) : ?>
						<input
							class="max"
							type="text"
							value="<?php echo esc_attr( $formatted_min_price ); ?>"
							data-wc-bind--value="state.formattedMaxPrice"
							data-wc-on--focus="actions.selectInputContent"
							data-wc-on--change--parent-action="<?php echo esc_attr( $actions['setPrices'] ); ?>"
							data-max-price="<?php echo esc_attr( $max_price ); ?>"
							data-wc-bind--data-max-price="context.maxPrice"
						/>
					<?php else : ?>
						<span><?php echo wp_kses_post( $formatted_max_price ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
