<?php
/**
 * Update WC to 2.5.0
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Updates
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Verified owner" labeling is now powered by comment meta - backfill this for existing reviews.
 */
$unverified_comments = new WP_Comment_Query( array(
	'meta_query'   => array(
		array(
			'key'     => 'verified',
			'compare' => 'NOT EXISTS'
		)
	),
	'post_type'    => 'product',
	'type__not_in' => array( 'order_note' ),
	'fields'       => 'ids',
) );

array_map( array( 'WC_Comments', 'add_comment_purchase_verification' ), $unverified_comments->comments );
