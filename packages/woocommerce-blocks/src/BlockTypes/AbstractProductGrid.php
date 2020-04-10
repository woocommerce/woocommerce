<?php
/**
 * Class for product grid functionality
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Utils\BlocksWpQuery;

/**
 * AbstractProductGrid class.
 */
abstract class AbstractProductGrid extends AbstractDynamicBlock {

	/**
	 * Attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * InnerBlocks content.
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Query args.
	 *
	 * @var array
	 */
	protected $query_args = array();

	/**
	 * Get a set of attributes shared across most of the grid blocks.
	 *
	 * @return array List of block attributes with type and defaults.
	 */
	protected function get_attributes() {
		return array(
			'className'         => $this->get_schema_string(),
			'columns'           => $this->get_schema_number( wc_get_theme_support( 'product_blocks::default_columns', 3 ) ),
			'rows'              => $this->get_schema_number( wc_get_theme_support( 'product_blocks::default_rows', 1 ) ),
			'categories'        => $this->get_schema_list_ids(),
			'catOperator'       => array(
				'type'    => 'string',
				'default' => 'any',
			),
			'contentVisibility' => $this->get_schema_content_visibility(),
			'align'             => $this->get_schema_align(),
			'alignButtons'      => $this->get_schema_boolean( false ),
			'isPreview'         => $this->get_schema_boolean( false ),
		);
	}

	/**
	 * Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		$this->attributes = $this->parse_attributes( $attributes );
		$this->content    = $content;
		$this->query_args = $this->parse_query_args();
		$products         = $this->get_products();

		if ( ! $products ) {
			return '';
		}

		$classes = $this->get_container_classes();
		$output  = implode( '', array_map( array( $this, 'render_product' ), $products ) );

		return sprintf( '<div class="%s"><ul class="wc-block-grid__products">%s</ul></div>', esc_attr( $classes ), $output );
	}

	/**
	 * Get the schema for the contentVisibility attribute
	 *
	 * @return array List of block attributes with type and defaults.
	 */
	protected function get_schema_content_visibility() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'title'  => $this->get_schema_boolean( true ),
				'price'  => $this->get_schema_boolean( true ),
				'rating' => $this->get_schema_boolean( true ),
				'button' => $this->get_schema_boolean( true ),
			),
		);
	}

	/**
	 * Get the schema for the orderby attribute.
	 *
	 * @return array Property definition of `orderby` attribute.
	 */
	protected function get_schema_orderby() {
		return array(
			'type'    => 'string',
			'enum'    => array( 'date', 'popularity', 'price_asc', 'price_desc', 'rating', 'title', 'menu_order' ),
			'default' => 'date',
		);
	}

	/**
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	protected function parse_attributes( $attributes ) {
		// These should match what's set in JS `registerBlockType`.
		$defaults = array(
			'columns'           => wc_get_theme_support( 'product_blocks::default_columns', 3 ),
			'rows'              => wc_get_theme_support( 'product_blocks::default_rows', 1 ),
			'alignButtons'      => false,
			'categories'        => array(),
			'catOperator'       => 'any',
			'contentVisibility' => array(
				'title'  => true,
				'price'  => true,
				'rating' => true,
				'button' => true,
			),
		);

		return wp_parse_args( $attributes, $defaults );
	}

	/**
	 * Parse query args.
	 *
	 * @return array
	 */
	protected function parse_query_args() {
		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
			'orderby'             => '',
			'order'               => '',
			'meta_query'          => WC()->query->get_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query'           => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page'      => $this->get_products_limit(),
		);

		$this->set_block_query_args( $query_args );
		$this->set_ordering_query_args( $query_args );
		$this->set_categories_query_args( $query_args );
		$this->set_visibility_query_args( $query_args );

		return $query_args;
	}

	/**
	 * Parse query args.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_ordering_query_args( &$query_args ) {
		if ( isset( $this->attributes['orderby'] ) ) {
			if ( 'price_desc' === $this->attributes['orderby'] ) {
				$query_args['orderby'] = 'price';
				$query_args['order']   = 'DESC';
			} elseif ( 'price_asc' === $this->attributes['orderby'] ) {
				$query_args['orderby'] = 'price';
				$query_args['order']   = 'ASC';
			} elseif ( 'date' === $this->attributes['orderby'] ) {
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
			} else {
				$query_args['orderby'] = $this->attributes['orderby'];
			}
		}

		$query_args = array_merge(
			$query_args,
			WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] )
		);
	}

	/**
	 * Set args specific to this block
	 *
	 * @param array $query_args Query args.
	 */
	abstract protected function set_block_query_args( &$query_args );

	/**
	 * Set categories query args.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_categories_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['categories'] ) ) {
			$categories = array_map( 'absint', $this->attributes['categories'] );

			$query_args['tax_query'][] = array(
				'taxonomy'         => 'product_cat',
				'terms'            => $categories,
				'field'            => 'term_id',
				'operator'         => 'all' === $this->attributes['catOperator'] ? 'AND' : 'IN',

				/*
				 * When cat_operator is AND, the children categories should be excluded,
				 * as only products belonging to all the children categories would be selected.
				 */
				'include_children' => 'all' === $this->attributes['catOperator'] ? false : true,
			);
		}
	}

	/**
	 * Set visibility query args.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_query_args( &$query_args ) {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( $product_visibility_terms['exclude-from-catalog'] );

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$product_visibility_not_in[] = $product_visibility_terms['outofstock'];
		}

		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => $product_visibility_not_in,
			'operator' => 'NOT IN',
		);
	}

	/**
	 * Works out the item limit based on rows and columns, or returns default.
	 *
	 * @return int
	 */
	protected function get_products_limit() {
		if ( isset( $this->attributes['rows'], $this->attributes['columns'] ) && ! empty( $this->attributes['rows'] ) ) {
			$this->attributes['limit'] = intval( $this->attributes['columns'] ) * intval( $this->attributes['rows'] );
		}
		return intval( $this->attributes['limit'] );
	}

	/**
	 * Run the query and return an array of product IDs
	 *
	 * @return array List of product IDs
	 */
	protected function get_products() {
		$is_cacheable      = (bool) apply_filters( 'woocommerce_blocks_product_grid_is_cacheable', true, $this->query_args );
		$transient_version = \WC_Cache_Helper::get_transient_version( 'product_query' );

		$query   = new BlocksWpQuery( $this->query_args );
		$results = wp_parse_id_list( $is_cacheable ? $query->get_cached_posts( $transient_version ) : $query->get_posts() );

		// Remove ordering query arguments which may have been added by get_catalog_ordering_args.
		WC()->query->remove_ordering_args();

		// Prime caches to reduce future queries.
		if ( is_callable( '_prime_post_caches' ) ) {
			_prime_post_caches( $results );
		}

		return $results;
	}

	/**
	 * Get the list of classes to apply to this block.
	 *
	 * @return string space-separated list of classes.
	 */
	protected function get_container_classes() {
		$classes = array(
			'wc-block-grid',
			"wp-block-{$this->block_name}",
			"wc-block-{$this->block_name}",
			"has-{$this->attributes['columns']}-columns",
		);

		if ( $this->attributes['rows'] > 1 ) {
			$classes[] = 'has-multiple-rows';
		}

		if ( isset( $this->attributes['align'] ) ) {
			$classes[] = "align{$this->attributes['align']}";
		}

		if ( ! empty( $this->attributes['alignButtons'] ) ) {
			$classes[] = 'has-aligned-buttons';
		}

		if ( ! empty( $this->attributes['className'] ) ) {
			$classes[] = $this->attributes['className'];
		}

		return implode( ' ', $classes );
	}

	/**
	 * Render a single products.
	 *
	 * @param int $id Product ID.
	 * @return string Rendered product output.
	 */
	public function render_product( $id ) {
		$product = wc_get_product( $id );

		if ( ! $product ) {
			return '';
		}

		$data = (object) array(
			'permalink' => esc_url( $product->get_permalink() ),
			'image'     => $this->get_image_html( $product ),
			'title'     => $this->get_title_html( $product ),
			'rating'    => $this->get_rating_html( $product ),
			'price'     => $this->get_price_html( $product ),
			'badge'     => $this->get_sale_badge_html( $product ),
			'button'    => $this->get_button_html( $product ),
		);

		return apply_filters(
			'woocommerce_blocks_product_grid_item_html',
			"<li class=\"wc-block-grid__product\">
				<a href=\"{$data->permalink}\" class=\"wc-block-grid__product-link\">
					{$data->image}
					{$data->title}
				</a>
				{$data->badge}
				{$data->price}
				{$data->rating}
				{$data->button}
			</li>",
			$data,
			$product
		);
	}

	/**
	 * Get the product image.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	protected function get_image_html( $product ) {
		return '<div class="wc-block-grid__product-image">' . $product->get_image( 'woocommerce_thumbnail' ) . '</div>';
	}

	/**
	 * Get the product title.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	protected function get_title_html( $product ) {
		if ( empty( $this->attributes['contentVisibility']['title'] ) ) {
			return '';
		}
		return '<div class="wc-block-grid__product-title">' . $product->get_title() . '</div>';
	}

	/**
	 * Render the rating icons.
	 *
	 * @param WC_Product $product Product.
	 * @return string Rendered product output.
	 */
	protected function get_rating_html( $product ) {
		if ( empty( $this->attributes['contentVisibility']['rating'] ) ) {
			return '';
		}
		$rating_count = $product->get_rating_count();
		$review_count = $product->get_review_count();
		$average      = $product->get_average_rating();

		if ( $rating_count > 0 ) {
			return sprintf(
				'<div class="wc-block-grid__product-rating">%s</div>',
				wc_get_rating_html( $average, $rating_count )
			);
		}
		return '';
	}

	/**
	 * Get the price.
	 *
	 * @param \WC_Product $product Product.
	 * @return string Rendered product output.
	 */
	protected function get_price_html( $product ) {
		if ( empty( $this->attributes['contentVisibility']['price'] ) ) {
			return '';
		}
		return sprintf(
			'<div class="wc-block-grid__product-price price">%s</div>',
			$product->get_price_html()
		);
	}

	/**
	 * Get the sale badge.
	 *
	 * @param \WC_Product $product Product.
	 * @return string Rendered product output.
	 */
	protected function get_sale_badge_html( $product ) {
		if ( empty( $this->attributes['contentVisibility']['price'] ) ) {
			return '';
		}

		if ( ! $product->is_on_sale() ) {
			return;
		}

		return '<span class="wc-block-grid__product-onsale">
			<span aria-hidden="true">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>
			<span class="screen-reader-text">' . esc_html__( 'Product on sale', 'woocommerce' ) . '</span>
		</span>';
	}

	/**
	 * Get the button.
	 *
	 * @param \WC_Product $product Product.
	 * @return string Rendered product output.
	 */
	protected function get_button_html( $product ) {
		if ( empty( $this->attributes['contentVisibility']['button'] ) ) {
			return '';
		}
		return '<div class="wp-block-button wc-block-grid__product-add-to-cart">' . $this->get_add_to_cart( $product ) . '</div>';
	}

	/**
	 * Get the "add to cart" button.
	 *
	 * @param \WC_Product $product Product.
	 * @return string Rendered product output.
	 */
	protected function get_add_to_cart( $product ) {
		$attributes = array(
			'aria-label'       => $product->add_to_cart_description(),
			'data-quantity'    => '1',
			'data-product_id'  => $product->get_id(),
			'data-product_sku' => $product->get_sku(),
			'rel'              => 'nofollow',
			'class'            => 'wp-block-button__link add_to_cart_button',
		);

		if ( $product->supports( 'ajax_add_to_cart' ) ) {
			$attributes['class'] .= ' ajax_add_to_cart';
		}

		return sprintf(
			'<a href="%s" %s>%s</a>',
			esc_url( $product->add_to_cart_url() ),
			wc_implode_html_attributes( $attributes ),
			esc_html( $product->add_to_cart_text() )
		);
	}
}
