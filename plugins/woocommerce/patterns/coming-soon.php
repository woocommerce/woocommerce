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

/**
 * Filters the slug for the store-only coming soon pattern.
 *
 * @since x.x.x
 *
 * @param string $slug The default slug for the store-only coming soon pattern.
 */
$store_only_slug = apply_filters( 'woocommerce_coming_soon_store_only_slug', 'woocommerce/coming-soon-store-only' );

/**
 * Filters the slug for the entire-site coming soon pattern.
 *
 * @since x.x.x
 *
 * @param string $slug The default slug for the entire-site coming soon pattern.
 */
$entire_site_slug = apply_filters( 'woocommerce_coming_soon_entire_site_slug', 'woocommerce/coming-soon-entire-site' );

$pattern_slug = $store_pages_only ? $store_only_slug : $entire_site_slug;
?>

<!-- wp:pattern {"slug":"<?php echo esc_attr( $pattern_slug ); ?>"} /-->
