<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\InteractivityComponents\Dropdown;
use Automattic\WooCommerce\Blocks\InteractivityComponents\CheckboxList;
use Automattic\WooCommerce\Blocks\Utils\ProductCollectionUtils;

/**
 * Product Filter: Taxonomy Block.
 */
final class ProductFilterTaxonomy extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-taxonomy';

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
		$param_keys = array_filter(
			$url_param_keys,
			function( $param ) {
				return strpos( $param, 'filter_' ) === 0 || strpos( $param, 'query_type_' ) === 0;
			}
		);

		return array_merge(
			$filter_param_keys,
			$param_keys
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
		$product_taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ), 'public' => true ), 'objects' );
		$product_taxonomies = array_filter(
			$product_taxonomies,
			function( $item ) {
				// We can use better check to filter product attribute out. This is just for demo.
				return strpos( $item->name, 'pa_' ) !== 0;
			}
		);

		$taxonomies_map = array_reduce(
			$product_taxonomies,
			function( $acc, $taxonomy_object ) {
				$acc[ $taxonomy_object->name ] = $taxonomy_object->label;
				return $acc;
			},
			array()
		);

		$active_taxonomies = array_reduce(
			array_keys( $params ),
			function( $acc, $taxonomy_name ) {
				if ( strpos( $taxonomy_name, 'filter_' ) === 0 ) {
					$acc[] = str_replace( 'filter_', '', $taxonomy_name );
				}
				return $acc;
			},
			array()
		);


		$active_taxonomies = array_filter(
			$active_taxonomies,
			function( $item ) use ( $taxonomies_map ) {
				return in_array( $item, array_keys( $taxonomies_map ), true );
			}
		);

		$action_namespace = $this->get_full_block_name();

		foreach ( $active_taxonomies as $taxonomy_name ) {
			$terms = empty( $params[ "filter_{$taxonomy_name}" ] ) ? array() : explode( ',', $params[ "filter_{$taxonomy_name}" ] );

			// Get term by slug.
			$terms = array_map(
				function( $term ) use ( $taxonomy_name, $action_namespace ) {
					$term_object = get_term_by( 'slug', $term, $taxonomy_name );
					return array(
						'title'      => $term_object->name,
						'attributes' => array(
							'data-wc-on--click' => "$action_namespace::actions.removeFilter",
							'data-wc-context'   => "$action_namespace::" . wp_json_encode(
								array(
									'value'         => $term,
									'taxonomyName'  => $taxonomy_name,
									'queryType'     => get_query_var( "query_type_{$taxonomy_name}" ),
								)
							),
						),
					);
				},
				$terms
			);

			$data[ $taxonomy_name ] = array(
				'type'  => $taxonomies_map[ $taxonomy_name ],
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
		if ( empty( $attributes['taxonomyName'] ) ) {
			$attributes['taxonomyName'] = 'product_cat';
		}

		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}

		$taxonomy = get_taxonomy( $attributes['taxonomyName'] );
		$counts   = $this->get_taxonomy_counts( $block, $taxonomy->name, $attributes['queryType'] );

		if ( empty( $counts ) ) {
			return sprintf(
				'<div %s></div>',
				get_block_wrapper_attributes(
					array(
						'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ) ),
						'data-has-filter'     => 'no',
					)
				),
			);
		}

		$all_terms = get_terms(
			array(
				'taxonomy' => $taxonomy->name,
				'include'  => array_keys( $counts ),
			)
		);

		$selected_terms = array_filter(
			explode(
				',',
				get_query_var( 'filter_' . str_replace( 'pa_', '', $taxonomy->name ) )
			)
		);

		$options = array_map(
			function( $term ) use ( $counts, $selected_terms ) {
				$term             = (array) $term;
				$term['count']    = $counts[ $term['term_id'] ];
				$term['selected'] = in_array( $term['slug'], $selected_terms, true );
				return $term;
			},
			$all_terms
		);

		$filtered_options = array_filter(
			$options,
			function( $option ) {
				return $option['count'] > 0;
			}
		);

		$filter_content = 'dropdown' === $attributes['displayStyle'] ?
			$this->render_dropdown( $filtered_options, $attributes ) :
			$this->render_checkbox_list( $filtered_options, $attributes );

		$context = array(
			'taxonomyName' => $taxonomy->name,
			'queryType'    => $attributes['queryType'],
			'selectType'   => $attributes['selectType'],
		);

		return sprintf(
			'<div %1$s>%2$s%3$s</div>',
			get_block_wrapper_attributes(
				array(
					'data-wc-context'     => wp_json_encode( $context ),
					'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ) ),
					'data-has-filter'     => 'yes',
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
	private function render_dropdown( $options, $attributes ) {
		if ( empty( $options ) ) {
			return '';
		}

		$list_items     = array();
		$selected_items = array();

		$taxonomy = get_taxonomy( $attributes[ 'taxonomyName' ] );

		foreach ( $options as $option ) {
			$item = array(
				'label' => $attributes['showCounts'] ? sprintf( '%1$s (%2$d)', $option['name'], $option['count'] ) : $option['name'],
				'value' => $option['slug'],
			);

			$list_items[] = $item;

			if ( $option['selected'] ) {
				$selected_items[] = $item;
			}
		}

		return Dropdown::render(
			array(
				'items'          => $list_items,
				'action'         => "{$this->get_full_block_name()}::actions.navigate",
				'selected_items' => $selected_items,
				'select_type'    => $attributes['selectType'] ?? 'multiple',
				// translators: %s is a taxonomy label.
				'placeholder'    => sprintf( __( 'Select %s', 'woocommerce' ), $taxonomy->label ),
			)
		);
	}

	/**
	 * Render the filter checkbox list.
	 *
	 * @param mixed $options    Filter options to render in the checkbox list.
	 * @param mixed $attributes Block attributes.
	 * @return string
	 */
	private function render_checkbox_list( $options, $attributes ) {
		if ( empty( $options ) ) {
			return '';
		}

		$show_counts = $attributes['showCounts'] ?? false;

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
				'on_change' => "{$this->get_full_block_name()}::actions.updateProducts",
			)
		);
	}

	/**
	 * Retrieve the count for current block.
	 *
	 * @param WP_Block $block      Block instance.
	 * @param string   $taxonomy_name       Taxonomy name.
	 * @param string   $query_type Query type, accept 'and' or 'or'.
	 */
	private function get_taxonomy_counts( $block, $taxonomy_name, $query_type ) {
		$filters    = Package::container()->get( QueryFilters::class );
		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		if ( 'and' !== strtolower( $query_type ) ) {
			unset( $query_vars[ 'filter_' . $taxonomy_name ] );
			unset(
				$query_vars['taxonomy'],
				$query_vars['term']
			);

			if ( ! empty( $query_vars['tax_query'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'taxonomy', $taxonomy_name );
			}
		}

		$terms_counts = $filters->get_taxonomy_counts( $query_vars, $taxonomy_name );
		$counts = array();

		foreach ( $terms_counts as $key => $value ) {
			$counts[] = array(
				'term'  => $key,
				'count' => $value,
			);
		}

		$counts = array_reduce(
			$counts,
			function( $acc, $count ) {
				$acc[ $count['term'] ] = $count['count'];
				return $acc;
			},
			[]
		);

		return $counts;
	}
}
