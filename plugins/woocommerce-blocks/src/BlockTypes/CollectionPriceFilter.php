<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use stdClass;

/**
 * CollectionPriceFilter class.
 */
final class CollectionPriceFilter extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'collection-price-filter';

	const MIN_PRICE_QUERY_VAR = 'min_price';
	const MAX_PRICE_QUERY_VAR = 'max_price';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Short circuit if the collection data isn't ready yet.
		if ( empty( $block->context['collectionData']['price_range'] ) ) {
			return $content;
		}

		$price_range = $block->context['collectionData']['price_range'];

		$wrapper_attributes = get_block_wrapper_attributes();
		$min_range          = $price_range->min_price / 10 ** $price_range->currency_minor_unit;
		$max_range          = $price_range->max_price / 10 ** $price_range->currency_minor_unit;
		$min_price          = intval( get_query_var( self::MIN_PRICE_QUERY_VAR, $min_range ) );
		$max_price          = intval( get_query_var( self::MAX_PRICE_QUERY_VAR, $max_range ) );

		$data = array(
			'minPrice' => $min_price,
			'maxPrice' => $max_price,
			'minRange' => $min_range,
			'maxRange' => $max_range,
		);

		wc_store(
			array(
				'state' => array(
					'filters' => $data,
				),
			)
		);

		$filter_reset_button = sprintf(
			' <button class="wc-block-components-filter-reset-button" data-wc-on--click="actions.filters.reset">
				<span aria-hidden="true">%1$s</span>
				<span class="screen-reader-text">%2$s</span>
			</button>',
			__( 'Reset', 'woo-gutenberg-products-block' ),
			__( 'Reset filter', 'woo-gutenberg-products-block' ),
		);

		return sprintf(
			'<div %1$s>
				<div class="controls">%2$s</div>
				<div class="actions">
					%3$s
				</div>
			</div>',
			$wrapper_attributes,
			$this->get_price_slider( $data, $attributes ),
			$filter_reset_button
		);
	}

	/**
	 * Get the price slider HTML.
	 *
	 * @param array $store_data The data passing to Interactivity Store.
	 * @param array $attributes Block attributes.
	 */
	private function get_price_slider( $store_data, $attributes ) {
		list (
			'showInputFields' => $show_input_fields,
			'inlineInput' => $inline_input
		) = $attributes;
		list (
			'minPrice' => $min_price,
			'maxPrice' => $max_price,
			'minRange' => $min_range,
			'maxRange' => $max_range,
		) = $store_data;

		// CSS variables for the range bar style.
		$__low       = 100 * $min_price / $max_range;
		$__high      = 100 * $max_price / $max_range;
		$range_style = "--low: $__low%; --high: $__high%";

		$formatted_min_price = wc_price( $min_price, array( 'decimals' => 0 ) );
		$formatted_max_price = wc_price( $max_price, array( 'decimals' => 0 ) );

		$classes = $show_input_fields && $inline_input ? 'price-slider inline-input' : 'price-slider';

		$price_min = $show_input_fields ?
			sprintf(
				'<input
					class="min"
					type="text"
					value="%d"
					data-wc-bind--value="state.filters.minPrice"
					data-wc-on--input="actions.filters.setMinPrice"
					data-wc-on--change="actions.filters.updateProducts"
				/>',
				esc_attr( $min_price )
			) : sprintf(
				'<span data-wc-text="state.filters.formattedMinPrice">%s</span>',
				esc_attr( $formatted_min_price )
			);

		$price_max = $show_input_fields ?
			sprintf(
				'<input
					class="max"
					type="text"
					value="%d"
					data-wc-bind--value="state.filters.maxPrice"
					data-wc-on--input="actions.filters.setMaxPrice"
					data-wc-on--change="actions.filters.updateProducts"
				/>',
				esc_attr( $max_price )
			) : sprintf(
				'<span data-wc-text="state.filters.formattedMaxPrice">%s</span>',
				esc_attr( $formatted_max_price )
			);

		ob_start();
		?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<div
					class="range"
					style="<?php echo esc_attr( $range_style ); ?>"
					data-wc-bind--style="state.filters.rangeStyle"
				>
					<div class="range-bar"></div>
					<input
						type="range"
						class="min"
						min="<?php echo esc_attr( $min_range ); ?>"
						max="<?php echo esc_attr( $max_range ); ?>"
						value="<?php echo esc_attr( $min_price ); ?>"
						data-wc-bind--max="state.filters.maxRange"
						data-wc-bind--value="state.filters.minPrice"
						data-wc-class--active="state.filters.isMinActive"
						data-wc-on--input="actions.filters.setMinPrice"
						data-wc-on--change="actions.filters.updateProducts"
					>
					<input
						type="range"
						class="max"
						min="<?php echo esc_attr( $min_range ); ?>"
						max="<?php echo esc_attr( $max_range ); ?>"
						value="<?php echo esc_attr( $max_price ); ?>"
						data-wc-bind--max="state.filters.maxRange"
						data-wc-bind--value="state.filters.maxPrice"
						data-wc-class--active="state.filters.isMaxActive"
						data-wc-on--input="actions.filters.setMaxPrice"
						data-wc-on--change="actions.filters.updateProducts"
					>
				</div>
				<div class="text">
					<?php // $price_min and $price_max are escapsed in the sprintf() calls above. ?>
					<?php echo $price_min; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $price_max; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}
