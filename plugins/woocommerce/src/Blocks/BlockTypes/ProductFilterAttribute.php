<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\InteractivityComponents\Dropdown;
use Automattic\WooCommerce\Blocks\InteractivityComponents\CheckboxList;
use Automattic\WooCommerce\Blocks\Utils\ProductCollectionUtils;

/**
 * Product Filter: Attribute Block.
 */
final class ProductFilterAttribute extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-attribute';

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
		add_action( 'deleted_transient', array( $this, 'delete_default_attribute_id_transient' ) );
		add_action( 'wp_loaded', array( $this, 'register_block_patterns' ) );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add( 'defaultProductFilterAttribute', $this->get_default_product_attribute() );
		}
	}

	/**
	 * Delete the default attribute id transient when the attribute taxonomies are deleted.
	 *
	 * @param string $transient The transient name.
	 */
	public function delete_default_attribute_id_transient( $transient ) {
		if ( 'wc_attribute_taxonomies' === $transient ) {
			delete_transient( 'wc_block_product_filter_attribute_default_attribute' );
		}
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
			function ( $param ) {
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
			function ( $acc, $attribute_object ) {
				$acc[ $attribute_object->attribute_name ] = $attribute_object->attribute_label;
				return $acc;
			},
			array()
		);

		$active_product_attributes = array_reduce(
			array_keys( $params ),
			function ( $acc, $attribute ) {
				if ( strpos( $attribute, 'filter_' ) === 0 ) {
					$acc[] = str_replace( 'filter_', '', $attribute );
				}
				return $acc;
			},
			array()
		);

		$active_product_attributes = array_filter(
			$active_product_attributes,
			function ( $item ) use ( $product_attributes_map ) {
				return in_array( $item, array_keys( $product_attributes_map ), true );
			}
		);

		$action_namespace = $this->get_full_block_name();

		foreach ( $active_product_attributes as $product_attribute ) {
			$terms = explode( ',', get_query_var( "filter_{$product_attribute}" ) );

			// Get attribute term by slug.
			$terms = array_map(
				function ( $term ) use ( $product_attribute, $action_namespace ) {
					$term_object = get_term_by( 'slug', $term, "pa_{$product_attribute}" );
					return array(
						'title'      => $term_object->name,
						'attributes' => array(
							'data-wc-on--click' => "$action_namespace::actions.removeFilter",
							'data-wc-context'   => "$action_namespace::" . wp_json_encode(
								array(
									'value'         => $term,
									'attributeSlug' => $product_attribute,
									'queryType'     => get_query_var( "query_type_{$product_attribute}" ),
								),
								JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
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
		if ( empty( $attributes['attributeId'] ) ) {
			$default_product_attribute = $this->get_default_product_attribute();
			$attributes['attributeId'] = $default_product_attribute->attribute_id;
		}

		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() || empty( $attributes['attributeId'] ) ) {
			return '';
		}

		$product_attribute = wc_get_attribute( $attributes['attributeId'] );
		$attribute_counts  = $this->get_attribute_counts( $block, $product_attribute->slug, $attributes['queryType'] );

		if ( empty( $attribute_counts ) ) {
			return sprintf(
				'<div %s></div>',
				get_block_wrapper_attributes(
					array(
						'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
						'data-has-filter'     => 'no',
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
			function ( $term ) use ( $attribute_counts, $selected_terms ) {
				$term             = (array) $term;
				$term['count']    = $attribute_counts[ $term['term_id'] ];
				$term['selected'] = in_array( $term['slug'], $selected_terms, true );
				return $term;
			},
			$attribute_terms
		);

		$filtered_options = array_filter(
			$attribute_options,
			function ( $option ) {
				return $option['count'] > 0;
			}
		);

		$filter_content = 'dropdown' === $attributes['displayStyle'] ?
			$this->render_attribute_dropdown( $filtered_options, $attributes ) :
			$this->render_attribute_checkbox_list( $filtered_options, $attributes );

		$context = array(
			'attributeSlug' => str_replace( 'pa_', '', $product_attribute->slug ),
			'queryType'     => $attributes['queryType'],
			'selectType'    => 'multiple',
		);

		return sprintf(
			'<div %1$s>%2$s%3$s</div>',
			get_block_wrapper_attributes(
				array(
					'data-wc-context'     => wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
					'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
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
	private function render_attribute_dropdown( $options, $attributes ) {
		if ( empty( $options ) ) {
			return '';
		}

		$list_items     = array();
		$selected_items = array();

		$product_attribute = wc_get_attribute( $attributes['attributeId'] );

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
				'select_type'    => 'multiple',
				// translators: %s is a product attribute name.
				'placeholder'    => sprintf( __( 'Select %s', 'woocommerce' ), $product_attribute->name ),
			)
		);
	}

	/**
	 * Render the attribute filter checkbox list.
	 *
	 * @param mixed $options Attribute filter options to render in the checkbox list.
	 * @param mixed $attributes Block attributes.
	 * @return string
	 */
	private function render_attribute_checkbox_list( $options, $attributes ) {
		if ( empty( $options ) ) {
			return '';
		}

		$show_counts = $attributes['showCounts'] ?? false;

		$list_options = array_map(
			function ( $option ) use ( $show_counts ) {
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
	 * Retrieve the attribute count for current block.
	 *
	 * @param WP_Block $block      Block instance.
	 * @param string   $slug       Attribute slug.
	 * @param string   $query_type Query type, accept 'and' or 'or'.
	 */
	private function get_attribute_counts( $block, $slug, $query_type ) {
		$filters    = Package::container()->get( QueryFilters::class );
		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		if ( 'and' !== strtolower( $query_type ) ) {
			unset( $query_vars[ 'filter_' . str_replace( 'pa_', '', $slug ) ] );
		}

		unset(
			$query_vars['taxonomy'],
			$query_vars['term']
		);

		if ( ! empty( $query_vars['tax_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'taxonomy', $slug );
		}

		$counts           = $filters->get_attribute_counts( $query_vars, $slug );
		$attribute_counts = array();

		foreach ( $counts as $key => $value ) {
			$attribute_counts[] = array(
				'term'  => $key,
				'count' => $value,
			);
		}

		$attribute_counts = array_reduce(
			$attribute_counts,
			function ( $acc, $count ) {
				$acc[ $count['term'] ] = $count['count'];
				return $acc;
			},
			array()
		);

		return $attribute_counts;
	}

	/**
	 * Get the attribute if with most term but closest to 30 terms.
	 *
	 * @return object
	 */
	private function get_default_product_attribute() {
		// Cache this variable in memory to prevent repeated database queries to check
		// for transient in the same request.
		static $cached = null;

		if ( $cached ) {
			return $cached;
		}

		$cached = get_transient( 'wc_block_product_filter_attribute_default_attribute' );

		if ( $cached ) {
			return $cached;
		}

		$attributes = wc_get_attribute_taxonomies();

		$attributes_count = array_map(
			function ( $attribute ) {
				return intval(
					wp_count_terms(
						array(
							'taxonomy'   => 'pa_' . $attribute->attribute_name,
							'hide_empty' => false,
						)
					)
				);
			},
			$attributes
		);

		asort( $attributes_count );

		$search       = 30;
		$closest      = null;
		$attribute_id = null;

		foreach ( $attributes_count as $id => $count ) {
			if ( null === $closest || abs( $search - $closest ) > abs( $count - $search ) ) {
				$closest      = $count;
				$attribute_id = $id;
			}

			if ( $closest && $count >= $search ) {
				break;
			}
		}

		$default_attribute = (object) array(
			'attribute_id'      => '0',
			'attribute_name'    => 'attribute',
			'attribute_label'   => __( 'Attribute', 'woocommerce' ),
			'attribute_type'    => 'select',
			'attribute_orderby' => 'menu_order',
			'attribute_public'  => 0,
		);

		if ( $attribute_id ) {
			$default_attribute = $attributes[ $attribute_id ];
		}

		set_transient( 'wc_block_product_filter_attribute_default_attribute', $default_attribute );

		return $default_attribute;
	}

	/**
	 * Register pattern for default product attribute.
	 */
	public function register_block_patterns() {
		$default_attribute = $this->get_default_product_attribute();
		register_block_pattern(
			'woocommerce/default-attribute-filter',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => strtr(
					'
<!-- wp:woocommerce/product-filter {"filterType":"attribute-filter","attributeId":{{attribute_id}}} -->
<!-- wp:group {"metadata":{"name":"Header"},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group">
	<!-- wp:heading {"level":3} -->
	<h3 class="wp-block-heading">{{attribute_label}}</h3>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-filter-clear-button {"lock":{"remove":true,"move":false}} -->
	<!-- wp:buttons {"layout":{"type":"flex"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"wc-block-product-filter-clear-button is-style-outline","style":{"border":{"width":"0px","style":"none"},"typography":{"textDecoration":"underline"},"outline":"none","fontSize":"medium"}} -->
		<div
			class="wp-block-button wc-block-product-filter-clear-button is-style-outline"
			style="text-decoration: underline"
		>
			<a class="wp-block-button__link wp-element-button" style="border-style: none; border-width: 0px">Clear</a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
	<!-- /wp:woocommerce/product-filter-clear-button -->
</div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-filter-attribute {"attributeId":{{attribute_id}},"lock":{"remove":true}} /-->
<!-- /wp:woocommerce/product-filter -->
					',
					array(
						'{{attribute_id}}'    => intval( $default_attribute->attribute_id ),
						'{{attribute_label}}' => esc_html( $default_attribute->attribute_label ),
					)
				),
			)
		);
	}
}
