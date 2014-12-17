<?php
/**
 * Webhook Logs
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Meta Boxes
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Webhook_Logs Class
 */
class WC_Meta_Box_Webhook_Logs {

	/**
	 * Log quantity
	 *
	 * @var int
	 */
	public static $quantity = 10;

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		$webhook = new WC_Webhook( $post->ID );

		$count_comments = wp_count_comments( $webhook->id );
		$total          = $count_comments->approved;
		$current        = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;
		$args           = array(
			'post_id' => $webhook->id,
			'status'  => 'approve',
			'type'    => 'webhook_delivery',
			'number'  => self::$quantity
		);

		if ( 1 < $current ) {
			$args['offset'] = ( $current - 1 ) * self::$quantity;
		}

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		$logs = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		if ( $logs ) {
			include_once( 'views/html-webhook-logs.php' );
		} else {
			echo '<p>' . __( 'This Webhook has no log yet.', 'woocommerce' ) . '</p>';
		}
	}

	/**
	 * Get the logs navigation.
	 *
	 * @param  int $total
	 *
	 * @return string
	 */
	public static function get_navigation( $total ) {
		global $post;

		$pages   = ceil( $total / self::$quantity );
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;

		$html = '<div class="webhook-logs-navigation">';

			$html .= '<p class="info" style="float: left;"><strong>';
			$html .= sprintf( '%s &ndash; Page %d of %d', _n( '1 item', sprintf( '%d items', $total ), $total, 'woocommerce' ), $current, $pages );
			$html .= '</strong></p>';

			if ( 1 < $pages ) {
				$html .= '<p class="tools" style="float: right;">';
					if ( 1 == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</button> ';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'post.php?post=' . $post->ID . '&action=edit&log_page=' . ( $current - 1 ) ) . '">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</a> ';
					}

					if ( $pages == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</button>';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'post.php?post=' . $post->ID . '&action=edit&log_page=' . ( $current + 1 ) ) . '">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</a>';
					}
				$html .= '</p>';
			}

		$html .= '<div class="clear"></div></div>';

		return $html;
	}
}
