<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Active Chips Block.
 */
final class ProductFilterActiveChips extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-active-chips';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$query_id = $block->context['queryId'] ?? 0;

		/**
		 * Filters the active filter data provided by filter blocks.
		 *
		 * $data = array(
		 *     <id> => array(
		 *         'type' => string,
		 *         'items' => array(
		 *             array(
		 *                 'title' => string,
		 *                 'attributes' => array(
		 *                     <key> => string
		 *                 )
		 *             )
		 *         )
		 *     ),
		 * );
		 *
		 * @since 11.7.0
		 *
		 * @param array $data   The active filters data
		 * @param array $params The query param parsed from the URL.
		 * @return array Active filters data.
		 */
		$active_filters = apply_filters( 'collection_active_filters_data', array(), $this->get_filter_query_params( $query_id ) );

		$style = '';

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-active-chips' ) ) ) {
			$classes = $tags->get_attribute( 'class' );
			$style   = $tags->get_attribute( 'style' );
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
				'data-wc-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
				'class'               => esc_attr( $classes ),
				'style'               => esc_attr( $style ),
			)
		);

		ob_start();
		?>

		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( ! empty( $active_filters ) ) : ?>
				<ul class="wc-block-product-filter-active-chips__items">
					<?php foreach ( $active_filters as $filter ) : ?>
						<?php foreach ( $filter['items'] as $item ) : ?>
							<?php $this->render_chip_item( $filter['type'], $item ); ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render the chip item of an active filter.
	 *
	 * @param string $type Filter type.
	 * @param array  $item Item data.
	 * @return string Item HTML.
	 */
	private function render_chip_item( $type, $item ) {
		list ( 'title' => $title, 'attributes' => $attributes ) = wp_parse_args(
			$item,
			array(
				'title'      => '',
				'attributes' => array(),
			)
		);

		if ( ! $title || empty( $attributes ) ) {
			return;
		}

		$remove_label = sprintf( 'Remove %s filter', wp_strip_all_tags( $title ) );
		?>
		<li class="wc-block-product-filter-active-chips__item">
			<span class="wc-block-product-filter-active-chips__label">
				<?php printf( '%s: %s', esc_html( $type ), wp_kses_post( $title ) ); ?>
			</span>
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button class="wc-block-product-filter-active-chips__remove" aria-label="<?php echo esc_attr( $remove_label ); ?>" <?php echo $this->get_html_attributes( $attributes ); ?>>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="25" height="25" class="wc-block-product-filter-active-chips__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
				<span class="screen-reader-text"><?php echo esc_attr( $remove_label ); ?></span>
			</button>
		</li>
		<?php
	}

	/**
	 * Build HTML attributes string from assoc array.
	 *
	 * @param array $attributes Attributes data as an assoc array.
	 * @return string Escaped HTML attributes string.
	 */
	private function get_html_attributes( $attributes ) {
		return array_reduce(
			array_keys( $attributes ),
			function ( $acc, $key ) use ( $attributes ) {
				$acc .= sprintf( ' %1$s="%2$s"', esc_attr( $key ), esc_attr( $attributes[ $key ] ) );
				return $acc;
			},
			''
		);
	}

	/**
	 * Parse the filter parameters from the URL.
	 * For now we only get the global query params from the URL. In the future,
	 * we should get the query params based on $query_id.
	 *
	 * @param int $query_id Query ID.
	 * @return array Parsed filter params.
	 */
	private function get_filter_query_params( $query_id ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		$parsed_url = wp_parse_url( esc_url_raw( $request_uri ) );

		if ( empty( $parsed_url['query'] ) ) {
			return array();
		}

		parse_str( $parsed_url['query'], $url_query_params );

		/**
		 * Filters the active filter data provided by filter blocks.
		 *
		 * @since 11.7.0
		 *
		 * @param array $filter_param_keys The active filters data
		 * @param array $url_param_keys    The query param parsed from the URL.
		 *
		 * @return array Active filters params.
		 */
		$filter_param_keys = array_unique( apply_filters( 'collection_filter_query_param_keys', array(), array_keys( $url_query_params ) ) );

		return array_filter(
			$url_query_params,
			function ( $key ) use ( $filter_param_keys ) {
				return in_array( $key, $filter_param_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}
