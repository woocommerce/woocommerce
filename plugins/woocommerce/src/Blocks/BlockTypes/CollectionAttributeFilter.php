<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\InteractivityComponents\Dropdown;
use Automattic\WooCommerce\Blocks\InteractivityComponents\CheckboxList;

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
		$attribute_param_keys = array_filter(
			$url_param_keys,
			function( $param ) {
				return strpos( $param, 'filter_' ) === 0 || strpos( $param, 'query_type_' ) === 0;
			}
		);

		return array_merge(
			$filter_param_keys,
			$attribute_param_keys
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
		$product_attributes_map = array_reduce(
			wc_get_attribute_taxonomies(),
			function( $acc, $attribute_object ) {
				$acc[ $attribute_object->attribute_name ] = $attribute_object->attribute_label;
				return $acc;
			},
			array()
		);

		$active_product_attributes = array_reduce(
			array_keys( $params ),
			function( $acc, $attribute ) {
				if ( strpos( $attribute, 'filter_' ) === 0 ) {
					$acc[] = str_replace( 'filter_', '', $attribute );
				}
				return $acc;
			},
			array()
		);

		$active_product_attributes = array_filter(
			$active_product_attributes,
			function( $item ) use ( $product_attributes_map ) {
				return in_array( $item, array_keys( $product_attributes_map ), true );
			}
		);

		foreach ( $active_product_attributes as $product_attribute ) {
			$terms = explode( ',', get_query_var( "filter_{$product_attribute}" ) );

			// Get attribute term by slug.
			$terms = array_map(
				function( $term ) use ( $product_attribute ) {
					$term_object = get_term_by( 'slug', $term, "pa_{$product_attribute}" );
					return array(
						'title'      => $term_object->name,
						'attributes' => array(
							'data-wc-on--click' => 'woocommerce/collection-attribute-filter::actions.removeFilter',
							'data-wc-context'   => 'woocommerce/collection-attribute-filter::' . wp_json_encode(
								array(
									'value'         => $term,
									'attributeSlug' => $product_attribute,
									'queryType'     => get_query_var( "query_type_{$product_attribute}" ),
								)
							),
						),
					);
				},
				$terms
			);

			$data[ $product_attribute ] = array(
				'type'  => $product_attributes_map[ $product_attribute ],
				'items' => $terms,
			);
		}

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
		if ( is_admin() || wp_doing_ajax() || empty( $attributes['attributeId'] ) ) {
			return '';
		}

		$product_attribute = wc_get_attribute( $attributes['attributeId'] );
		$attribute_counts  = array_reduce(
			$block->context['collectionData']['attribute_counts'] ?? [],
			function( $acc, $count ) {
				$acc[ $count['term'] ] = $count['count'];
				return $acc;
			},
			[]
		);

		if ( empty( $attribute_counts ) ) {
			return sprintf(
				'<div %s></div>',
				get_block_wrapper_attributes(
					array(
						'data-wc-interactive' => wp_json_encode( array( 'namespace' => 'woocommerce/collection-attribute-filter' ) ),
					)
				),
			);

		}

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

		$filter_content = 'dropdown' === $attributes['displayStyle'] ? $this->render_attribute_dropdown( $attribute_options, $attributes ) : $this->render_attribute_checkbox_list( $attribute_options, $attributes );

		$context = array(
			'attributeSlug' => str_replace( 'pa_', '', $product_attribute->slug ),
			'queryType'     => $attributes['queryType'],
			'selectType'    => $attributes['selectType'],
		);

		return sprintf(
			'<div %1$s>%2$s%3$s</div>',
			get_block_wrapper_attributes(
				array(
					'data-wc-context'     => wp_json_encode( $context ),
					'data-wc-interactive' => wp_json_encode( array( 'namespace' => 'woocommerce/collection-attribute-filter' ) ),
				)
			),
			$content,
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
		if ( empty( $options ) ) {
			return '';
		}

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
				'items'          => $list_items,
				'action'         => 'woocommerce/collection-attribute-filter::actions.navigate',
				'selected_items' => array( $selected_item ),
			)
		);
	}

	/**
	 * Render the attribute filter checkbox list.
	 *
	 * @param mixed $options Attribute filter options to render in the checkbox list.
	 * @param mixed $show_counts Whether to show counts for each option.
	 * @return string
	 */
	private function render_attribute_checkbox_list( $options, $show_counts ) {
		if ( empty( $options ) ) {
			return '';
		}

		$list_options = array_map(
			function( $option ) use ( $show_counts ) {
				return array(
					'id'      => $option['slug'] . '-' . $option['term_id'],
					'checked' => $option['selected'],
					'label'   => $show_counts ? sprintf( '%1$s (%2$d)', $option['name'], $option['count'] ) : $option['name'],
					'value'   => $option['slug'],
				);
			},
			$options
		);

		return CheckboxList::render(
			array(
				'items'     => $list_options,
				'on_change' => 'woocommerce/collection-attribute-filter::actions.updateProducts',
			)
		);
	}
}
