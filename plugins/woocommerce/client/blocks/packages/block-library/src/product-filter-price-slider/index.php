<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-price-slider` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-filter-price-slider` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_price_slider( array $attributes, string $content, $block ): string {
	if ( is_admin() || wp_doing_ajax() || ! $block instanceof WP_Block || empty( $block->context['woocommerce/rangeInput'] ) ) {
		return '';
	}

	$range_data = is_array( $block->context['woocommerce/rangeInput'] ) ? $block->context['woocommerce/rangeInput'] : array();
	$min_range  = intval( $range_data['min'] ?? 0 );
	$max_range  = intval( $range_data['max'] ?? 0 );
	$min_price  = intval( $range_data['currentMin'] ?? $min_range );
	$max_price  = intval( $range_data['currentMax'] ?? $max_range );

	if ( $min_range === $max_range ) {
		return '';
	}

	$classes = '';
	$style   = '';

	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-price-slider' ) ) ) {
		$classes = $tags->get_attribute( 'class' );
		$style   = $tags->get_attribute( 'style' );
	}

	$show_input_fields = isset( $attributes['showInputFields'] ) ? (bool) $attributes['showInputFields'] : true;
	$inline_input      = isset( $attributes['inlineInput'] ) ? (bool) $attributes['inlineInput'] : false;

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-price-slider' ),
		'class'               => is_string( $classes ) ? esc_attr( $classes ) : '',
	);

	if ( is_string( $style ) && '' !== $style ) {
		$wrapper_attributes['style'] = esc_attr( $style );
	}

	$content_class = 'wc-block-product-filter-price-slider__content';
	if ( $inline_input && $show_input_fields ) {
		$content_class .= ' wc-block-product-filter-price-slider__content--inline';
	}

	$range_low   = 100 * ( $min_price - $min_range ) / ( $max_range - $min_range );
	$range_high  = 100 * ( $max_price - $min_range ) / ( $max_range - $min_range );
	$range_style = "--low: {$range_low}%; --high: {$range_high}%";

	wp_interactivity_state(
		'woocommerce/product-filters',
		array(
			'rangeStyle' => $range_style,
		)
	);

	ob_start();
	?>
	<div class="wc-block-product-filter-price-slider__left text">
		<?php if ( $show_input_fields ) : ?>
			<input
				class="min"
				type="text"
				data-wp-bind--value="state.formattedMinPrice"
				data-wp-on--focus="actions.selectInputContent"
				data-wp-on--input="actions.debounceSetMinPrice"
				aria-label="<?php esc_attr_e( 'Filter products by minimum price', 'woocommerce' ); ?>"
			/>
		<?php else : ?>
			<span data-wp-text="state.formattedMinPrice"></span>
		<?php endif; ?>
	</div>
	<?php
	$left_input = ob_get_clean();

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="<?php echo esc_attr( $content_class ); ?>">
			<?php if ( $inline_input && is_string( $left_input ) ) : ?>
				<?php echo $left_input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<div
				class="wc-block-product-filter-price-slider__range"
				data-wp-bind--style="state.rangeStyle"
			>
				<div class="range-bar"></div>
				<input
					type="range"
					class="min"
					min="<?php echo esc_attr( (string) $min_range ); ?>"
					max="<?php echo esc_attr( (string) $max_range ); ?>"
					data-wp-bind--value="state.minPrice"
					data-wp-on--input="actions.setMin"
					data-wp-on--mouseup="actions.navigate"
					data-wp-on--keyup="actions.navigate"
					data-wp-on--touchend="actions.navigate"
					aria-label="<?php esc_attr_e( 'Filter products by minimum price', 'woocommerce' ); ?>"
				/>
				<input
					type="range"
					class="max"
					min="<?php echo esc_attr( (string) $min_range ); ?>"
					max="<?php echo esc_attr( (string) $max_range ); ?>"
					data-wp-bind--value="state.maxPrice"
					data-wp-on--input="actions.setMax"
					data-wp-on--mouseup="actions.navigate"
					data-wp-on--keyup="actions.navigate"
					data-wp-on--touchend="actions.navigate"
					aria-label="<?php esc_attr_e( 'Filter products by maximum price', 'woocommerce' ); ?>"
				/>
			</div>
			<?php if ( ! $inline_input && is_string( $left_input ) ) : ?>
				<?php echo $left_input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<div class="wc-block-product-filter-price-slider__right text">
				<?php if ( $show_input_fields ) : ?>
					<input
						class="max"
						type="text"
						data-wp-bind--value="state.formattedMaxPrice"
						data-wp-on--focus="actions.selectInputContent"
						data-wp-on--input="actions.debounceSetMaxPrice"
						aria-label="<?php esc_attr_e( 'Filter products by maximum price', 'woocommerce' ); ?>"
					/>
				<?php else : ?>
					<span data-wp-text="state.formattedMaxPrice"></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	$output = ob_get_clean();
	return false === $output ? '' : $output;
}

/**
 * Registers the `woocommerce/product-filter-price-slider` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_price_slider(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_price_slider',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_price_slider' );
