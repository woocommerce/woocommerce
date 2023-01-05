<?php
/**
 * Pagination - Show numbered pagination for catalog pages
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/pagination.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
if ( $total <= 1 ) {
	return;
}

$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
$format  = isset( $format ) ? $format : '';

$prev_next = '<span aria-hidden="true">%1$s</span><span class="screen-reader-text">%2$s</span>';
$prev_text = sprintf( $prev_next, is_rtl() ? '&rarr;' : '&larr;', esc_html_x( 'Previous', 'pagination', 'woocommerce' ) );
$next_text = sprintf( $prev_next, is_rtl() ? '&larr;' : '&rarr;', esc_html_x( 'Next', 'pagination', 'woocommerce' ) );
?>
<nav class="woocommerce-pagination" aria-label="<?php esc_attr_e( 'Products', 'woocommerce' ); ?>">
	<?php
	echo paginate_links(
		/**
		 * Filters the paginated links arguments of the catalog pagination.
		 *
		 * @param array $args The arguments of paginate_links().
		 *
		 * @since 2.0.0
		 */
		apply_filters(
			'woocommerce_pagination_args',
			array( // WPCS: XSS ok.
				'base'      => $base,
				'format'    => $format,
				'add_args'  => false,
				'current'   => max( 1, $current ),
				'total'     => $total,
				'prev_text' => $prev_text,
				'next_text' => $next_text,
				'type'      => 'list',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);
	?>
</nav>
