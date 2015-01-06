<?php
/**
 * WooCommerce Webhooks Table List
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_Admin_Webhooks_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'webhook', 'woocommerce' ),
			'plural'   => __( 'webhooks', 'woocommerce' ),
			'ajax'     => false
		) );
	}

	/**
	 * Get list columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Name', 'woocommerce' ),
			'status'       => __( 'Status', 'woocommerce' ),
			'topic'        => __( 'Topic', 'woocommerce' ),
			'delivery_url' => __( 'Delivery URL', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Column cb.
	 *
	 * @param  WC_Post $webhook
	 *
	 * @return string
	 */
	public function column_cb( $webhook ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $webhook->ID );
	}

	/**
	 * Webhook columns.
	 *
	 * @param  WC_Post $webhook
	 * @param  string $column_name
	 *
	 * @return string
	 */
	public function column_default( $webhook, $column_name ) {
		global $the_webhook;

		if ( empty( $the_webhook ) || $the_webhook->id != $webhook->ID ) {
			$the_webhook = new WC_Webhook( $webhook->ID );
		}

		$output = '';

		switch ( $column_name ) {
			case 'title' :
				$edit_link        = get_edit_post_link( $the_webhook->id );
				$title            = _draft_or_post_title( $the_webhook->post_data );
				$post_type_object = get_post_type_object( $the_webhook->post_data->post_type );

				$output = '<strong><a href="' . esc_attr( $edit_link ) . '">' . esc_html( $title ) . '</a></strong>';

				// Get actions
				$actions = array();

				$actions['id'] = sprintf( __( 'ID: %d', 'woocommerce' ), $the_webhook->id );

				if ( current_user_can( $post_type_object->cap->edit_post, $the_webhook->id ) ) {
					$actions['edit'] = '<a href="' . admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=edit', $the_webhook->id ) ) . '">' . __( 'Edit', 'woocommerce' ) . '</a>';
				}

				if ( current_user_can( $post_type_object->cap->delete_post, $the_webhook->id ) ) {

					if ( 'trash' == $the_webhook->post_data->post_status ) {
						$actions['untrash'] = '<a title="' . esc_attr( __( 'Restore this item from the Trash', 'woocommerce' ) ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $the_webhook->id ) ), 'untrash-post_' . $the_webhook->id ) . '">' . __( 'Restore', 'woocommerce' ) . '</a>';
					} elseif ( EMPTY_TRASH_DAYS ) {
						$actions['trash'] = '<a class="submitdelete" title="' . esc_attr( __( 'Move this item to the Trash', 'woocommerce' ) ) . '" href="' . get_delete_post_link( $the_webhook->id ) . '">' . __( 'Trash', 'woocommerce' ) . '</a>';
					}

					if ( 'trash' == $the_webhook->post_data->post_status || ! EMPTY_TRASH_DAYS ) {
						$actions['delete'] = '<a class="submitdelete" title="' . esc_attr( __( 'Delete this item permanently', 'woocommerce' ) ) . '" href="' . get_delete_post_link( $the_webhook->id, '', true ) . '">' . __( 'Delete Permanently', 'woocommerce' ) . '</a>';
					}
				}

				$actions = apply_filters( 'post_row_actions', $actions, $the_webhook->post_data );

				$output .= '<div class="row-actions">';

				$i = 0;
				$action_count = sizeof( $actions );

				foreach ( $actions as $action => $link ) {
					++$i;
					$sep = ( $i == $action_count ) ? '' : ' | ';

					$output .= '<span class="' . $action . '">' . $link . $sep . '</span>';
				}

				$output .= '</div>';

				break;
			case 'status' :
				$output = $the_webhook->get_i18n_status();
				break;
			case 'topic' :
				$output = $the_webhook->get_topic();
				break;
			case 'delivery_url' :
				$output = $the_webhook->get_delivery_url();
				break;

			default :
				break;
		}

		return $output;
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		$per_page = 5;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();

		$webhooks = new WP_Query( array(
			'post_type'           => 'shop_webhook',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'paged'               => $current_page
		) );

		$this->items = $webhooks->posts;

		$this->set_pagination_args( array(
			'total_items' => $webhooks->found_posts,
			'per_page'    => $per_page,
			'total_pages' => $webhooks->max_num_pages
		) );
	}
}
