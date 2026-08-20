<?php
/**
 * List tables: products.
 *
 * @package  WooCommerce\Admin
 * @version  3.3.0
 */

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Admin_List_Table_Products', false ) ) {
	return;
}

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once __DIR__ . '/abstract-class-wc-admin-list-table.php';
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
	 * Caches the value of the "COGS is enabled" flag.
	 *
	 * @var bool
	 */
	private bool $cogs_is_enabled;

	/**
	 * Flag indicating if the COGS value column in the product meta lookup table can be used.
	 *
	 * @var bool
	 */
	private bool $use_cogs_lookup_column;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'disable_months_dropdown', '__return_true' );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_filter( 'views_edit-product', array( $this, 'product_views' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
		// On posts_clauses, which core applies after every posts_orderby priority, so no posts_orderby
		// callback sees the relevance clause. The late priority gives earlier posts_clauses callbacks
		// that same unmodified view.
		add_filter( 'posts_clauses', array( $this, 'order_search_results' ), 9999, 2 );

		// Use hooks to prime various caches and improve products page performance.
		add_action( 'load-edit.php', array( $this, 'prime_status_counts_cache' ) );
		add_filter( 'the_posts', array( $this, 'prime_thumbnail_caches' ), 10, 2 );

		$cogs_controller              = wc_get_container()->get( CostOfGoodsSoldController::class );
		$this->cogs_is_enabled        = $cogs_controller->feature_is_enabled();
		$this->use_cogs_lookup_column = $this->cogs_is_enabled && $cogs_controller->product_meta_lookup_table_cogs_value_columns_exist();
	}

	/**
	 * Pre-warm the wp_count_posts cache before the list table renders.
	 *
	 * @internal
	 * @since 11.0.0
	 *
	 * @return void
	 */
	public function prime_status_counts_cache(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-product' !== $screen->id ) {
			return;
		}

		// Performance note: the current listings architecture prevents us from isolating wp_count_posts calls.
		// In the context of the products page, we can still isolate the underlying SQL by warming up the wp_count_posts cache.
		$cache = (object) array_map(
			static fn ( $count ) => $count ? (string) $count : (int) $count,
			wc_get_container()->get( ProductUtil::class )->get_counts_for_type( 'product' )
		);
		// Trade-off: private-status tally may read slightly high for restricted roles (other users' privates included) — non-critical.
		wp_cache_set_multiple(
			array(
				'posts-product' => $cache,
				'posts-product_readable_' . get_current_user_id() => $cache,
			),
			'counts'
		);
	}

	/**
	 * Prime featured image caches for product queries to avoid individual queries during rendering.
	 *
	 * @since 10.9.0
	 *
	 * @param \WP_Post[] $posts Posts from WP Query.
	 * @param \WP_Query  $query Current query.
	 * @return array
	 */
	public function prime_thumbnail_caches( $posts, $query ) {
		if ( $query instanceof \WP_Query && 'product' === $query->get( 'post_type' ) ) {
			update_post_thumbnail_cache( $query );
		}

		return $posts;
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="woocommerce-BlankState woocommerce-BlankState--products">';

		echo '<h2 class="woocommerce-BlankState-message">' . esc_html__( 'Ready to start selling something awesome?', 'woocommerce' ) . '</h2>';

		echo '<div class="woocommerce-BlankState-buttons">';

		echo '<a class="woocommerce-BlankState-cta button button-secondary" href="' . esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ) . '">' . esc_html__( 'Create Product', 'woocommerce' ) . '</a>';
		echo '<a class="woocommerce-BlankState-cta button button-secondary" href="' . esc_url( admin_url( 'edit.php?post_type=product&page=product_importer' ) ) . '">' . esc_html__( 'Start Import', 'woocommerce' ) . '</a>';

		echo '</div>';

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
			'price'            => 'price',
			'sku'              => 'sku',
			'name'             => 'title',
			'global_unique_id' => 'global_unique_id',
		);

		if ( $this->use_cogs_lookup_column ) {
			$custom['cogs_value'] = 'cogs_value';
		}

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

		$show_columns['global_unique_id'] = __( 'GTIN, UPC, EAN, or ISBN', 'woocommerce' );

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			$show_columns['is_in_stock'] = __( 'Stock', 'woocommerce' );
		}

		$show_columns['price'] = __( 'Price', 'woocommerce' );
		if ( $this->cogs_is_enabled ) {
			$show_columns['cogs_value'] = __( 'Cost', 'woocommerce' );
		}
		$show_columns['product_cat'] = __( 'Categories', 'woocommerce' );
		if ( is_object_in_taxonomy( 'product', 'product_tag' ) ) {
			$show_columns['product_tag'] = __( 'Tags', 'woocommerce' );
		}
		$show_columns['featured'] = '<span class="wc-featured parent-tips" data-tip="' . esc_attr__( 'Featured', 'woocommerce' ) . '">' . __( 'Featured', 'woocommerce' ) . '</span>';
		$show_columns['date']     = __( 'Date', 'woocommerce' );

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
	 * Render column: thumb.
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
			echo '&nbsp;&nbsp;&larr; <a href="' . esc_url( get_edit_post_link( $this->object->get_parent_id() ) ) . '">' . get_the_title( $this->object->get_parent_id() ) . '</a>'; // @codingStandardsIgnoreLine.
		}

		get_inline_data( $post );

		$cogs_value_html = $this->cogs_is_enabled ?
				'<div class="cogs_value">' . esc_html( $this->object->get_cogs_value() ?? '0' ) . '</div>' :
				'';

		/**
		 * Product represented by the current list-table row.
		 * Narrow the inherited object type without adding a PHPStan baseline entry.
		 * In future we should correct the type of $this->object.
		 *
		 * @var WC_Product $product
		 */
		$product        = $this->object;
		$sale_date_from = $product->get_date_on_sale_from( 'edit' );
		$sale_date_to   = $product->get_date_on_sale_to( 'edit' );
		$sale_date_from = $sale_date_from ? date_i18n( 'Y-m-d', $sale_date_from->getOffsetTimestamp() ) : '';
		$sale_date_to   = $sale_date_to ? date_i18n( 'Y-m-d', $sale_date_to->getOffsetTimestamp() ) : '';

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- the COGS value is already escaped.
		/* Custom inline data for woocommerce. */
		echo '
			<div class="hidden" id="woocommerce_inline_' . absint( $this->object->get_id() ) . '">
				<div class="menu_order">' . esc_html( $this->object->get_menu_order() ) . '</div>
				<div class="sku">' . esc_html( $this->object->get_sku() ) . '</div>
				<div class="global_unique_id">' . esc_html( $this->object->get_global_unique_id() ) . '</div>
				<div class="regular_price">' . esc_html( $this->object->get_regular_price() ) . '</div>
				<div class="sale_price">' . esc_html( $this->object->get_sale_price() ) . '</div>
				<div class="sale_price_dates_from">' . esc_html( $sale_date_from ) . '</div>
				<div class="sale_price_dates_to">' . esc_html( $sale_date_to ) . '</div>
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
				<div class="low_stock_amount">' . esc_html( $this->object->get_low_stock_amount() ) . '</div>'
				. $cogs_value_html .
			'</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render column: sku.
	 */
	protected function render_sku_column() {
		echo $this->object->get_sku() ? esc_html( $this->object->get_sku() ) : '<span class="na">&ndash;</span>';
	}

	/**
	 * Render column: global_unique_id.
	 */
	protected function render_global_unique_id_column() {
		echo $this->object->get_global_unique_id() ? esc_html( $this->object->get_global_unique_id() ) : '<span class="na">&ndash;</span>';
	}

	/**
	 * Render column: price.
	 */
	protected function render_price_column() {
		$html = $this->object->get_price_html();
		echo $html ? wp_kses_post( $html ) : '<span class="na">&ndash;</span>';
	}

	/**
	 * Render column: cost.
	 */
	protected function render_cogs_value_column() {
		$html = $this->object->get_cogs_value_html();
		echo $html ? wp_kses_post( $html ) : '<span class="na">&ndash;</span>';
	}

	/**
	 * Render column: product_cat.
	 */
	protected function render_product_cat_column() {
		$terms = get_the_terms( $this->object->get_id(), 'product_cat' );
		if ( ! $terms ) {
			echo '<span class="na">&ndash;</span>';
		} else {
			$termlist = array();
			foreach ( $terms as $term ) {
				$termlist[] = '<a href="' . esc_url( admin_url( 'edit.php?product_cat=' . $term->slug . '&post_type=product' ) ) . '">' . esc_html( $term->name ) . '</a>';
			}

			echo apply_filters( 'woocommerce_admin_product_term_list', implode( ', ', $termlist ), 'product_cat', $this->object->get_id(), $termlist, $terms ); // WPCS: XSS ok.
		}
	}

	/**
	 * Render column: product_tag.
	 */
	protected function render_product_tag_column() {
		$terms = get_the_terms( $this->object->get_id(), 'product_tag' );
		if ( ! $terms ) {
			echo '<span class="na">&ndash;</span>';
		} else {
			$termlist = array();
			foreach ( $terms as $term ) {
				$termlist[] = '<a href="' . esc_url( admin_url( 'edit.php?product_tag=' . $term->slug . '&post_type=product' ) ) . '">' . esc_html( $term->name ) . '</a>';
			}

			echo apply_filters( 'woocommerce_admin_product_term_list', implode( ', ', $termlist ), 'product_tag', $this->object->get_id(), $termlist, $terms ); // WPCS: XSS ok.
		}
	}

	/**
	 * Render column: featured.
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
	 * Render column: is_in_stock.
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
		$filters = apply_filters(
			'woocommerce_products_admin_list_table_filters',
			array(
				'product_category' => array( $this, 'render_products_category_filter' ),
				'product_type'     => array( $this, 'render_products_type_filter' ),
				'stock_status'     => array( $this, 'render_products_stock_status_filter' ),
			)
		);

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
					// Performance note: pad_counts=0 skips the hierarchical count SQL — O(all published products), degrades linearly
					// with catalog size. show_count=0 suppresses the raw wp_term_taxonomy.count that would otherwise render in its place.
					'pad_counts'         => 0,
					'show_count'         => 0,
				)
			);
		} else {
			$current_category_slug = isset( $_GET['product_cat'] ) ? wc_clean( wp_unslash( $_GET['product_cat'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters; inputs are unslashed and sanitized.
			$current_category      = $current_category_slug ? get_term_by( 'slug', $current_category_slug, 'product_cat' ) : false;
			?>
			<select class="wc-category-search" name="product_cat" data-placeholder="<?php esc_attr_e( 'Filter by category', 'woocommerce' ); ?>" data-allow_clear="true">
				<?php if ( $current_category_slug && $current_category ) : ?>
					<option value="<?php echo esc_attr( $current_category_slug ); ?>" selected="selected"><?php echo esc_html( htmlspecialchars( wp_kses_post( $current_category->name ) ) ); ?></option>
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
		$current_product_type = isset( $_REQUEST['product_type'] ) ? wc_clean( wp_unslash( $_REQUEST['product_type'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters; inputs are unslashed and sanitized.
		$output               = '<select name="product_type" id="dropdown_product_type"><option value="">' . esc_html__( 'Filter by product type', 'woocommerce' ) . '</option>';

		foreach ( wc_get_product_types() as $value => $label ) {
			$output .= '<option value="' . esc_attr( $value ) . '" ';
			$output .= selected( $value, $current_product_type, false );
			$output .= '>' . esc_html( $label ) . '</option>';

			if ( ProductType::SIMPLE === $value ) {

				$output .= '<option value="downloadable" ';
				$output .= selected( 'downloadable', $current_product_type, false );
				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . esc_html__( 'Downloadable', 'woocommerce' ) . '</option>';

				$output .= '<option value="virtual" ';
				$output .= selected( 'virtual', $current_product_type, false );
				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . esc_html__( 'Virtual', 'woocommerce' ) . '</option>';
			}
		}

		$output .= '</select>';
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $output contains only fixed markup and escaped option values.
	}

	/**
	 * Render the stock status filter for the list table.
	 *
	 * @since 3.5.0
	 */
	public function render_products_stock_status_filter() {
		$current_stock_status = isset( $_REQUEST['stock_status'] ) ? wc_clean( wp_unslash( $_REQUEST['stock_status'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters; inputs are unslashed and sanitized.
		$stock_statuses       = wc_get_product_stock_status_options();
		$output               = '<select name="stock_status"><option value="">' . esc_html__( 'Filter by stock status', 'woocommerce' ) . '</option>';

		foreach ( $stock_statuses as $status => $label ) {
			$output .= '<option ' . selected( $status, $current_stock_status, false ) . ' value="' . esc_attr( $status ) . '">' . esc_html( $label ) . '</option>';
		}

		$output .= '</select>';
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $output contains only fixed markup and escaped option values.
	}

	/**
	 * Search by SKU or ID for products.
	 *
	 * @deprecated 4.4.0 Logic moved to query_filters.
	 * @param string $where Where clause SQL.
	 * @return string
	 */
	public function sku_search( $where ) {
		wc_deprecated_function( 'WC_Admin_List_Table_Products::sku_search', '4.4.0', 'Logic moved to query_filters.' );
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
		if ( current_user_can( 'edit_others_products' ) ) {
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

		if ( 'edit.php' !== $pagenow || 'product' !== $typenow || ! get_query_var( 'product_search' ) || ! isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters; inputs are unslashed and sanitized.
			return $query;
		}

		return wc_clean( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters; inputs are unslashed and sanitized.
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		$this->remove_ordering_args();
		// Custom order by arguments.
		if ( isset( $query_vars['orderby'] ) ) {
			$orderby = strtolower( $query_vars['orderby'] );
			$order   = isset( $query_vars['order'] ) ? strtoupper( $query_vars['order'] ) : 'DESC';

			if ( 'price' === $orderby ) {
				$callback = 'DESC' === $order ? 'order_by_price_desc_post_clauses' : 'order_by_price_asc_post_clauses';
				add_filter( 'posts_clauses', array( $this, $callback ) );
			}

			if ( 'sku' === $orderby ) {
				$callback = 'DESC' === $order ? 'order_by_sku_desc_post_clauses' : 'order_by_sku_asc_post_clauses';
				add_filter( 'posts_clauses', array( $this, $callback ) );
			}

			if ( 'cogs_value' === $orderby && $this->use_cogs_lookup_column ) {
				$callback = 'DESC' === $order ? 'order_by_cogs_value_desc_post_clauses' : 'order_by_cogs_value_asc_post_clauses';
				add_filter( 'posts_clauses', array( $this, $callback ) );
			}

			if ( 'global_unique_id' === $orderby ) {
				$callback = 'DESC' === $order ? 'order_by_global_unique_id_desc_post_clauses' : 'order_by_global_unique_id_asc_post_clauses';
				add_filter( 'posts_clauses', array( $this, $callback ) );
			}
		}

		// Type filtering.
		if ( isset( $query_vars['product_type'] ) ) {
			if ( 'downloadable' === $query_vars['product_type'] ) {
				$query_vars['product_type'] = '';
				add_filter( 'posts_clauses', array( $this, 'filter_downloadable_post_clauses' ) );
			} elseif ( 'virtual' === $query_vars['product_type'] ) {
				$query_vars['product_type'] = '';
				add_filter( 'posts_clauses', array( $this, 'filter_virtual_post_clauses' ) );
			}
		}

		// Stock status filter.
		if ( ! empty( $_GET['stock_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_filter( 'posts_clauses', array( $this, 'filter_stock_status_post_clauses' ) );
		}

		// Shipping class taxonomy.
		if ( ! empty( $_GET['product_shipping_class'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_vars['tax_query'][] = array(
				'taxonomy' => 'product_shipping_class',
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['product_shipping_class'] ) ),
				'operator' => 'IN',
			);
		}

		// Search using CRUD.
		if ( ! empty( $query_vars['s'] ) ) {
			$search_term                  = wc_clean( wp_unslash( $query_vars['s'] ) );
			$data_store                   = WC_Data_Store::load( 'product' );
			$ids                          = $data_store->search_products( $search_term, '', true, true );
			$query_vars['post__in']       = array_merge( $ids, array( 0 ) );
			$query_vars['product_search'] = true;
			$default_search_order         = $this->get_default_search_order( $query_vars );
			if ( null !== $default_search_order ) {
				$query_vars['product_search_term']          = $search_term;
				$query_vars['product_search_default_order'] = $default_search_order;
			}
			unset( $query_vars['s'] );
		}

		return $query_vars;
	}

	/**
	 * Undocumented function
	 *
	 * @param array    $args  Array of SELECT statement pieces (from, where, etc).
	 * @param WP_Query $query WP_Query instance.
	 * @return array
	 */
	public function posts_clauses( $args, $query ) {

		return $args;
	}

	/**
	 * Prioritize title matches in unsorted product searches.
	 *
	 * Runs on posts_clauses rather than posts_orderby so that every posts_orderby callback, at any
	 * priority, still observes the ORDER BY clause core generated. Reading it that late means it may
	 * already carry third-party ordering, so orderby_leads_with() decides whether ranking still leads.
	 *
	 * @since 11.1.0
	 *
	 * @param array    $clauses Array of SELECT statement pieces (from, where, orderby, etc).
	 * @param WP_Query $query   WP_Query instance.
	 * @return array
	 */
	public function order_search_results( $clauses, $query ) {
		// Another callback produced this array, so confirm the piece being read is still a clause.
		if ( ! is_array( $clauses ) || ! isset( $clauses['orderby'] ) || ! is_string( $clauses['orderby'] ) ) {
			return $clauses;
		}

		$orderby              = $clauses['orderby'];
		$search_term          = $query->get( 'product_search_term' );
		$default_search_order = $query->get( 'product_search_default_order' );
		if ( ! $query->get( 'product_search' ) || ! is_string( $search_term ) || '' === $search_term ) {
			return $clauses;
		}

		global $wpdb;
		if ( 'date' === $default_search_order ) {
			if ( $query->get( 'orderby' ) || ! $this->orderby_leads_with( $orderby, "{$wpdb->posts}.post_date DESC" ) ) {
				return $clauses;
			}
		} elseif ( 'modified' === $default_search_order ) {
			$post_status = $query->get( 'post_status' );
			$order       = $query->get( 'order' );
			if (
				'modified' !== $query->get( 'orderby' )
				|| ! is_string( $post_status )
				|| ! is_string( $order )
				|| ( ! ( 'draft' === $post_status && 'DESC' === $order ) && ! ( 'pending' === $post_status && 'ASC' === $order ) )
				|| ! $this->orderby_leads_with( $orderby, "{$wpdb->posts}.post_modified {$order}" )
			) {
				return $clauses;
			}
		} else {
			return $clauses;
		}

		$case_clause = $this->build_title_match_case_clause( $search_term );
		if ( '' === $case_clause ) {
			return $clauses;
		}

		$clauses['orderby'] = $case_clause . ', ' . $orderby;

		return $clauses;
	}

	/**
	 * Determine whether a clause still leads with the ordering core generated for this view.
	 *
	 * A third party that appends a tiebreak leaves core's ordering in charge of the primary sort, so
	 * ranking can still lead. One that prepends or replaces has taken the primary sort over, and
	 * ranking defers to it. Only a comma may follow the core clause, so a longer token that merely
	 * starts the same way is not mistaken for it.
	 *
	 * @since 11.1.0
	 *
	 * @param string $orderby      Clause observed on the query.
	 * @param string $core_orderby Clause core generated for this view.
	 * @return bool
	 */
	private function orderby_leads_with( $orderby, $core_orderby ) {
		return $orderby === $core_orderby || 0 === strpos( $orderby, $core_orderby . ',' );
	}

	/**
	 * Build the relevance expression that ranks title matches ahead of the remaining search results.
	 *
	 * @since 11.1.0
	 *
	 * @param string $search_term Search term the product matcher already ran against.
	 * @return string CASE expression, or an empty string when the term yields no title predicates.
	 */
	private function build_title_match_case_clause( $search_term ) {
		global $wpdb;

		// Group exactly as search_products() does, so ranking and matching agree on what an OR group is.
		// \s+or\s+ alone would not: \s also matches \x0B and \x0C, which wc_clean() leaves in place.
		$search_groups = stristr( $search_term, ' or ' ) ? preg_split( '/\s+or\s+/i', $search_term ) : array( $search_term );
		if ( ! $search_groups ) {
			return '';
		}

		$title_match_groups = array();
		foreach ( $search_groups as $search_group ) {
			$title_match_queries = array();
			foreach ( $this->parse_search_terms( $search_group ) as $title_search_term ) {
				$title_match_queries[] = $wpdb->prepare(
					"{$wpdb->posts}.post_title LIKE %s",
					'%' . $wpdb->esc_like( $title_search_term ) . '%'
				);
			}

			if ( $title_match_queries ) {
				$title_match_groups[] = '(' . implode( ' AND ', $title_match_queries ) . ')';
			}
		}

		if ( ! $title_match_groups ) {
			return '';
		}

		return 'CASE WHEN (' . implode( ' OR ', $title_match_groups ) . ') THEN 0 ELSE 1 END';
	}

	/**
	 * Get the default ordering mode for a product search.
	 *
	 * @param array $query_vars Query variables.
	 * @return string|null
	 */
	private function get_default_search_order( $query_vars ) {
		$orderby = $query_vars['orderby'] ?? '';
		if ( ! is_string( $orderby ) ) {
			return null;
		}

		if ( '' === $orderby ) {
			return 'date';
		}

		// Draft and Pending views receive modified ordering from wp_edit_posts_query() only when no orderby was requested.
		if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only presence check distinguishes explicit sorting from the core default.
			return null;
		}

		$post_status = $query_vars['post_status'] ?? '';
		$order       = $query_vars['order'] ?? '';
		if ( 'modified' !== strtolower( $orderby ) || ! is_string( $post_status ) || ! is_string( $order ) ) {
			return null;
		}

		$order = $order ? strtoupper( $order ) : 'DESC';
		if ( ( 'draft' === $post_status && 'DESC' === $order ) || ( 'pending' === $post_status && 'ASC' === $order ) ) {
			return 'modified';
		}

		return null;
	}

	/**
	 * Parse a product search group using WooCommerce search-term rules.
	 *
	 * @param string $search_group Search group without OR separators.
	 * @return string[]
	 */
	private function parse_search_terms( $search_group ) {
		// Mirrors WP_Query::parse_search_terms(): each match is either a double-quoted phrase (closed by
		// a quote or the end of the string) or a run of characters delimited by tab, space, double quote,
		// comma, or plus. The `u` modifier is intentionally omitted for parity with WordPress core.
		if ( ! preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $search_group, $matches ) ) {
			return array( $search_group );
		}

		$search_terms = $this->get_valid_search_terms( $matches[0] );
		$count        = count( $search_terms );

		return 9 < $count || 0 === $count ? array( $search_group ) : $search_terms;
	}

	/**
	 * Remove unsuitable product search terms.
	 *
	 * Mirrors WP_Query::get_search_terms(), including its stopword and single-character rules.
	 *
	 * @param string[] $terms Search terms.
	 * @return string[]
	 */
	private function get_valid_search_terms( $terms ) {
		$valid_terms = array();
		$stopwords   = $this->get_search_stopwords();

		foreach ( $terms as $term ) {
			// Keep before/after spaces when term is for exact match, otherwise trim quotes and spaces.
			if ( preg_match( '/^".+"$/', $term ) ) {
				$term = trim( $term, "\"'" );
			} else {
				$term = trim( $term, "\"' " );
			}

			if ( empty( $term ) || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
				continue;
			}

			if ( in_array( wc_strtolower( $term ), $stopwords, true ) ) {
				continue;
			}

			$valid_terms[] = $term;
		}

		return $valid_terms;
	}

	/**
	 * Retrieve stopwords used when parsing product search terms.
	 *
	 * @return string[]
	 */
	private function get_search_stopwords() {
		// Translators: This is a comma-separated list of very common words that should be excluded from a search, like a, an, and the. These are usually called "stopwords". You should not simply translate these individual words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		$stopwords = array_map(
			'wc_strtolower',
			array_map(
				'trim',
				explode(
					',',
					_x(
						'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
						'Comma-separated list of search stopwords in your language',
						'woocommerce'
					)
				)
			)
		);

		/** This filter is documented in wp-includes/class-wp-query.php. */
		$filtered_stopwords = apply_filters( 'wp_search_stopwords', $stopwords ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- The hook is documented by WordPress.

		// A callback can return anything; keep only strings so the strict in_array() below stays safe.
		return is_array( $filtered_stopwords ) ? array_filter( $filtered_stopwords, 'is_string' ) : $stopwords;
	}

	/**
	 * Remove ordering queries.
	 *
	 * @param array $posts Posts array, keeping this for backwards compatibility defaulting to empty array.
	 * @return array
	 */
	public function remove_ordering_args( $posts = array() ) {
		remove_filter( 'posts_clauses', array( $this, 'order_by_price_asc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_price_desc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_sku_asc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_sku_desc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_global_unique_id_asc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_global_unique_id_desc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'filter_downloadable_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'filter_virtual_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'filter_stock_status_post_clauses' ) );
		if ( $this->use_cogs_lookup_column ) {
			remove_filter( 'posts_clauses', array( $this, 'order_by_cogs_value_asc_post_clauses' ) );
			remove_filter( 'posts_clauses', array( $this, 'order_by_cogs_value_desc_post_clauses' ) );
		}
		return $posts; // Keeping this here for backward compatibility.
	}

	/**
	 * Handle numeric price sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_price_asc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.min_price ASC, wc_product_meta_lookup.product_id ASC ';
		return $args;
	}

	/**
	 * Handle numeric price sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_price_desc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.max_price DESC, wc_product_meta_lookup.product_id DESC ';
		return $args;
	}

	/**
	 * Handle sku sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_sku_asc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.sku ASC, wc_product_meta_lookup.product_id ASC ';
		return $args;
	}

	/**
	 * Handle sku sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_sku_desc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.sku DESC, wc_product_meta_lookup.product_id DESC ';
		return $args;
	}

	/**
	 * Handle COGS value sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_cogs_value_asc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.cogs_total_value ASC, wc_product_meta_lookup.product_id ASC ';
		return $args;
	}

	/**
	 * Handle COGS value sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_cogs_value_desc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.cogs_total_value DESC, wc_product_meta_lookup.product_id DESC ';
		return $args;
	}

	/**
	 * Handle global unique ID sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_global_unique_id_asc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.global_unique_id ASC, wc_product_meta_lookup.product_id ASC ';
		return $args;
	}

	/**
	 * Handle global unique ID sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_global_unique_id_desc_post_clauses( $args ) {
		$args['join']    = $this->append_product_sorting_table_join( $args['join'] );
		$args['orderby'] = ' wc_product_meta_lookup.global_unique_id DESC, wc_product_meta_lookup.product_id DESC ';
		return $args;
	}

	/**
	 * Filter by type.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function filter_downloadable_post_clauses( $args ) {
		$args['join']   = $this->append_product_sorting_table_join( $args['join'] );
		$args['where'] .= ' AND wc_product_meta_lookup.downloadable=1 ';
		return $args;
	}

	/**
	 * Filter by type.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function filter_virtual_post_clauses( $args ) {
		$args['join']   = $this->append_product_sorting_table_join( $args['join'] );
		$args['where'] .= ' AND wc_product_meta_lookup.virtual=1 ';
		return $args;
	}

	/**
	 * Filter by stock status.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function filter_stock_status_post_clauses( $args ) {
		global $wpdb;
		if ( ! empty( $_GET['stock_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$stock_status = wc_clean( wp_unslash( $_GET['stock_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ProductStockStatus::OUT_OF_STOCK === $stock_status ) {
				// Only published variations qualify their parent for this discoverability filter.
				// Other statuses retain normal aggregate-parent behavior.
				$args['where'] .= $wpdb->prepare(
					" AND {$wpdb->posts}.ID IN (
						SELECT stock_status_products.product_id
						FROM (
							SELECT DISTINCT CAST(
								CASE
									WHEN stock_status_posts.post_type = 'product_variation' THEN stock_status_posts.post_parent
									ELSE stock_status_lookup.product_id
								END AS UNSIGNED
							) AS product_id
							FROM {$wpdb->wc_product_meta_lookup} stock_status_lookup
							INNER JOIN {$wpdb->posts} stock_status_posts
								ON stock_status_posts.ID = stock_status_lookup.product_id
							WHERE stock_status_lookup.stock_status = %s
								AND (
									stock_status_posts.post_type = 'product'
									OR (
										stock_status_posts.post_type = 'product_variation'
										AND stock_status_posts.post_status = 'publish'
									)
								)
						) stock_status_products
					) ",
					$stock_status
				);
			} else {
				$args['join']   = $this->append_product_sorting_table_join( $args['join'] );
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.stock_status=%s ', $stock_status );
			}
		}
		return $args;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	private function append_product_sorting_table_join( $sql ) {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
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
		if ( isset( $_GET['product_shipping_class'] ) && '0' !== $_GET['product_shipping_class'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters; inputs are unslashed and sanitized.
			$replaced_where   = str_replace( ".post_type = 'product'", ".post_type = 'product_variation'", $pieces['where'] );
			$pieces['where'] .= " OR {$wpdb->posts}.ID in (
				SELECT {$wpdb->posts}.post_parent FROM
				{$wpdb->posts} LEFT JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)
				WHERE 1=1 $replaced_where
			)";
			return $pieces;
		}
		return $pieces;
	}

	/**
	 * Add a sample product badge to the product list table.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 *
	 * @since 8.8.0
	 * @deprecated 11.1.0
	 */
	public function add_sample_product_badge( $column_name, $post_id ) {
		wc_deprecated_function( __METHOD__, '11.1.0' );
	}

	/**
	 * Define which columns are hidden by default.
	 *
	 * @return array
	 */
	protected function define_hidden_columns() {
		return array(
			'global_unique_id',
		);
	}
}
