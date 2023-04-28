<?php
/**
 * Title: Featured Category Triple
 * Slug: woocommerce-blocks/featured-category-triple
 * Categories: WooCommerce
 */

// Below query is temporary work around to get patterns previews to work.
$transient_name = 'wc_blocks_pattern_featured_category_triple';
$categories     = get_transient( $transient_name );

if ( ( false === $categories ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
	global $wpdb;

	$categories = $wpdb->get_results(
		"SELECT tt.term_id FROM {$wpdb->prefix}term_taxonomy AS tt
		LEFT JOIN {$wpdb->prefix}terms AS t ON tt.term_id = t.term_id
		WHERE tt.taxonomy = 'product_cat' AND tt.count > 0 AND tt.parent = 0 AND t.slug != 'uncategorized'
		LIMIT 3",
		ARRAY_A
	);

	set_transient( $transient_name, $categories, DAY_IN_SECONDS * 14 );
}

$cat1 = $categories[0]['term_id'] ? $categories[0]['term_id'] : 0;
$cat2 = $categories[1]['term_id'] ? $categories[1]['term_id'] : 0;
$cat3 = $categories[2]['term_id'] ? $categories[2]['term_id'] : 0;

?>
<!-- wp:columns {"align":"full","style":{"spacing":{"blockGap":{"top":"0","left":"0"},"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
<div class="wp-block-columns alignfull" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column -->
	<div class="wp-block-column"><?php echo '<!-- wp:woocommerce/featured-category {"dimRatio":0,"editMode":false,"imageFit":"cover","categoryId":' . esc_attr( $cat1 ) . ',"overlayColor":"#F6F6F6","showDesc":false,"textColor":"foreground"} -->'; ?>
			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons"><!-- wp:button {"lock":{"move":true,"remove":true}} -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_category_link( $cat1 ) ); ?>"><?php esc_html_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a></div>
				<!-- /wp:button --></div>
			<!-- /wp:buttons -->
			<!-- /wp:woocommerce/featured-category --></div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column"><?php echo '<!-- wp:woocommerce/featured-category {"dimRatio":0,"editMode":false,"imageFit":"cover","categoryId":' . esc_attr( $cat2 ) . ',"overlayColor":"#F6F6F6","showDesc":false,"textColor":"foreground"} -->'; ?>
			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons"><!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_category_link( $cat2 ) ); ?>"><?php esc_html_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a></div>
				<!-- /wp:button --></div>
			<!-- /wp:buttons -->
			<!-- /wp:woocommerce/featured-category --></div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column"><?php echo '<!-- wp:woocommerce/featured-category {"dimRatio":0,"editMode":false,"imageFit":"cover","categoryId":' . esc_attr( $cat3 ) . ',"overlayColor":"#F6F6F6","showDesc":false,"textColor":"foreground"} -->'; ?>
			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons"><!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_category_link( $cat3 ) ); ?>"><?php esc_html_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a></div>
				<!-- /wp:button --></div>
			<!-- /wp:buttons -->
		<!-- /wp:woocommerce/featured-category --></div>
	<!-- /wp:column --></div>
<!-- /wp:columns -->
