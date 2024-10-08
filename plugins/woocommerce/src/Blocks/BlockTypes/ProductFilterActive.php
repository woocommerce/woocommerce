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

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
				'data-wc-key'         => 'product-filter-active-' . md5( wp_json_encode( $attributes ) ),
			)
		);

		$list_classes = 'filter-list';

		if ( 'chips' === $attributes['displayStyle'] ) {
			$list_classes .= ' list-chips';
		}

		ob_start();
		?>

		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( ! empty( $active_filters ) ) : ?>
				<ul class="<?php echo esc_attr( $list_classes ); ?>">
					<?php foreach ( $active_filters as $filter ) : ?>
					<li>
						<span class="list-item-type"><?php echo esc_html( $filter['type'] ); ?>: </span>
						<ul>
							<?php $this->render_items( $filter['items'], $attributes['displayStyle'] ); ?>
						</ul>
					</li>
					<?php endforeach; ?>
				</ul>
				<button class="clear-all" data-wc-on--click="actions.clearAll">
					<span aria-hidden="true"><?php echo esc_html__( 'Clear All', 'woocommerce' ); ?></span>
					<span class="screen-reader-text"><?php echo esc_html__( 'Clear All Filters', 'woocommerce' ); ?></span>
				</button>
			<?php endif; ?>
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
		<li class="list-item">
			<span class="list-item-name">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<button class="list-item-remove"  <?php echo $this->get_html_attributes( $attributes ); ?>>
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
		<li class="list-item">
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
