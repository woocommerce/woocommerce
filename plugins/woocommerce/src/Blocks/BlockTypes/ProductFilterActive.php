<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Active Block.
 */
final class ProductFilterActive extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-active';

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

		$context = array(
			'hasSelectedFilters' => ! empty( $active_filters ) ?? false,
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
				'data-wc-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
				'data-wc-context'     => wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			)
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			array_reduce(
				$block->parsed_block['innerBlocks'],
				function ( $carry, $parsed_block ) {
					$carry .= ( new \WP_Block( $parsed_block ) )->render();
					return $carry;
				},
				''
			)
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

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
