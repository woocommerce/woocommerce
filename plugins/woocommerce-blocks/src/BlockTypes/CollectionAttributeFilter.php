<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\InteractivityComponents\Dropdown;

/**
 * CollectionAttributeFilter class.
 */
final class CollectionAttributeFilter extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'collection-attribute-filter';

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
			empty( $block->context['collectionData']['attribute_counts'] ) ||
			empty( $attributes['attributeId'] )
		) {
			return $content;
		}

		$product_attribute = wc_get_attribute( $attributes['attributeId'] );

		$attribute_counts = array_reduce(
			$block->context['collectionData']['attribute_counts'],
			function( $acc, $count ) {
				$acc[ $count['term'] ] = $count['count'];
				return $acc;
			},
			[]
		);

		$attribute_terms = get_terms(
			array(
				'taxonomy' => $product_attribute->slug,
				'include'  => array_keys( $attribute_counts ),
			)
		);

		$selected_terms = array_filter(
			explode(
				',',
				get_query_var( 'filter_' . str_replace( 'pa_', '', $product_attribute->slug ) )
			)
		);

		$attribute_options = array_map(
			function( $term ) use ( $attribute_counts, $selected_terms ) {
				$term             = (array) $term;
				$term['count']    = $attribute_counts[ $term['term_id'] ];
				$term['selected'] = in_array( $term['slug'], $selected_terms, true );
				return $term;
			},
			$attribute_terms
		);

		$filter_content = 'dropdown' === $attributes['displayStyle'] ? $this->render_attribute_dropdown( $attribute_options, $attributes ) : $this->render_attribute_list( $attribute_options, $attributes );

		$context = array(
			'attributeSlug' => str_replace( 'pa_', '', $product_attribute->slug ),
			'queryType'     => $attributes['queryType'],
			'selectType'    => $attributes['selectType'],
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(
				array(
					'data-wc-context'     => wp_json_encode( $context ),
					'data-wc-interactive' => wp_json_encode( array( 'namespace' => 'woocommerce/collection-attribute-filter' ) ),
				)
			),
			$filter_content
		);
	}

	/**
	 * Render the dropdown.
	 *
	 * @param array $options    Data to render the dropdown.
	 * @param bool  $attributes Block attributes.
	 */
	private function render_attribute_dropdown( $options, $attributes ) {
		$list_items    = array();
		$selected_item = array();

		foreach ( $options as $option ) {
			$item = array(
				'label' => $attributes['showCounts'] ? sprintf( '%1$s (%2$d)', $option['name'], $option['count'] ) : $option['name'],
				'value' => $option['slug'],
			);

			$list_items[] = $item;

			if ( $option['selected'] ) {
				$selected_item = $item;
			}
		}

		return Dropdown::render(
			array(
				'items'         => $list_items,
				'action'        => 'woocommerce/collection-attribute-filter::actions.navigate',
				'selected_item' => $selected_item,
			)
		);
	}

	/**
	 * Render the list.
	 *
	 * @param array $options    Data to render the list.
	 * @param bool  $attributes Block attributes.
	 */
	private function render_attribute_list( $options, $attributes ) {
		$output = '<ul class="wc-block-checkbox-list wc-block-components-checkbox-list wc-block-stock-filter-list">';
		foreach ( $options as $option ) {
			$output .= $this->render_list_item_template( $option, $attributes['showCounts'] );
		}
		$output .= '</ul>';
		return $output;
	}

	/**
	 * Render the list item.
	 *
	 * @param array $option      Data to render the list item.
	 * @param bool  $show_counts Whether to display the count.
	 */
	private function render_list_item_template( $option, $show_counts ) {
		$count_html = $show_counts ?
			sprintf(
				'<span class="wc-filter-element-label-list-count">
				<span aria-hidden="true">%1$s</span>
				<span class="screen-reader-text">%2$s</span>
				</span>',
				$option['count'],
				// translators: %d is the number of products.
				sprintf( _n( '%d product', '%d products', $option['count'], 'woo-gutenberg-products-block' ), $option['count'] )
			) :
			'';

		$template = '<li>
			<div class="wc-block-components-checkbox wc-block-checkbox-list__checkbox">
				<label for="%1$s">
					<input
						id="%1$s"
						class="wc-block-components-checkbox__input"
						type="checkbox"
						aria-invalid="false"
						data-wc-on--change="actions.updateProducts"
						data-wc-context=\'{ "attributeTermSlug": "%5$s" }\'
						value="%5$s"
						%4$s
					/>
					<svg class="wc-block-components-checkbox__mark" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 20"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"></path></svg>
					<span class="wc-block-components-checkbox__label">%2$s %3$s</span>
				</label>
			</div>
		</li>';

		return sprintf(
			$template,
			esc_attr( $option['slug'] ) . '-' . $option['term_id'],
			esc_html( $option['name'] ),
			$count_html,
			checked( $option['selected'], true, false ),
			esc_attr( $option['slug'] )
		);
	}
}
