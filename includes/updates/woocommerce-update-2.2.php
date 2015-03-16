<?php
/**
 * Update WC to 2.2.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
$update_variations = $wpdb->get_results( "
	SELECT DISTINCT posts.ID AS variation_id, posts.post_parent AS variation_parent FROM {$wpdb->posts} as posts
	LEFT OUTER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock'
	LEFT OUTER JOIN {$wpdb->postmeta} as postmeta2 ON posts.ID = postmeta2.post_id AND postmeta2.meta_key = '_manage_stock'
	WHERE posts.post_type = 'product_variation'
	AND postmeta.meta_value IS NOT NULL
	AND postmeta.meta_value != ''
	AND postmeta2.meta_value IS NULL
" );

foreach ( $update_variations as $variation ) {
	$parent_backorders = get_post_meta( $variation->variation_parent, '_backorders', true );
	add_post_meta( $variation->variation_id, '_manage_stock', 'yes', true );
	add_post_meta( $variation->variation_id, '_backorders', $parent_backorders ? $parent_backorders : 'no', true );
}

// Update taxonomy names with correct sanitized names
$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );

foreach ( $attribute_taxonomies as $attribute_taxonomy ) {
	$sanitized_attribute_name = wc_sanitize_taxonomy_name( $attribute_taxonomy->attribute_name );
	if ( $sanitized_attribute_name !== $attribute_taxonomy->attribute_name ) {
		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $sanitized_attribute_name ) ) ) {
			// Update attribute
			$wpdb->update(
				"{$wpdb->prefix}woocommerce_attribute_taxonomies",
				array(
					'attribute_name' => $sanitized_attribute_name
				),
				array(
					'attribute_id' => $attribute_taxonomy->attribute_id
				)
			);

			// Update terms
			$wpdb->update(
				$wpdb->term_taxonomy,
				array( 'taxonomy' => wc_attribute_taxonomy_name( $sanitized_attribute_name ) ),
				array( 'taxonomy' => 'pa_' . $attribute_taxonomy->attribute_name )
			);
		}
	}
}

// add webhook capabilities to shop_manager/administrator role
global $wp_roles;

if ( class_exists( 'WP_Roles' ) ) {
	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}
}

if ( is_object( $wp_roles ) ) {
	$webhook_capabilities = array(
		// post type
		'edit_shop_webhook',
		'read_shop_webhook',
		'delete_shop_webhook',
		'edit_shop_webhooks',
		'edit_others_shop_webhooks',
		'publish_shop_webhooks',
		'read_private_shop_webhooks',
		'delete_shop_webhooks',
		'delete_private_shop_webhooks',
		'delete_published_shop_webhooks',
		'delete_others_shop_webhooks',
		'edit_private_shop_webhooks',
		'edit_published_shop_webhooks',

		// terms
		'manage_shop_webhook_terms',
		'edit_shop_webhook_terms',
		'delete_shop_webhook_terms',
		'assign_shop_webhook_terms'
	);

	foreach ( $webhook_capabilities as $cap ) {
		$wp_roles->add_cap( 'shop_manager', $cap );
		$wp_roles->add_cap( 'administrator', $cap );
	}
}
