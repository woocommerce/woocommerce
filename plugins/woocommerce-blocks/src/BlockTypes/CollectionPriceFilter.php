<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
		if (
			is_admin() ||
			empty( $block->context['collectionData'] ) ||
			empty( $block->context['collectionData']['price_range'] )
		) {
			return $content;
		}

		$price_range = $block->context['collectionData']['price_range'];

		$wrapper_attributes  = get_block_wrapper_attributes();
		$min_range           = $price_range['min_price'] / 10 ** $price_range['currency_minor_unit'];
		$max_range           = $price_range['max_price'] / 10 ** $price_range['currency_minor_unit'];
		$min_price           = intval( get_query_var( self::MIN_PRICE_QUERY_VAR, $min_range ) );
		$max_price           = intval( get_query_var( self::MAX_PRICE_QUERY_VAR, $max_range ) );
		$formatted_min_price = wc_price( $min_price, array( 'decimals' => 0 ) );
		$formatted_max_price = wc_price( $max_price, array( 'decimals' => 0 ) );

		$data = array(
			'minPrice'          => $min_price,
			'maxPrice'          => $max_price,
			'minRange'          => $min_range,
			'maxRange'          => $max_range,
			'formattedMinPrice' => $formatted_min_price,
			'formattedMaxPrice' => $formatted_max_price,
		);

		wc_store(
			array(
				'state' => array(
					'filters' => $data,
				),
			)
		);

		list (
			'showInputFields' => $show_input_fields,
			'inlineInput' => $inline_input
		) = $attributes;

		// Max range shouldn't be 0.
		if ( ! $max_range ) {
			return $content;
		}

		// CSS variables for the range bar style.
		$__low       = 100 * ( $min_price - $min_range ) / ( $max_range - $min_range );
		$__high      = 100 * ( $max_price - $min_range ) / ( $max_range - $min_range );
		$range_style = "--low: $__low%; --high: $__high%";

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $show_input_fields && $inline_input ? 'inline-input' : '',
			)
		);

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
			<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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
