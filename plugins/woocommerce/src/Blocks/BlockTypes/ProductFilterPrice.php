<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Price Block.
 */
final class ProductFilterPrice extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-price';

	const MIN_PRICE_QUERY_VAR = 'min_price';
	const MAX_PRICE_QUERY_VAR = 'max_price';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();

		add_filter( 'collection_filter_query_param_keys', array( $this, 'get_filter_query_param_keys' ), 10, 2 );
		add_filter( 'collection_active_filters_data', array( $this, 'register_active_filters_data' ), 10, 2 );
	}

	/**
	 * Register the query param keys.
	 *
	 * @param array $filter_param_keys The active filters data.
	 * @param array $url_param_keys    The query param parsed from the URL.
	 *
	 * @return array Active filters param keys.
	 */
	public function get_filter_query_param_keys( $filter_param_keys, $url_param_keys ) {
		$price_param_keys = array_filter(
			$url_param_keys,
			function( $param ) {
				return self::MIN_PRICE_QUERY_VAR === $param || self::MAX_PRICE_QUERY_VAR === $param;
			}
		);

		return array_merge(
			$filter_param_keys,
			$price_param_keys
		);
	}

	/**
	 * Register the active filters data.
	 *
	 * @param array $data   The active filters data.
	 * @param array $params The query param parsed from the URL.
	 * @return array Active filters data.
	 */
	public function register_active_filters_data( $data, $params ) {
		$min_price           = intval( $params[ self::MIN_PRICE_QUERY_VAR ] ?? 0 );
		$max_price           = intval( $params[ self::MAX_PRICE_QUERY_VAR ] ?? 0 );
		$formatted_min_price = $min_price ? wp_strip_all_tags( wc_price( $min_price, array( 'decimals' => 0 ) ) ) : null;
		$formatted_max_price = $max_price ? wp_strip_all_tags( wc_price( $max_price, array( 'decimals' => 0 ) ) ) : null;

		if ( ! $formatted_min_price && ! $formatted_max_price ) {
			return $data;
		}

		if ( $formatted_min_price && $formatted_max_price ) {
			$title = sprintf(
				/* translators: %1$s and %2$s are the formatted minimum and maximum prices respectively. */
				__( 'Between %1$s and %2$s', 'woocommerce' ),
				$formatted_min_price,
				$formatted_max_price
			);
		}

		if ( ! $formatted_min_price ) {
			/* translators: %s is the formatted maximum price. */
			$title = sprintf( __( 'Up to %s', 'woocommerce' ), $formatted_max_price );
		}

		if ( ! $formatted_max_price ) {
			/* translators: %s is the formatted minimum price. */
			$title = sprintf( __( 'From %s', 'woocommerce' ), $formatted_min_price );
		}

		$data['price'] = array(
			'type'  => __( 'Price', 'woocommerce' ),
			'items' => array(
				array(
					'title'      => $title,
					'attributes' => array(
						'data-wc-on--click' => "{$this->get_full_block_name()}::actions.reset",
					),
				),
			),
		);

		return $data;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}

		$price_range         = $block->context['collectionData']['price_range'] ?? [];
		$min_range           = $price_range['min_price'] ?? 0;
		$max_range           = $price_range['max_price'] ?? 0;
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

		list (
			'showInputFields' => $show_input_fields,
			'inlineInput' => $inline_input
		) = $attributes;

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => $show_input_fields && $inline_input ? 'inline-input' : '',
				'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ) ),
				'data-wc-context'     => wp_json_encode( $data ),
			)
		);

		if ( $min_range === $max_range || ! $max_range ) {
			return sprintf(
				'<div %s></div>',
				$wrapper_attributes
			);
		}

		// CSS variables for the range bar style.
		$__low       = 100 * ( $min_price - $min_range ) / ( $max_range - $min_range );
		$__high      = 100 * ( $max_price - $min_range ) / ( $max_range - $min_range );
		$range_style = "--low: $__low%; --high: $__high%";

		$price_min = $show_input_fields ?
			sprintf(
				'<input
					class="min"
					type="text"
					value="%d"
					data-wc-bind--value="context.minPrice"
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
					data-wc-bind--value="context.maxPrice"
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
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="filter-controls">
					<div
						class="range"
						style="<?php echo esc_attr( $range_style ); ?>"
						data-wc-bind--style="state.rangeStyle"
					>
						<div class="range-bar"></div>
						<input
							type="range"
							class="min"
							name="min"
							min="<?php echo esc_attr( $min_range ); ?>"
							max="<?php echo esc_attr( $max_range ); ?>"
							value="<?php echo esc_attr( $min_price ); ?>"
							data-wc-bind--min="context.minRange"
							data-wc-bind--max="context.maxRange"
							data-wc-bind--value="context.minPrice"
							data-wc-on--change="actions.updateProducts"
						>
						<input
							type="range"
							class="max"
							name="max"
							min="<?php echo esc_attr( $min_range ); ?>"
							max="<?php echo esc_attr( $max_range ); ?>"
							value="<?php echo esc_attr( $max_price ); ?>"
							data-wc-bind--min="context.minRange"
							data-wc-bind--max="context.maxRange"
							data-wc-bind--value="context.maxPrice"
							data-wc-on--change="actions.updateProducts"
						>
					</div>
					<div class="text">
						<?php // $price_min and $price_max are escaped in the sprintf() calls above. ?>
						<?php echo $price_min; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $price_max; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}
