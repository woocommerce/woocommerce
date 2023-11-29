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
			'minPrice' => $min_price,
			'maxPrice' => $max_price,
			'minRange' => $min_range,
			'maxRange' => $max_range,
		);

		wc_initial_state(
			'woocommerce/collection-price-filter',
			$data
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

		$data_directive = wp_json_encode( array( 'namespace' => 'woocommerce/collection-price-filter' ) );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => $show_input_fields && $inline_input ? 'inline-input' : '',
				'data-wc-interactive' => $data_directive,
			)
		);

		$price_min = $show_input_fields ?
			sprintf(
				'<input
					class="min"
					type="text"
					value="%d"
					data-wc-bind--value="state.minPrice"
					data-wc-on--input="actions.setMinPrice"
					data-wc-on--change="actions.updateProducts"
				/>',
				esc_attr( $min_price )
			) : sprintf(
				'<span data-wc-text="state.formattedMinPrice">%s</span>',
				// Not escaped, as this is HTML.
				$formatted_min_price
			);

		$price_max = $show_input_fields ?
			sprintf(
				'<input
					class="max"
					type="text"
					value="%d"
					data-wc-bind--value="state.maxPrice"
					data-wc-on--input="actions.setMaxPrice"
					data-wc-on--change="actions.updateProducts"
				/>',
				esc_attr( $max_price )
			) : sprintf(
				'<span data-wc-text="state.formattedMaxPrice">%s</span>',
				// Not escaped, as this is HTML.
				$formatted_max_price
			);

		ob_start();
		?>
			<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div
					class="range"
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
						data-wc-bind--max="state.maxRange"
						data-wc-bind--value="state.minPrice"
						data-wc-class--active="state.isMinActive"
						data-wc-on--input="actions.setMinPrice"
						data-wc-on--change="actions.updateProducts"
					>
					<input
						type="range"
						class="max"
						min="<?php echo esc_attr( $min_range ); ?>"
						max="<?php echo esc_attr( $max_range ); ?>"
						value="<?php echo esc_attr( $max_price ); ?>"
						data-wc-bind--max="state.maxRange"
						data-wc-bind--value="state.maxPrice"
						data-wc-class--active="state.isMaxActive"
						data-wc-on--input="actions.setMaxPrice"
						data-wc-on--change="actions.updateProducts"
					>
				</div>
				<div class="text">
					<?php // $price_min and $price_max are escaped in the sprintf() calls above. ?>
					<?php echo $price_min; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $price_max; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}
