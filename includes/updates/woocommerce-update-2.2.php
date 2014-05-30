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
$wpdb->show_errors();
// Update order statuses
$wpdb->query( "
	UPDATE {$wpdb->posts} as posts
	LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
	LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
	LEFT JOIN {$wpdb->terms} AS term USING( term_id )
	SET posts.post_status = 'pending'
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
	SET posts.post_status = 'processing'
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
	SET posts.post_status = 'on-hold'
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
	SET posts.post_status = 'completed'
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
	SET posts.post_status = 'cancelled'
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
	SET posts.post_status = 'refunded'
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
	SET posts.post_status = 'failed'
	WHERE posts.post_type = 'shop_order' 
	AND posts.post_status = 'publish'
	AND tax.taxonomy = 'shop_order_status'
	AND	term.slug LIKE 'failed%';
	"
);