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
$default_pattern  = $store_pages_only ? 'coming-soon-store-only' : 'page-coming-soon-default';

?>

<!-- wp:pattern {"slug":"woocommerce/<?php echo $default_pattern; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"} /-->
