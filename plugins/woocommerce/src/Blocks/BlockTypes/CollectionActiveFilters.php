<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CollectionAttributeFilter class.
 */
final class CollectionActiveFilters extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'collection-active-filters';

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

		if ( empty( $active_filters ) ) {
			return $content;
		}

		$context = array(
			'queryId' => $query_id,
			'params'  => array_keys( $this->get_filter_query_params( $query_id ) ),
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => 'wc-block-active-filters',
				'data-wc-interactive' => wp_json_encode( array( 'namespace' => 'woocommerce/collection-active-filters' ) ),
				'data-wc-context'     => wp_json_encode( $context ),
			)
		);

		ob_start();
		?>

		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div <?php echo $wrapper_attributes; ?>>
			<ul class="wc-block-active-filters__list %3$s">
				<?php foreach ( $active_filters as $filter ) : ?>
				<li>
					<span class="wc-block-active-filters__list-item-type"><?php echo esc_html( $filter['type'] ); ?>: </span>
					<ul>
						<?php $this->render_items( $filter['items'], $attributes['displayStyle'] ); ?>
					</ul>
				</li>
				<?php endforeach; ?>
			</ul>
			<button class="wc-block-active-filters__clear-all" data-wc-on--click="actions.clearAll">
				<span aria-hidden="true"><?php echo esc_html__( 'Clear All', 'woocommerce' ); ?></span>
				<span class="screen-reader-text"><?php echo esc_html__( 'Clear All Filters', 'woocommerce' ); ?></span>
			</button>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render the list items.
	 *
	 * @param array  $items Items data.
	 * @param string $style Display style: list | chips.
	 */
	private function render_items( $items, $style ) {
		foreach ( $items as $item ) {
			if ( 'chips' === $style ) {
				$this->render_chip_item( $item );
			} else {
				$this->render_list_item( $item );
			}
		}
	}

	/**
	 * Render the list item of an active filter.
	 *
	 * @param array $args Item data.
	 * @return string Item HTML.
	 */
	private function render_list_item( $args ) {
		list ( 'title' => $title, 'attributes' => $attributes ) = wp_parse_args(
			$args,
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
		<li class="wc-block-active-filters__list-item">
			<span class="wc-block-active-filters__list-item-name">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<button class="wc-block-active-filters__list-item-remove"  <?php echo $this->get_html_attributes( $attributes ); ?>>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" class="wc-block-components-chip__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
					<span class="screen-reader-text"><?php echo esc_html( $remove_label ); ?></span>
				</button>
				<?php echo wp_kses_post( $title ); ?>
			</span>
		</li>
		<?php
	}

	/**
	 * Render the chip item of an active filter.
	 *
	 * @param array $args Item data.
	 * @return string Item HTML.
	 */
	private function render_chip_item( $args ) {
		list ( 'title' => $title, 'attributes' => $attributes ) = wp_parse_args(
			$args,
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
		<li class="wc-block-active-filters__list-item">
			<span class="is-removable wc-block-components-chip wc-block-components-chip--radius-large">
				<span aria-hidden="false" class="wc-block-components-chip__text"><?php echo wp_kses_post( $title ); ?></span>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<button class="wc-block-components-chip__remove" aria-label="<?php echo esc_attr( $remove_label ); ?>" <?php echo $this->get_html_attributes( $attributes ); ?>>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" role="img" class="wc-block-components-chip__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
				</button>
			</span>
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
			function( $acc, $key ) use ( $attributes ) {
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
			function( $key ) use ( $filter_param_keys ) {
				return in_array( $key, $filter_param_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}
