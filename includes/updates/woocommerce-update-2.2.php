<?php
/**
 * Update WC to 2.2.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

// Update options
$woocommerce_ship_to_destination = 'shipping';

if ( get_option( 'woocommerce_ship_to_billing_address_only' ) === 'yes' ) {
	$woocommerce_ship_to_destination = 'billing_only';
} elseif ( get_option( 'woocommerce_ship_to_billing' ) === 'yes' ) {
	$woocommerce_ship_to_destination = 'billing';
}

add_option( 'woocommerce_ship_to_destination', $woocommerce_ship_to_destination, '', 'no' );

// Update order statuses
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-pending'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'pending%';
	"
);
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-processing'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'processing%';
	"
);
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-on-hold'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'on-hold%';
	"
);
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-completed'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'completed%';
	"
);
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-cancelled'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'cancelled%';
	"
);
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-refunded'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'refunded%';
	"
);
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'wc-failed'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'failed%';
	"
);

// Update variations which manage stock
$update_variations = $wpdb->get_col( "
	SELECT DISTINCT posts.ID FROM {$wpdb->posts} as posts
	LEFT OUTER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock'
	LEFT OUTER JOIN {$wpdb->postmeta} as postmeta2 ON posts.ID = postmeta2.post_id AND postmeta2.meta_key = '_manage_stock'
	WHERE posts.post_type = 'product_variation'
	AND postmeta.meta_value IS NOT NULL
	AND postmeta.meta_value != ''
	AND postmeta2.meta_value IS NULL
" );

foreach ( $update_variations as $variation_id ) {
	add_post_meta( $variation_id, '_manage_stock', 'yes', true );
}
