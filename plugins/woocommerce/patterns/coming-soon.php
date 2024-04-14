<?php
/**
 * Title: Coming Soon
 * Slug: woocommerce/coming-soon
 * Categories: WooCommerce
 *
 * @package WooCommerce\Blocks
 */

$store_pages_only = 'yes' === get_option( 'woocommerce_store_pages_only', 'no' );
?>

<!-- wp:pattern {"slug":"woocommerce/<?php echo $store_pages_only ? 'coming-soon-store-only' : 'coming-soon-entire-site'; ?>"} /-->
