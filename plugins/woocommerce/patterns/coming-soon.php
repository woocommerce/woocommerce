<?php
/**
 * Title: Coming Soon
 * Slug: woocommerce/coming-soon
 * Categories: WooCommerce
 * Inserter: false
 * Feature Flag: launch-your-store
 *
 * @package WooCommerce\Blocks
 */

$store_pages_only = 'yes' === get_option( 'woocommerce_store_pages_only', 'no' );

$store_only_slug = apply_filters( 'woocommerce_coming_soon_store_only_slug', 'coming-soon-store-only' );
$entire_site_slug = apply_filters( 'woocommerce_coming_soon_entire_site_slug', 'coming-soon-entire-site' );

$pattern_slug = $store_pages_only ? $store_only_slug : $entire_site_slug;
?>

<!-- wp:pattern {"slug":"woocommerce/<?php echo esc_attr( $pattern_slug ); ?>"} /-->
