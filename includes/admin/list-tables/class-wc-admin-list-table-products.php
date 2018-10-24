<?php
/**
 * List tables: products.
 *
 * @package  WooCommerce/Admin
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Admin_List_Table_Products', false ) ) {
	return;
}

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once 'abstract-class-wc-admin-list-table.php';
}

/**
 * WC_Admin_List_Table_Products Class.
 */
class WC_Admin_List_Table_Products extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'product';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'disable_months_dropdown', '__return_true' );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_filter( 'views_edit-product', array( $this, 'product_views' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'posts_clauses', array( $this, 'add_variation_parents_for_shipping_class' ), 10, 2 );
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="woocommerce-BlankState">';
		echo '<h2 class="woocommerce-BlankState-message">' . esc_html__( 'Ready to start selling something awesome?', 'woocommerce' ) . '</h2>';
		echo '<a class="woocommerce-BlankState-cta button-primary button" href="' . esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ) . '">' . esc_html__( 'Create your first product!', 'woocommerce' ) . '</a>';
		echo '<a class="woocommerce-BlankState-cta button" href="' . esc_url( admin_url( 'edit.php?post_type=product&page=product_importer' ) ) . '">' . esc_html__( 'Import products from a CSV file', 'woocommerce' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'name';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		/* translators: %d: product ID. */
		return array_merge( array( 'id' => sprintf( __( 'ID: %d', 'woocommerce' ), $post->ID ) ), $actions );
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = array(
			'price' => 'price',
			'sku'   => 'sku',
			'name'  => 'title',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		if ( empty( $columns ) && ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['title'], $columns['comments'], $columns['date'] );

		$show_columns          = array();
		$show_columns['cb']    = '<input type="checkbox" />';
		$show_columns['thumb'] = '<span class="wc-image tips" data-tip="' . esc_attr__( 'Image', 'woocommerce' ) . '">' . __( 'Image', 'woocommerce' ) . '</span>';
		$show_columns['name']  = __( 'Name', 'woocommerce' );

		if ( wc_product_sku_enabled() ) {
			$show_columns['sku'] = __( 'SKU', 'woocommerce' );
		}

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			$show_columns['is_in_stock'] = __( 'Stock', 'woocommerce' );
		}

		$show_columns['price']        = __( 'Price', 'woocommerce' );
		$show_columns['product_cat']  = __( 'Categories', 'woocommerce' );
		$show_columns['product_tag']  = __( 'Tags', 'woocommerce' );
		$show_columns['featured']     = '<span class="wc-featured parent-tips" data-tip="' . esc_attr__( 'Featured', 'woocommerce' ) . '">' . __( 'Featured', 'woocommerce' ) . '</span>';
		$show_columns['date']         = __( 'Date', 'woocommerce' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_product global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_product;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$the_product  = wc_get_product( $post_id );
			$this->object = $the_product;
		}
	}

	/**
	 * Render columm: thumb.
	 */
	protected function render_thumb_column() {
		echo '<a href="' . esc_url( get_edit_post_link( $this->object->get_id() ) ) . '">' . $this->object->get_image( 'thumbnail' ) . '</a>'; // WPCS: XSS ok.
	}

	/**
	 * Render column: name.
	 */
	protected function render_name_column() {
		global $post;

		$edit_link = get_edit_post_link( $this->object->get_id() );
		$title     = _draft_or_post_title();

		echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';

		_post_states( $post );

		echo '</strong>';

		if ( $this->object->get_parent_id() > 0 ) {
			echo '&nbsp;&nbsp;&larr; <a href="' . esc_url( get_edit_post_link( $this->object->get_parent_id() ) ) . '">' . get_the_title( $this->object->get_parent_id() ) . '</a>';
		}

		get_inline_data( $post );

		/* Custom inline data for woocommerce. */
		echo '
			<div class="hidden" id="woocommerce_inline_' . absint( $this->object->get_id() ) . '">
				<div class="menu_order">' . absint( $this->object->get_menu_order() ) . '</div>
				<div class="sku">' . esc_html( $this->object->get_sku() ) . '</div>
				<div class="regular_price">' . esc_html( $this->object->get_regular_price() ) . '</div>
				<div class="sale_price">' . esc_html( $this->object->get_sale_price() ) . '</div>
				<div class="weight">' . esc_html( $this->object->get_weight() ) . '</div>
				<div class="length">' . esc_html( $this->object->get_length() ) . '</div>
				<div class="width">' . esc_html( $this->object->get_width() ) . '</div>
				<div class="height">' . esc_html( $this->object->get_height() ) . '</div>
				<div class="shipping_class">' . esc_html( $this->object->get_shipping_class() ) . '</div>
				<div class="visibility">' . esc_html( $this->object->get_catalog_visibility() ) . '</div>
				<div class="stock_status">' . esc_html( $this->object->get_stock_status() ) . '</div>
				<div class="stock">' . esc_html( $this->object->get_stock_quantity() ) . '</div>
				<div class="manage_stock">' . esc_html( wc_bool_to_string( $this->object->get_manage_stock() ) ) . '</div>
				<div class="featured">' . esc_html( wc_bool_to_string( $this->object->get_featured() ) ) . '</div>
				<div class="product_type">' . esc_html( $this->object->get_type() ) . '</div>
				<div class="product_is_virtual">' . esc_html( wc_bool_to_string( $this->object->get_virtual() ) ) . '</div>
				<div class="tax_status">' . esc_html( $this->object->get_tax_status() ) . '</div>
				<div class="tax_class">' . esc_html( $this->object->get_tax_class() ) . '</div>
				<div class="backorders">' . esc_html( $this->object->get_backorders() ) . '</div>
				<div class="low_stock_amount">' . esc_html( $this->object->get_low_stock_amount() ) . '</div>
			</div>
		';
	}

	/**
	 * Render columm: sku.
	 */
	protected function render_sku_column() {
		echo $this->object->get_sku() ? esc_html( $this->object->get_sku() ) : '<span class="na">&ndash;</span>';
	}

	/**
	 * Render columm: price.
	 */
	protected function render_price_column() {
		echo $this->object->get_price_html() ? wp_kses_post( $this->object->get_price_html() ) : '<span class="na">&ndash;</span>';
	}

	/**
	 * Render columm: product_cat.
	 */
	protected function render_product_cat_column() {
		$terms = get_the_terms( $this->object->get_id(), 'product_cat' );
		if ( ! $terms ) {
			echo '<span class="na">&ndash;</span>';
		} else {
			$termlist = array();
			foreach ( $terms as $term ) {
				$termlist[] = '<a href="' . esc_url( admin_url( 'edit.php?product_cat=' . $term->slug . '&post_type=product' ) ) . ' ">' . esc_html( $term->name ) . '</a>';
			}

			echo apply_filters( 'woocommerce_admin_product_term_list', implode( ', ', $termlist ), 'product_cat', $this->object->get_id(), $termlist, $terms ); // WPCS: XSS ok.
		}
	}

	/**
	 * Render columm: product_tag.
	 */
	protected function render_product_tag_column() {
		$terms = get_the_terms( $this->object->get_id(), 'product_tag' );
		if ( ! $terms ) {
			echo '<span class="na">&ndash;</span>';
		} else {
			$termlist = array();
			foreach ( $terms as $term ) {
				$termlist[] = '<a href="' . esc_url( admin_url( 'edit.php?product_tag=' . $term->slug . '&post_type=product' ) ) . ' ">' . esc_html( $term->name ) . '</a>';
			}

			echo apply_filters( 'woocommerce_admin_product_term_list', implode( ', ', $termlist ), 'product_tag', $this->object->get_id(), $termlist, $terms ); // WPCS: XSS ok.
		}
	}

	/**
	 * Render columm: featured.
	 */
	protected function render_featured_column() {
		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_feature_product&product_id=' . $this->object->get_id() ), 'woocommerce-feature-product' );
		echo '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr__( 'Toggle featured', 'woocommerce' ) . '">';
		if ( $this->object->is_featured() ) {
			echo '<span class="wc-featured tips" data-tip="' . esc_attr__( 'Yes', 'woocommerce' ) . '">' . esc_html__( 'Yes', 'woocommerce' ) . '</span>';
		} else {
			echo '<span class="wc-featured not-featured tips" data-tip="' . esc_attr__( 'No', 'woocommerce' ) . '">' . esc_html__( 'No', 'woocommerce' ) . '</span>';
		}
		echo '</a>';
	}

	/**
	 * Render columm: is_in_stock.
	 */
	protected function render_is_in_stock_column() {
		if ( $this->object->is_on_backorder() ) {
			$stock_html = '<mark class="onbackorder">' . __( 'On backorder', 'woocommerce' ) . '</mark>';
		} elseif ( $this->object->is_in_stock() ) {
			$stock_html = '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
		} else {
			$stock_html = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
		}

		if ( $this->object->managing_stock() ) {
			$stock_html .= ' (' . wc_stock_amount( $this->object->get_stock_quantity() ) . ')';
		}

		echo wp_kses_post( apply_filters( 'woocommerce_admin_stock_html', $stock_html, $this->object ) );
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars Array of query vars.
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'sku';
		return $public_query_vars;
	}

	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$filters = apply_filters( 'woocommerce_products_admin_list_table_filters', array(
			'product_category' => array( $this, 'render_products_category_filter' ),
			'product_type'     => array( $this, 'render_products_type_filter' ),
			'stock_status'     => array( $this, 'render_products_stock_status_filter' ),
		) );

		ob_start();
		foreach ( $filters as $filter_callback ) {
			call_user_func( $filter_callback );
		}
		$output = ob_get_clean();

		echo apply_filters( 'woocommerce_product_filters', $output ); // WPCS: XSS ok.
	}

	/**
	 * Render the product category filter for the list table.
	 *
	 * @since 3.5.0
	 */
	protected function render_products_category_filter() {
		$categories_count = (int) wp_count_terms( 'product_cat' );

		if ( $categories_count <= apply_filters( 'woocommerce_product_category_filter_threshold', 100 ) ) {
			wc_product_dropdown_categories(
				array(
					'option_select_text' => __( 'Filter by category', 'woocommerce' ),
					'hide_empty'         => 0,
				)
			);
		} else {
			$current_category_slug = isset( $_GET['product_cat'] ) ? wc_clean( wp_unslash( $_GET['product_cat'] ) ) : false; // WPCS: input var ok, CSRF ok.
			$current_category      = $current_category_slug ? get_term_by( 'slug', $current_category_slug, 'product_cat' ) : false;
			?>
			<select class="wc-category-search" name="product_cat" data-placeholder="<?php esc_attr_e( 'Filter by category', 'woocommerce' ); ?>" data-allow_clear="true">
				<?php if ( $current_category_slug && $current_category ) : ?>
					<option value="<?php echo esc_attr( $current_category_slug ); ?>" selected="selected"><?php echo esc_html( $current_category->name ); ?><option>
				<?php endif; ?>
			</select>
			<?php
		}
	}

	/**
	 * Render the product type filter for the list table.
	 *
	 * @since 3.5.0
	 */
	protected function render_products_type_filter() {
		$current_product_type = isset( $_REQUEST['product_type'] ) ? wc_clean( wp_unslash( $_REQUEST['product_type'] ) ) : false; // WPCS: input var ok, sanitization ok.
		$output               = '<select name="product_type" id="dropdown_product_type"><option value="">' . __( 'Filter by product type', 'woocommerce' ) . '</option>';

		foreach ( wc_get_product_types() as $value => $label ) {
			$output .= '<option value="' . esc_attr( $value ) . '" ';
			$output .= selected( $value, $current_product_type, false );
			$output .= '>' . esc_html( $label ) . '</option>';

			if ( 'simple' === $value ) {

				$output .= '<option value="downloadable" ';
				$output .= selected( 'downloadable', $current_product_type, false );
				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __( 'Downloadable', 'woocommerce' ) . '</option>';

				$output .= '<option value="virtual" ';
				$output .= selected( 'virtual', $current_product_type, false );
				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __( 'Virtual', 'woocommerce' ) . '</option>';
			}
		}

		$output .= '</select>';
		echo $output; // WPCS: XSS ok.
	}

	/**
	 * Render the stock status filter for the list table.
	 *
	 * @since 3.5.0
	 */
	public function render_products_stock_status_filter() {
		$current_stock_status = isset( $_REQUEST['stock_status'] ) ? wc_clean( wp_unslash( $_REQUEST['stock_status'] ) ) : false; // WPCS: input var ok, sanitization ok.
		$stock_statuses       = wc_get_product_stock_status_options();
		$output               = '<select name="stock_status"><option value="">' . esc_html__( 'Filter by stock status', 'woocommerce' ) . '</option>';

		foreach ( $stock_statuses as $status => $label ) {
			$output .= '<option ' . selected( $status, $current_stock_status, false ) . ' value="' . esc_attr( $status ) . '">' . esc_html( $label ) . '</option>';
		}

		$output .= '</select>';
		echo $output; // WPCS: XSS ok.
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		if ( isset( $query_vars['orderby'] ) ) {
			if ( 'price' === $query_vars['orderby'] ) {
				$query_vars = array_merge( $query_vars, array(
					// phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
					'meta_key' => '_price',
					'orderby'  => 'meta_value_num',
				) );
			}
			if ( 'sku' === $query_vars['orderby'] ) {
				$query_vars = array_merge( $query_vars, array(
					// phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
					'meta_key' => '_sku',
					'orderby'  => 'meta_value',
				) );
			}
		}

		if ( isset( $query_vars['product_type'] ) ) {
			if ( 'downloadable' === $query_vars['product_type'] ) {
				$query_vars['product_type'] = '';
				$query_vars['meta_value']   = 'yes'; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
				$query_vars['meta_key']     = '_downloadable'; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
			} elseif ( 'virtual' === $query_vars['product_type'] ) {
				$query_vars['product_type'] = '';
				$query_vars['meta_value']   = 'yes'; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
				$query_vars['meta_key']     = '_virtual'; // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
			}
		}

		if ( isset( $_GET['product_shipping_class'] ) && '0' === $_GET['product_shipping_class'] ) { // WPCS: input var ok.
			$query_vars['tax_query'][] = array(
				'taxonomy' => 'product_shipping_class',
				'field'    => 'id',
				'terms'    => get_terms( 'product_shipping_class', array( 'fields' => 'ids' ) ),
				'operator' => 'NOT IN',
			);
		}

		if ( ! empty( $_GET['stock_status'] ) ) {
			if ( ! isset( $query_vars['meta_query'] ) ) {
				$query_vars['meta_query'] = array(); // phpcs:ignore WordPress.VIP.SlowDBQuery.slow_db_query_meta_query
			}

			$query_vars['meta_query'][] = array(
				'key'   => '_stock_status',
				'value' => wc_clean( wp_unslash( $_GET['stock_status'] ) ),
			);
		}

		// Search using CRUD.
		if ( ! empty( $query_vars['s'] ) ) {
			$data_store                   = WC_Data_Store::load( 'product' );
			$ids                          = $data_store->search_products( wc_clean( wp_unslash( $query_vars['s'] ) ), '', true, true );
			$query_vars['post__in']       = array_merge( $ids, array( 0 ) );
			$query_vars['product_search'] = true;
			unset( $query_vars['s'] );
		}

		return $query_vars;
	}

	/**
	 * Search by SKU or ID for products.
	 *
	 * @deprecated Logic moved to query_filters.
	 * @param string $where Where clause SQL.
	 * @return string
	 */
	public function sku_search( $where ) {
		return $where;
	}

	/**
	 * Change views on the edit product screen.
	 *
	 * @param  array $views Array of views.
	 * @return array
	 */
	public function product_views( $views ) {
		global $wp_query;

		// Products do not have authors.
		unset( $views['mine'] );

		// Add sorting link.
		if ( current_user_can( 'edit_others_pages' ) ) {
			$class            = ( isset( $wp_query->query['orderby'] ) && 'menu_order title' === $wp_query->query['orderby'] ) ? 'current' : '';
			$query_string     = remove_query_arg( array( 'orderby', 'order' ) );
			$query_string     = add_query_arg( 'orderby', rawurlencode( 'menu_order title' ), $query_string );
			$query_string     = add_query_arg( 'order', rawurlencode( 'ASC' ), $query_string );
			$views['byorder'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Sorting', 'woocommerce' ) . '</a>';
		}

		return $views;
	}

	/**
	 * Change the label when searching products
	 *
	 * @param string $query Search Query.
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'product' !== $typenow || ! get_query_var( 'product_search' ) || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return $query;
		}

		return wc_clean( wp_unslash( $_GET['s'] ) ); // WPCS: input var ok, sanitization ok.
	}

	/**
	 * Modifies post query so that it includes parent products whose variations have particular shipping class assigned.
	 *
	 * @param array    $pieces   Array of SELECT statement pieces (from, where, etc).
	 * @param WP_Query $wp_query WP_Query instance.
	 * @return array             Array of products, including parents of variations.
	 */
	public function add_variation_parents_for_shipping_class( $pieces, $wp_query ) {
		global $wpdb;
		if ( isset( $_GET['product_shipping_class'] ) && '0' !== $_GET['product_shipping_class'] ) { // WPCS: input var ok.
			$replaced_where   = str_replace( ".post_type = 'product'", ".post_type = 'product_variation'", $pieces['where'] );
			$pieces['where'] .= " OR {$wpdb->posts}.ID in (
				SELECT {$wpdb->posts}.post_parent FROM 
				wp_posts  LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)
				WHERE 1=1 $replaced_where
			)";
			return $pieces;
		}
		return $pieces;
	}

}
