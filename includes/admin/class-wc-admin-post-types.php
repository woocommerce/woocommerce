<?php
/**
 * Post Types Admin
 *
 * @author   WooCommerce
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Admin_Post_Types', false ) ) :

/**
 * WC_Admin_Post_Types Class.
 *
 * Handles the edit posts views and some functionality on the edit post screen for WC post types.
 */
class WC_Admin_Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

		// Disable Auto Save
		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

		// Extra post data.
		add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );

		// WP List table columns. Defined here so they are always available for events such as inline editing.
		add_filter( 'manage_product_posts_columns', array( $this, 'product_columns' ) );
		add_filter( 'manage_shop_coupon_posts_columns', array( $this, 'shop_coupon_columns' ) );
		add_filter( 'manage_shop_order_posts_columns', array( $this, 'shop_order_columns' ) );

		add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_columns' ), 2 );
		add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'render_shop_coupon_columns' ), 2 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_shop_order_columns' ), 2 );

		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'product_sortable_columns' ) );
		add_filter( 'manage_edit-shop_coupon_sortable_columns', array( $this, 'shop_coupon_sortable_columns' ) );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'shop_order_sortable_columns' ) );

		add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 2, 100 );

		// Views
		add_filter( 'views_edit-product', array( $this, 'product_sorting_link' ) );

		// Bulk / quick edit
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );
		add_action( 'quick_edit_custom_box',  array( $this, 'quick_edit' ), 10, 2 );
		add_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ), 10, 2 );
		add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'shop_order_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_shop_order_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );

		// Order Search
		add_filter( 'get_search_query', array( $this, 'shop_order_search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'parse_query', array( $this, 'shop_order_search_custom_fields' ) );

		// Filters
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_filter( 'request', array( $this, 'request_query' ) );
		add_filter( 'parse_query', array( $this, 'product_filters_query' ) );
		add_filter( 'posts_search', array( $this, 'product_search' ) );

		// Edit post screens
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
		add_filter( 'default_hidden_meta_boxes', array( $this, 'hidden_meta_boxes' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'product_data_visibility' ) );

		// Uploads
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		add_action( 'media_upload_downloadable_product', array( $this, 'media_upload_downloadable_product' ) );

		if ( ! function_exists( 'duplicate_post_plugin_activation' ) ) {
			include( 'class-wc-admin-duplicate-product.php' );
		}

		include_once( dirname( __FILE__ ) . '/class-wc-admin-meta-boxes.php' );

		// Disable DFW feature pointer
		add_action( 'admin_footer', array( $this, 'disable_dfw_feature_pointer' ) );

		// Disable post type view mode options
		add_filter( 'view_mode_post_types', array( $this, 'disable_view_mode_options' ) );

		// Update the screen options.
		add_filter( 'default_hidden_columns', array( $this, 'adjust_shop_order_columns' ), 10, 2 );

		// Show blank state
		add_action( 'manage_posts_extra_tablenav', array( $this, 'maybe_render_blank_state' ) );

		// Hide template for CPT archive.
		add_filter( 'theme_page_templates', array( $this, 'hide_cpt_archive_templates' ), 10, 3 );
		add_action( 'edit_form_top', array( $this, 'show_cpt_archive_notice' ) );
	}

	/**
	 * Adjust shop order columns for the user on certain conditions.
	 */
	public function adjust_shop_order_columns( $hidden, $screen ) {
		if ( isset( $screen->id ) && 'edit-shop_order' === $screen->id ) {
			if ( 'disabled' === get_option( 'woocommerce_ship_to_countries' ) ) {
				$hidden[] = 'shipping_address';
			} else {
				$hidden[] = 'billing_address';
			}
		}
		return $hidden;
	}

	/**
	 * Change messages when a post type is updated.
	 * @param  array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['product'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Product updated. <a href="%s">View Product</a>', 'woocommerce' ), esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'woocommerce' ),
			3 => __( 'Custom field deleted.', 'woocommerce' ),
			4 => __( 'Product updated.', 'woocommerce' ),
			/* translators: %s: revision title */
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Product restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			/* translators: %s: product url */
			6 => sprintf( __( 'Product published. <a href="%s">View Product</a>', 'woocommerce' ), esc_url( get_permalink( $post_ID ) ) ),
			7 => __( 'Product saved.', 'woocommerce' ),
			/* translators: %s: product url */
			8 => sprintf( __( 'Product submitted. <a target="_blank" href="%s">Preview product</a>', 'woocommerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			/* translators: 1: date 2: product url */
			9 => sprintf( __( 'Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>', 'woocommerce' ),
			  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			/* translators: %s: product url */
			10 => sprintf( __( 'Product draft updated. <a target="_blank" href="%s">Preview product</a>', 'woocommerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		$messages['shop_order'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Order updated.', 'woocommerce' ),
			2 => __( 'Custom field updated.', 'woocommerce' ),
			3 => __( 'Custom field deleted.', 'woocommerce' ),
			4 => __( 'Order updated.', 'woocommerce' ),
			/* translators: %s: revision title */
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Order restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Order updated.', 'woocommerce' ),
			7 => __( 'Order saved.', 'woocommerce' ),
			8 => __( 'Order submitted.', 'woocommerce' ),
			/* translators: %s: date */
			9 => sprintf( __( 'Order scheduled for: <strong>%1$s</strong>.', 'woocommerce' ),
			  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Order draft updated.', 'woocommerce' ),
			11 => __( 'Order updated and email sent.', 'woocommerce' ),
		);

		$messages['shop_coupon'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Coupon updated.', 'woocommerce' ),
			2 => __( 'Custom field updated.', 'woocommerce' ),
			3 => __( 'Custom field deleted.', 'woocommerce' ),
			4 => __( 'Coupon updated.', 'woocommerce' ),
			/* translators: %s: revision title */
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Coupon restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Coupon updated.', 'woocommerce' ),
			7 => __( 'Coupon saved.', 'woocommerce' ),
			8 => __( 'Coupon submitted.', 'woocommerce' ),
			/* translators: %s: date */
			9 => sprintf( __( 'Coupon scheduled for: <strong>%1$s</strong>.', 'woocommerce' ),
			  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Coupon draft updated.', 'woocommerce' ),
		);

		return $messages;
	}

	/**
	 * Specify custom bulk actions messages for different post types.
	 * @param  array $bulk_messages
	 * @param  array $bulk_counts
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages['product'] = array(
			/* translators: %s: product count */
			'updated'   => _n( '%s product updated.', '%s products updated.', $bulk_counts['updated'], 'woocommerce' ),
			/* translators: %s: product count */
			'locked'    => _n( '%s product not updated, somebody is editing it.', '%s products not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce' ),
			/* translators: %s: product count */
			'deleted'   => _n( '%s product permanently deleted.', '%s products permanently deleted.', $bulk_counts['deleted'], 'woocommerce' ),
			/* translators: %s: product count */
			'trashed'   => _n( '%s product moved to the Trash.', '%s products moved to the Trash.', $bulk_counts['trashed'], 'woocommerce' ),
			/* translators: %s: product count */
			'untrashed' => _n( '%s product restored from the Trash.', '%s products restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce' ),
		);

		$bulk_messages['shop_order'] = array(
			/* translators: %s: order count */
			'updated'   => _n( '%s order updated.', '%s orders updated.', $bulk_counts['updated'], 'woocommerce' ),
			/* translators: %s: order count */
			'locked'    => _n( '%s order not updated, somebody is editing it.', '%s orders not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce' ),
			/* translators: %s: order count */
			'deleted'   => _n( '%s order permanently deleted.', '%s orders permanently deleted.', $bulk_counts['deleted'], 'woocommerce' ),
			/* translators: %s: order count */
			'trashed'   => _n( '%s order moved to the Trash.', '%s orders moved to the Trash.', $bulk_counts['trashed'], 'woocommerce' ),
			/* translators: %s: order count */
			'untrashed' => _n( '%s order restored from the Trash.', '%s orders restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce' ),
		);

		$bulk_messages['shop_coupon'] = array(
			/* translators: %s: coupon count */
			'updated'   => _n( '%s coupon updated.', '%s coupons updated.', $bulk_counts['updated'], 'woocommerce' ),
			/* translators: %s: coupon count */
			'locked'    => _n( '%s coupon not updated, somebody is editing it.', '%s coupons not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce' ),
			/* translators: %s: coupon count */
			'deleted'   => _n( '%s coupon permanently deleted.', '%s coupons permanently deleted.', $bulk_counts['deleted'], 'woocommerce' ),
			/* translators: %s: coupon count */
			'trashed'   => _n( '%s coupon moved to the Trash.', '%s coupons moved to the Trash.', $bulk_counts['trashed'], 'woocommerce' ),
			/* translators: %s: coupon count */
			'untrashed' => _n( '%s coupon restored from the Trash.', '%s coupons restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce' ),
		);

		return $bulk_messages;
	}

	/**
	 * Define custom columns for products.
	 * @param  array $existing_columns
	 * @return array
	 */
	public function product_columns( $existing_columns ) {
		if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
			$existing_columns = array();
		}

		unset( $existing_columns['title'], $existing_columns['comments'], $existing_columns['date'] );

		$columns          = array();
		$columns['cb']    = '<input type="checkbox" />';
		$columns['thumb'] = '<span class="wc-image tips" data-tip="' . esc_attr__( 'Image', 'woocommerce' ) . '">' . __( 'Image', 'woocommerce' ) . '</span>';
		$columns['name']  = __( 'Name', 'woocommerce' );

		if ( wc_product_sku_enabled() ) {
			$columns['sku'] = __( 'SKU', 'woocommerce' );
		}

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			$columns['is_in_stock'] = __( 'Stock', 'woocommerce' );
		}

		$columns['price']        = __( 'Price', 'woocommerce' );
		$columns['product_cat']  = __( 'Categories', 'woocommerce' );
		$columns['product_tag']  = __( 'Tags', 'woocommerce' );
		$columns['featured']     = '<span class="wc-featured parent-tips" data-tip="' . esc_attr__( 'Featured', 'woocommerce' ) . '">' . __( 'Featured', 'woocommerce' ) . '</span>';
		$columns['product_type'] = '<span class="wc-type parent-tips" data-tip="' . esc_attr__( 'Type', 'woocommerce' ) . '">' . __( 'Type', 'woocommerce' ) . '</span>';
		$columns['date']         = __( 'Date', 'woocommerce' );

		return array_merge( $columns, $existing_columns );

	}

	/**
	 * Define custom columns for coupons.
	 * @param  array $existing_columns
	 * @return array
	 */
	public function shop_coupon_columns( $existing_columns ) {
		$columns                = array();
		$columns['cb']          = $existing_columns['cb'];
		$columns['coupon_code'] = __( 'Code', 'woocommerce' );
		$columns['type']        = __( 'Coupon type', 'woocommerce' );
		$columns['amount']      = __( 'Coupon amount', 'woocommerce' );
		$columns['description'] = __( 'Description', 'woocommerce' );
		$columns['products']    = __( 'Product IDs', 'woocommerce' );
		$columns['usage']       = __( 'Usage / Limit', 'woocommerce' );
		$columns['expiry_date'] = __( 'Expiry date', 'woocommerce' );

		return $columns;
	}

	/**
	 * Define custom columns for orders.
	 * @param  array $existing_columns
	 * @return array
	 */
	public function shop_order_columns( $existing_columns ) {
		$columns                     = array();
		$columns['cb']               = $existing_columns['cb'];
		$columns['order_status']     = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'woocommerce' ) . '">' . esc_attr__( 'Status', 'woocommerce' ) . '</span>';
		$columns['order_title']      = __( 'Order', 'woocommerce' );
		$columns['billing_address']  = __( 'Billing', 'woocommerce' );
		$columns['shipping_address'] = __( 'Ship to', 'woocommerce' );
		$columns['customer_message'] = '<span class="notes_head tips" data-tip="' . esc_attr__( 'Customer message', 'woocommerce' ) . '">' . esc_attr__( 'Customer message', 'woocommerce' ) . '</span>';
		$columns['order_notes']      = '<span class="order-notes_head tips" data-tip="' . esc_attr__( 'Order notes', 'woocommerce' ) . '">' . esc_attr__( 'Order notes', 'woocommerce' ) . '</span>';
		$columns['order_date']       = __( 'Date', 'woocommerce' );
		$columns['order_total']      = __( 'Total', 'woocommerce' );
		$columns['order_actions']    = __( 'Actions', 'woocommerce' );

		return $columns;
	}

	/**
	 * Ouput custom columns for products.
	 *
	 * @param string $column
	 */
	public function render_product_columns( $column ) {
		global $post, $the_product;

		if ( empty( $the_product ) || $the_product->get_id() != $post->ID ) {
			$the_product = wc_get_product( $post );
		}

		// Only continue if we have a product.
		if ( empty( $the_product ) ) {
			return;
		}

		switch ( $column ) {
			case 'thumb' :
				echo '<a href="' . get_edit_post_link( $post->ID ) . '">' . $the_product->get_image( 'thumbnail' ) . '</a>';
				break;
			case 'name' :
				$edit_link = get_edit_post_link( $post->ID );
				$title     = _draft_or_post_title();

				echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';

				_post_states( $post );

				echo '</strong>';

				if ( $post->post_parent > 0 ) {
					echo '&nbsp;&nbsp;&larr; <a href="' . get_edit_post_link( $post->post_parent ) . '">' . get_the_title( $post->post_parent ) . '</a>';
				}

				// Excerpt view
				if ( isset( $_GET['mode'] ) && 'excerpt' == $_GET['mode'] ) {
					echo apply_filters( 'the_excerpt', $post->post_excerpt );
				}

				get_inline_data( $post );

				/* Custom inline data for woocommerce. */
				echo '
					<div class="hidden" id="woocommerce_inline_' . absint( $post->ID ) . '">
						<div class="menu_order">' . absint( $the_product->get_menu_order() ) . '</div>
						<div class="sku">' . esc_html( $the_product->get_sku() ) . '</div>
						<div class="regular_price">' . esc_html( $the_product->get_regular_price() ) . '</div>
						<div class="sale_price">' . esc_html( $the_product->get_sale_price() ) . '</div>
						<div class="weight">' . esc_html( $the_product->get_weight() ) . '</div>
						<div class="length">' . esc_html( $the_product->get_length() ) . '</div>
						<div class="width">' . esc_html( $the_product->get_width() ) . '</div>
						<div class="height">' . esc_html( $the_product->get_height() ) . '</div>
						<div class="shipping_class">' . esc_html( $the_product->get_shipping_class() ) . '</div>
						<div class="visibility">' . esc_html( $the_product->get_catalog_visibility() ) . '</div>
						<div class="stock_status">' . esc_html( $the_product->get_stock_status() ) . '</div>
						<div class="stock">' . esc_html( $the_product->get_stock_quantity() ) . '</div>
						<div class="manage_stock">' . esc_html( wc_bool_to_string( $the_product->get_manage_stock() ) ) . '</div>
						<div class="featured">' . esc_html( wc_bool_to_string( $the_product->get_featured() ) ) . '</div>
						<div class="product_type">' . esc_html( $the_product->get_type() ) . '</div>
						<div class="product_is_virtual">' . esc_html( wc_bool_to_string( $the_product->get_virtual() ) ) . '</div>
						<div class="tax_status">' . esc_html( $the_product->get_tax_status() ) . '</div>
						<div class="tax_class">' . esc_html( $the_product->get_tax_class() ) . '</div>
						<div class="backorders">' . esc_html( $the_product->get_backorders() ) . '</div>
					</div>
				';

			break;
			case 'sku' :
				echo $the_product->get_sku() ? esc_html( $the_product->get_sku() ) : '<span class="na">&ndash;</span>';
				break;
			case 'product_type' :
				if ( $the_product->is_type( 'grouped' ) ) {
					echo '<span class="product-type tips grouped" data-tip="' . esc_attr__( 'Grouped', 'woocommerce' ) . '"></span>';
				} elseif ( $the_product->is_type( 'external' ) ) {
					echo '<span class="product-type tips external" data-tip="' . esc_attr__( 'External/Affiliate', 'woocommerce' ) . '"></span>';
				} elseif ( $the_product->is_type( 'simple' ) ) {

					if ( $the_product->is_virtual() ) {
						echo '<span class="product-type tips virtual" data-tip="' . esc_attr__( 'Virtual', 'woocommerce' ) . '"></span>';
					} elseif ( $the_product->is_downloadable() ) {
						echo '<span class="product-type tips downloadable" data-tip="' . esc_attr__( 'Downloadable', 'woocommerce' ) . '"></span>';
					} else {
						echo '<span class="product-type tips simple" data-tip="' . esc_attr__( 'Simple', 'woocommerce' ) . '"></span>';
					}
				} elseif ( $the_product->is_type( 'variable' ) ) {
					echo '<span class="product-type tips variable" data-tip="' . esc_attr__( 'Variable', 'woocommerce' ) . '"></span>';
				} else {
					// Assuming that we have other types in future
					echo '<span class="product-type tips ' . esc_attr( sanitize_html_class( $the_product->get_type() ) ) . '" data-tip="' . esc_attr( ucfirst( $the_product->get_type() ) ) . '"></span>';
				}
				break;
			case 'price' :
				echo $the_product->get_price_html() ? $the_product->get_price_html() : '<span class="na">&ndash;</span>';
				break;
			case 'product_cat' :
			case 'product_tag' :
				if ( ! $terms = get_the_terms( $post->ID, $column ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					$termlist = array();
					foreach ( $terms as $term ) {
						$termlist[] = '<a href="' . admin_url( 'edit.php?' . $column . '=' . $term->slug . '&post_type=product' ) . ' ">' . $term->name . '</a>';
					}

					echo implode( ', ', $termlist );
				}
				break;
			case 'featured' :
				$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_feature_product&product_id=' . $post->ID ), 'woocommerce-feature-product' );
				echo '<a href="' . esc_url( $url ) . '" aria-label="' . __( 'Toggle featured', 'woocommerce' ) . '">';
				if ( $the_product->is_featured() ) {
					echo '<span class="wc-featured tips" data-tip="' . esc_attr__( 'Yes', 'woocommerce' ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				} else {
					echo '<span class="wc-featured not-featured tips" data-tip="' . esc_attr__( 'No', 'woocommerce' ) . '">' . __( 'No', 'woocommerce' ) . '</span>';
				}
				echo '</a>';
				break;
			case 'is_in_stock' :

				if ( $the_product->is_in_stock() ) {
					$stock_html = '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
				} else {
					$stock_html = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
				}

				if ( $the_product->managing_stock() ) {
					$stock_html .= ' (' . wc_stock_amount( $the_product->get_stock_quantity() ) . ')';
				}

				echo apply_filters( 'woocommerce_admin_stock_html', $stock_html, $the_product );

				break;
			default :
				break;
		}
	}

	/**
	 * Output custom columns for coupons.
	 *
	 * @param string $column
	 */
	public function render_shop_coupon_columns( $column ) {
		global $post, $the_coupon;

		if ( empty( $the_coupon ) || $the_coupon->get_id() !== $post->ID ) {
			$the_coupon = new WC_Coupon( $post->ID );
		}

		switch ( $column ) {
			case 'coupon_code' :
				$edit_link = get_edit_post_link( $post->ID );
				$title     = $the_coupon->get_code();

				echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
				_post_states( $post );
				echo '</strong>';
			break;
			case 'type' :
				echo esc_html( wc_get_coupon_type( $the_coupon->get_discount_type() ) );
			break;
			case 'amount' :
				echo esc_html( wc_format_localized_price( $the_coupon->get_amount() ) );
			break;
			case 'products' :
				$product_ids = $the_coupon->get_product_ids();

				if ( sizeof( $product_ids ) > 0 ) {
					echo esc_html( implode( ', ', $product_ids ) );
				} else {
					echo '&ndash;';
				}
			break;
			case 'usage_limit' :
				$usage_limit = $the_coupon->get_usage_limit();

				if ( $usage_limit ) {
					echo esc_html( $usage_limit );
				} else {
					echo '&ndash;';
				}
			break;
			case 'usage' :
				$usage_count = $the_coupon->get_usage_count();
				$usage_limit = $the_coupon->get_usage_limit();

				/* translators: 1: count 2: limit */
				printf(
					__( '%1$s / %2$s', 'woocommerce' ),
					esc_html( $usage_count ),
					esc_html( $usage_limit ? $usage_limit : '&infin;' )
				);
			break;
			case 'expiry_date' :
				$expiry_date = $the_coupon->get_date_expires();

				if ( $expiry_date ) {
					echo esc_html( $expiry_date->date_i18n( 'F j, Y' ) );
				} else {
					echo '&ndash;';
				}
			break;
			case 'description' :
				echo wp_kses_post( $the_coupon->get_description() ? $the_coupon->get_description() : '&ndash;' );
			break;
		}
	}

	/**
	 * Output custom columns for coupons.
	 * @param string $column
	 */
	public function render_shop_order_columns( $column ) {
		global $post, $the_order;

		if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		switch ( $column ) {
			case 'order_status' :
				printf( '<mark class="%s tips" data-tip="%s">%s</mark>', esc_attr( sanitize_html_class( $the_order->get_status() ) ), esc_attr( wc_get_order_status_name( $the_order->get_status() ) ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
			break;
			case 'order_date' :
				printf( '<time datetime="%s">%s</time>', esc_attr( $the_order->get_date_created()->date( 'c' ) ), esc_html( $the_order->get_date_created()->date_i18n( __( 'Y-m-d', 'woocommerce' ) ) ) );
			break;
			case 'customer_message' :
				if ( $the_order->get_customer_note() ) {
					echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $the_order->get_customer_note() ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}

			break;
			case 'billing_address' :

				if ( $address = $the_order->get_formatted_billing_address() ) {
					echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
				} else {
					echo '&ndash;';
				}

				if ( $the_order->get_billing_phone() ) {
					echo '<small class="meta">' . __( 'Phone:', 'woocommerce' ) . ' ' . esc_html( $the_order->get_billing_phone() ) . '</small>';
				}

			break;
			case 'shipping_address' :

				if ( $address = $the_order->get_formatted_shipping_address() ) {
					echo '<a target="_blank" href="' . esc_url( $the_order->get_shipping_address_map_url() ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
				} else {
					echo '&ndash;';
				}

				if ( $the_order->get_shipping_method() ) {
					echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
				}

			break;
			case 'order_notes' :

				if ( $post->comment_count ) {

					// check the status of the post
					$status = ( 'trash' !== $post->post_status ) ? '' : 'post-trashed';

					$latest_notes = get_comments( array(
						'post_id'   => $post->ID,
						'number'    => 1,
						'status'    => $status,
					) );

					$latest_note = current( $latest_notes );

					if ( isset( $latest_note->comment_content ) && 1 == $post->comment_count ) {
						echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					} elseif ( isset( $latest_note->comment_content ) ) {
						/* translators: %d: notes count */
						echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content . '<br/><small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), 'woocommerce' ), $post->comment_count - 1 ) . '</small>' ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					} else {
						/* translators: %d: notes count */
						echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $post->comment_count, 'woocommerce' ), $post->comment_count ) ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
					}
				} else {
					echo '<span class="na">&ndash;</span>';
				}

			break;
			case 'order_total' :
				echo $the_order->get_formatted_order_total();

				if ( $the_order->get_payment_method_title() ) {
					echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_payment_method_title() ) . '</small>';
				}
			break;
			case 'order_title' :

				if ( $the_order->get_customer_id() ) {
					$user     = get_user_by( 'id', $the_order->get_customer_id() );
					$username = '<a href="user-edit.php?user_id=' . absint( $the_order->get_customer_id() ) . '">';
					$username .= esc_html( ucwords( $user->display_name ) );
					$username .= '</a>';
				} elseif ( $the_order->get_billing_first_name() || $the_order->get_billing_last_name() ) {
					/* translators: 1: first name 2: last name */
					$username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->get_billing_first_name(), $the_order->get_billing_last_name() ) );
				} elseif ( $the_order->get_billing_company() ) {
					$username = trim( $the_order->get_billing_company() );
				} else {
					$username = __( 'Guest', 'woocommerce' );
				}

				/* translators: 1: order and number (i.e. Order #13) 2: user name */
				printf(
					__( '%1$s by %2$s', 'woocommerce' ),
					'<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $the_order->get_order_number() ) . '</strong></a>',
					$username
				);

				if ( $the_order->get_billing_email() ) {
					echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->get_billing_email() ) . '">' . esc_html( $the_order->get_billing_email() ) . '</a></small>';
				}

				echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'woocommerce' ) . '</span></button>';

			break;
			case 'order_actions' :

				?><p>
					<?php
						do_action( 'woocommerce_admin_order_actions_start', $the_order );

						$actions = array();

						if ( $the_order->has_status( array( 'pending', 'on-hold' ) ) ) {
							$actions['processing'] = array(
								'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
								'name'      => __( 'Processing', 'woocommerce' ),
								'action'    => "processing",
							);
						}

						if ( $the_order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
							$actions['complete'] = array(
								'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
								'name'      => __( 'Complete', 'woocommerce' ),
								'action'    => "complete",
							);
						}

						$actions['view'] = array(
							'url'       => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
							'name'      => __( 'View', 'woocommerce' ),
							'action'    => "view",
						);

						$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $the_order );

						foreach ( $actions as $action ) {
							printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						}

						do_action( 'woocommerce_admin_order_actions_end', $the_order );
					?>
				</p><?php

			break;
		}
	}

	/**
	 * Make columns sortable - https://gist.github.com/906872.
	 *
	 * @param  array $columns
	 * @return array
	 */
	public function product_sortable_columns( $columns ) {
		$custom = array(
			'price'    => 'price',
			'sku'      => 'sku',
			'name'     => 'title',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Make columns sortable - https://gist.github.com/906872.
	 *
	 * @param  array $columns
	 * @return array
	 */
	public function shop_coupon_sortable_columns( $columns ) {
		return $columns;
	}

	/**
	 * Make columns sortable - https://gist.github.com/906872.
	 *
	 * @param  array $columns
	 * @return array
	 */
	public function shop_order_sortable_columns( $columns ) {
		$custom = array(
			'order_title' => 'ID',
			'order_total' => 'order_total',
			'order_date'  => 'date',
		);
		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Set list table primary column for products and orders.
	 * Support for WordPress 4.3.
	 *
	 * @param  string $default
	 * @param  string $screen_id
	 *
	 * @return string
	 */
	public function list_table_primary_column( $default, $screen_id ) {

		if ( 'edit-product' === $screen_id ) {
			return 'name';
		}

		if ( 'edit-shop_order' === $screen_id ) {
			return 'order_title';
		}

		if ( 'edit-shop_coupon' === $screen_id ) {
			return 'coupon_code';
		}

		return $default;
	}

	/**
	 * Set row actions for products and orders.
	 *
	 * @param  array $actions
	 * @param  WP_Post $post
	 *
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( 'product' === $post->post_type ) {
			return array_merge( array( 'id' => 'ID: ' . $post->ID ), $actions );
		}

		if ( in_array( $post->post_type, array( 'shop_order', 'shop_coupon' ) ) ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
		}

		return $actions;
	}

	/**
	 * Product sorting link.
	 *
	 * Based on Simple Page Ordering by 10up (https://wordpress.org/plugins/simple-page-ordering/).
	 *
	 * @param  array $views
	 * @return array
	 */
	public function product_sorting_link( $views ) {
		global $post_type, $wp_query;

		if ( ! current_user_can( 'edit_others_pages' ) ) {
			return $views;
		}

		$class            = ( isset( $wp_query->query['orderby'] ) && 'menu_order title' === $wp_query->query['orderby'] ) ? 'current' : '';
		$query_string     = remove_query_arg( array( 'orderby', 'order' ) );
		$query_string     = add_query_arg( 'orderby', urlencode( 'menu_order title' ), $query_string );
		$query_string     = add_query_arg( 'order', urlencode( 'ASC' ), $query_string );
		$views['byorder'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Sort products', 'woocommerce' ) . '</a>';

		return $views;
	}

	/**
	 * Custom bulk edit - form.
	 *
	 * @param mixed $column_name
	 * @param mixed $post_type
	 */
	public function bulk_edit( $column_name, $post_type ) {

		if ( 'price' != $column_name || 'product' != $post_type ) {
			return;
		}

		$shipping_class = get_terms( 'product_shipping_class', array(
			'hide_empty' => false,
		) );

		include( WC()->plugin_path() . '/includes/admin/views/html-bulk-edit-product.php' );
	}

	/**
	 * Custom quick edit - form.
	 *
	 * @param mixed $column_name
	 * @param mixed $post_type
	 */
	public function quick_edit( $column_name, $post_type ) {

		if ( 'price' != $column_name || 'product' != $post_type ) {
			return;
		}

		$shipping_class = get_terms( 'product_shipping_class', array(
			'hide_empty' => false,
		) );

		include( WC()->plugin_path() . '/includes/admin/views/html-quick-edit-product.php' );
	}

	/**
	 * Offers a way to hook into save post without causing an infinite loop
	 * when quick/bulk saving product info.
	 *
	 * @since 3.0.0
	 * @param int    $post_id
	 * @param object $post
	 */
	public function bulk_and_quick_edit_hook( $post_id, $post ) {
		remove_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ) );
		do_action( 'woocommerce_product_bulk_and_quick_edit', $post_id, $post );
		add_action( 'save_post', array( $this, 'bulk_and_quick_edit_hook' ), 10, 2 );
	}

	/**
	 * Quick and bulk edit saving.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @return int
	 */
	public function bulk_and_quick_edit_save_post( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' != $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check nonces
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) && ! isset( $_REQUEST['woocommerce_bulk_edit_nonce'] ) ) {
			return $post_id;
		}
		if ( isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) && ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) {
			return $post_id;
		}
		if ( isset( $_REQUEST['woocommerce_bulk_edit_nonce'] ) && ! wp_verify_nonce( $_REQUEST['woocommerce_bulk_edit_nonce'], 'woocommerce_bulk_edit_nonce' ) ) {
			return $post_id;
		}

		// Get the product and save
		$product = wc_get_product( $post );

		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) {
			$this->quick_edit_save( $post_id, $product );
		} else {
			$this->bulk_edit_save( $post_id, $product );
		}

		return $post_id;
	}

	/**
	 * Quick edit.
	 *
	 * @param integer    $post_id
	 * @param WC_Product $product
	 */
	private function quick_edit_save( $post_id, $product ) {
		$data_store        = $product->get_data_store();
		$old_regular_price = $product->get_regular_price();
		$old_sale_price    = $product->get_sale_price();

		// Save fields
		if ( isset( $_REQUEST['_sku'] ) ) {
			$sku     = $product->get_sku();
			$new_sku = (string) wc_clean( $_REQUEST['_sku'] );

			if ( $new_sku !== $sku ) {
				if ( ! empty( $new_sku ) ) {
					$unique_sku = wc_product_has_unique_sku( $post_id, $new_sku );
					if ( $unique_sku ) {
						$product->set_sku( $new_sku );
					}
				} else {
					$product->set_sku( '' );
				}
			}
		}

		if ( isset( $_REQUEST['_weight'] ) ) {
			$product->set_weight( wc_clean( $_REQUEST['_weight'] ) );
		}

		if ( isset( $_REQUEST['_length'] ) ) {
			$product->set_length( wc_clean( $_REQUEST['_length'] ) );
		}

		if ( isset( $_REQUEST['_width'] ) ) {
			$product->set_width( wc_clean( $_REQUEST['_width'] ) );
		}

		if ( isset( $_REQUEST['_height'] ) ) {
			$product->set_height( wc_clean( $_REQUEST['_height'] ) );
		}

		if ( ! empty( $_REQUEST['_shipping_class'] ) ) {
			if ( '_no_shipping_class' === $_REQUEST['_shipping_class'] ) {
				$product->set_shipping_class_id( 0 );
			} else {
				$shipping_class_id = $data_store->get_shipping_class_id_by_slug( wc_clean( $_REQUEST['_shipping_class'] ) );
				$product->set_shipping_class_id( $shipping_class_id );
			}
		}

		if ( isset( $_REQUEST['_visibility'] ) ) {
			$product->set_catalog_visibility( wc_clean( $_REQUEST['_visibility'] ) );
		}

		if ( isset( $_REQUEST['_featured'] ) ) {
			$product->set_featured( true );
		} else {
			$product->set_featured( false );
		}

		if ( isset( $_REQUEST['_tax_status'] ) ) {
			$product->set_tax_status( wc_clean( $_REQUEST['_tax_status'] ) );
		}

		if ( isset( $_REQUEST['_tax_class'] ) ) {
			$product->set_tax_class( wc_clean( $_REQUEST['_tax_class'] ) );
		}

		if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {

			if ( isset( $_REQUEST['_regular_price'] ) ) {
				$new_regular_price = ( '' === $_REQUEST['_regular_price'] ) ? '' : wc_format_decimal( $_REQUEST['_regular_price'] );
				$product->set_regular_price( $new_regular_price );
			} else {
				$new_regular_price = null;
			}
			if ( isset( $_REQUEST['_sale_price'] ) ) {
				$new_sale_price = ( '' === $_REQUEST['_sale_price'] ) ? '' : wc_format_decimal( $_REQUEST['_sale_price'] );
				$product->set_sale_price( $new_sale_price );
			} else {
				$new_sale_price = null;
			}

			// Handle price - remove dates and set to lowest
			$price_changed = false;

			if ( ! is_null( $new_regular_price ) && $new_regular_price != $old_regular_price ) {
				$price_changed = true;
			} elseif ( ! is_null( $new_sale_price ) && $new_sale_price != $old_sale_price ) {
				$price_changed = true;
			}

			if ( $price_changed ) {
				$product->set_date_on_sale_to( '' );
				$product->set_date_on_sale_from( '' );
			}
		}

		// Handle Stock Data
		$manage_stock = ! empty( $_REQUEST['_manage_stock'] ) && 'grouped' !== $product->get_type() ? 'yes' : 'no';
		$backorders   = ! empty( $_REQUEST['_backorders'] ) ? wc_clean( $_REQUEST['_backorders'] ) : 'no';
		$stock_status = ! empty( $_REQUEST['_stock_status'] ) ? wc_clean( $_REQUEST['_stock_status'] ) : 'instock';
		$stock_amount = 'yes' === $manage_stock ? wc_stock_amount( $_REQUEST['_stock'] ) : '';

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {

			// Apply product type constraints to stock status
			if ( $product->is_type( 'external' ) ) {
				// External always in stock
				$stock_status = 'instock';
			} elseif ( $product->is_type( 'variable' ) ) {
				// Stock status is always determined by children
				foreach ( $product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					if ( ! $product->get_manage_stock() ) {
						$child->set_stock_status( $stock_status );
						$child->save();
					}
				}

				$product = WC_Product_Variable::sync( $product, false );
			}

			$product->set_manage_stock( $manage_stock );
			$product->set_backorders( $backorders );
			$product->save();

			if ( ! $product->is_type( 'variable' ) ) {
				wc_update_product_stock_status( $post_id, $stock_status );
			}

			wc_update_product_stock( $post_id, $stock_amount );

		} else {
			$product->save();
			wc_update_product_stock_status( $post_id, $stock_status );
		}

		do_action( 'woocommerce_product_quick_edit_save', $product );
	}

	/**
	 * Bulk edit.
	 *
	 * @param integer $post_id
	 * @param WC_Product $product
	 */
	public function bulk_edit_save( $post_id, $product ) {
		$data_store        = $product->get_data_store();
		$old_regular_price = $product->get_regular_price();
		$old_sale_price    = $product->get_sale_price();

		// Save fields
		if ( ! empty( $_REQUEST['change_weight'] ) && isset( $_REQUEST['_weight'] ) ) {
			$product->set_weight( wc_clean( stripslashes( $_REQUEST['_weight'] ) ) );
		}

		if ( ! empty( $_REQUEST['change_dimensions'] ) ) {
			if ( isset( $_REQUEST['_length'] ) ) {
				$product->set_length( wc_clean( stripslashes( $_REQUEST['_length'] ) ) );
			}
			if ( isset( $_REQUEST['_width'] ) ) {
				$product->set_width( wc_clean( stripslashes( $_REQUEST['_width'] ) ) );
			}
			if ( isset( $_REQUEST['_height'] ) ) {
				$product->set_height( wc_clean( stripslashes( $_REQUEST['_height'] ) ) );
			}
		}

		if ( ! empty( $_REQUEST['_tax_status'] ) ) {
			$product->set_tax_status( wc_clean( $_REQUEST['_tax_status'] ) );
		}

		if ( ! empty( $_REQUEST['_tax_class'] ) ) {
			$tax_class = wc_clean( $_REQUEST['_tax_class'] );
			if ( 'standard' == $tax_class ) {
				$tax_class = '';
			}
			$product->set_tax_class( $tax_class );
		}

		if ( ! empty( $_REQUEST['_shipping_class'] ) ) {
			if ( '_no_shipping_class' === $_REQUEST['_shipping_class'] ) {
				$product->set_shipping_class_id( 0 );
			} else {
				$shipping_class_id = $data_store->get_shipping_class_id_by_slug( wc_clean( $_REQUEST['_shipping_class'] ) );
				$product->set_shipping_class_id( $shipping_class_id );
			}
		}

		if ( ! empty( $_REQUEST['_visibility'] ) ) {
			$product->set_catalog_visibility( wc_clean( $_REQUEST['_visibility'] ) );
		}

		if ( ! empty( $_REQUEST['_featured'] ) ) {
			$product->set_featured( stripslashes( $_REQUEST['_featured'] ) );
		}

		// Sold Individually
		if ( ! empty( $_REQUEST['_sold_individually'] ) ) {
			if ( 'yes' === $_REQUEST['_sold_individually'] ) {
				$product->set_sold_individually( 'yes' );
			} else {
				$product->set_sold_individually( '' );
			}
		}

		// Handle price - remove dates and set to lowest
		$change_price_product_types = apply_filters( 'woocommerce_bulk_edit_save_price_product_types', array( 'simple', 'external' ) );
		$can_product_type_change_price = false;
		foreach ( $change_price_product_types as $product_type ) {
			if ( $product->is_type( $product_type ) ) {
				$can_product_type_change_price = true;
				break;
			}
		}

		if ( $can_product_type_change_price ) {

			$price_changed = false;

			if ( ! empty( $_REQUEST['change_regular_price'] ) ) {
				$change_regular_price = absint( $_REQUEST['change_regular_price'] );
				$regular_price = esc_attr( stripslashes( $_REQUEST['_regular_price'] ) );

				switch ( $change_regular_price ) {
					case 1 :
						$new_price = $regular_price;
						break;
					case 2 :
						if ( strstr( $regular_price, '%' ) ) {
							$percent = str_replace( '%', '', $regular_price ) / 100;
							$new_price = $old_regular_price + ( round( $old_regular_price * $percent, wc_get_price_decimals() ) );
						} else {
							$new_price = $old_regular_price + $regular_price;
						}
						break;
					case 3 :
						if ( strstr( $regular_price, '%' ) ) {
							$percent = str_replace( '%', '', $regular_price ) / 100;
							$new_price = max( 0, $old_regular_price - ( round( $old_regular_price * $percent, wc_get_price_decimals() ) ) );
						} else {
							$new_price = max( 0, $old_regular_price - $regular_price );
						}
						break;

					default :
						break;
				}

				if ( isset( $new_price ) && $new_price != $old_regular_price ) {
					$price_changed = true;
					$new_price = round( $new_price, wc_get_price_decimals() );
					$product->set_regular_price( $new_price );
				}
			}

			if ( ! empty( $_REQUEST['change_sale_price'] ) ) {
				$change_sale_price = absint( $_REQUEST['change_sale_price'] );
				$sale_price        = esc_attr( stripslashes( $_REQUEST['_sale_price'] ) );

				switch ( $change_sale_price ) {
					case 1 :
						$new_price = $sale_price;
						break;
					case 2 :
						if ( strstr( $sale_price, '%' ) ) {
							$percent = str_replace( '%', '', $sale_price ) / 100;
							$new_price = $old_sale_price + ( $old_sale_price * $percent );
						} else {
							$new_price = $old_sale_price + $sale_price;
						}
						break;
					case 3 :
						if ( strstr( $sale_price, '%' ) ) {
							$percent = str_replace( '%', '', $sale_price ) / 100;
							$new_price = max( 0, $old_sale_price - ( $old_sale_price * $percent ) );
						} else {
							$new_price = max( 0, $old_sale_price - $sale_price );
						}
						break;
					case 4 :
						if ( strstr( $sale_price, '%' ) ) {
							$percent = str_replace( '%', '', $sale_price ) / 100;
							$new_price = max( 0, $product->regular_price - ( $product->regular_price * $percent ) );
						} else {
							$new_price = max( 0, $product->regular_price - $sale_price );
						}
						break;

					default :
						break;
				}

				if ( isset( $new_price ) && $new_price != $old_sale_price ) {
					$price_changed = true;
					$new_price = ! empty( $new_price ) || '0' === $new_price ? round( $new_price, wc_get_price_decimals() ) : '';
					$product->set_sale_price( $new_price );
				}
			}

			if ( $price_changed ) {
				$product->set_date_on_sale_to( '' );
				$product->set_date_on_sale_from( '' );

				if ( $product->get_regular_price() < $product->get_sale_price() ) {
					$product->set_sale_price( '' );
				}
			}
		}

		// Handle Stock Data
		$was_managing_stock = $product->get_manage_stock() ? 'yes' : 'no';
		$stock_status       = $product->get_stock_status();
		$backorders         = $product->get_backorders();

		$backorders   = ! empty( $_REQUEST['_backorders'] ) ? wc_clean( $_REQUEST['_backorders'] ) : $backorders;
		$stock_status = ! empty( $_REQUEST['_stock_status'] ) ? wc_clean( $_REQUEST['_stock_status'] ) : $stock_status;

		if ( ! empty( $_REQUEST['_manage_stock'] ) ) {
			$manage_stock = 'yes' === wc_clean( $_REQUEST['_manage_stock'] ) && 'grouped' !== $product->product_type ? 'yes' : 'no';
		} else {
			$manage_stock = $was_managing_stock;
		}

		$stock_amount = 'yes' === $manage_stock && ! empty( $_REQUEST['change_stock'] ) ? wc_stock_amount( $_REQUEST['_stock'] ) : $product->get_stock_quantity();

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {

			// Apply product type constraints to stock status
			if ( $product->is_type( 'external' ) ) {
				// External always in stock
				$stock_status = 'instock';
			} elseif ( $product->is_type( 'variable' ) ) {
				// Stock status is always determined by children
				foreach ( $product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					if ( ! $product->get_manage_stock() ) {
						$child->set_stock_status( $stock_status );
						$child->save();
					}
				}

				$product = WC_Product_Variable::sync( $product, false );
			}

			$product->set_manage_stock( $manage_stock );
			$product->set_backorders( $backorders );
			$product->save();

			if ( ! $product->is_type( 'variable' ) ) {
				wc_update_product_stock_status( $post_id, $stock_status );
			}

			wc_update_product_stock( $post_id, $stock_amount );

		} else {
			$product->save();
			wc_update_product_stock_status( $post_id, $stock_status );
		}

		do_action( 'woocommerce_product_bulk_edit_save', $product );
	}

	/**
	 * Manipulate shop order bulk actions.
	 *
	 * @param  array $actions List of actions.
	 * @return array
	 */
	public function shop_order_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		$actions['mark_processing'] = __( 'Mark processing', 'woocommerce' );
		$actions['mark_on-hold']    = __( 'Mark on-hold', 'woocommerce' );
		$actions['mark_completed']  = __( 'Mark complete', 'woocommerce' );

		return $actions;
	}

	/**
	 * Handle shop order bulk actions.
	 *
	 * @since  3.0.0
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_shop_order_bulk_actions( $redirect_to, $action, $ids ) {
		// Bail out if this is not a status-changing action.
		if ( false === strpos( $action, 'mark_' ) ) {
			return $redirect_to;
		}

		$order_statuses = wc_get_order_statuses();
		$new_status     = substr( $action, 5 ); // Get the status name from action.
		$report_action  = 'marked_' . $new_status;

		// Sanity check: bail out if this is actually not a status, or is
		// not a registered status.
		if ( ! isset( $order_statuses[ 'wc-' . $new_status ] ) ) {
			return $redirect_to;
		}

		$changed = 0;
		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );
			$order->update_status( $new_status, __( 'Order status changed by bulk edit:', 'woocommerce' ), true );
			do_action( 'woocommerce_order_edit_status', $id, $new_status );
			$changed++;
		}

		$redirect_to = add_query_arg( array(
			'post_type'    => 'shop_order',
			$report_action => true,
			'changed'      => $changed,
			'ids'          => join( ',', $ids ),
		), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) {
			return;
		}

		$order_statuses = wc_get_order_statuses();

		// Check if any status changes happened
		foreach ( $order_statuses as $slug => $name ) {

			if ( isset( $_REQUEST[ 'marked_' . str_replace( 'wc-', '', $slug ) ] ) ) {

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
				/* translators: %s: orders count */
				$message = sprintf( _n( 'Order status changed.', '%s order statuses changed.', $number, 'woocommerce' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . $message . '</p></div>';

				break;
			}
		}
	}

	/**
	 * Search custom fields as well as content.
	 * @param WP_Query $wp
	 */
	public function shop_order_search_custom_fields( $wp ) {
		global $pagenow;

		if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || 'shop_order' !== $wp->query_vars['post_type'] ) {
			return;
		}

		$post_ids = wc_order_search( $_GET['s'] );

		if ( ! empty( $post_ids ) ) {
			// Remove "s" - we don't want to search order name.
			unset( $wp->query_vars['s'] );

			// so we know we're doing this.
			$wp->query_vars['shop_order_search'] = true;

			// Search by found posts.
			$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
		}
	}

	/**
	 * Change the label when searching orders.
	 *
	 * @param mixed $query
	 * @return string
	 */
	public function shop_order_search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow ) {
			return $query;
		}

		if ( 'shop_order' !== $typenow ) {
			return $query;
		}

		if ( ! get_query_var( 'shop_order_search' ) ) {
			return $query;
		}

		return wp_unslash( $_GET['s'] );
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'sku';
		$public_query_vars[] = 'shop_order_search';

		return $public_query_vars;
	}

	/**
	 * Filters for post types.
	 */
	public function restrict_manage_posts() {
		global $typenow;

		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) {
			$this->shop_order_filters();
		} elseif ( 'product' == $typenow ) {
			$this->product_filters();
		} elseif ( 'shop_coupon' == $typenow ) {
			$this->shop_coupon_filters();
		}
	}

	/**
	 * Show a category filter box.
	 */
	public function product_filters() {
		global $wp_query;

		// Category Filtering
		wc_product_dropdown_categories();

		// Type filtering
		$terms   = get_terms( 'product_type' );
		$output  = '<select name="product_type" id="dropdown_product_type">';
		$output .= '<option value="">' . __( 'Show all product types', 'woocommerce' ) . '</option>';

		foreach ( $terms as $term ) {
			$output .= '<option value="' . sanitize_title( $term->name ) . '" ';

			if ( isset( $wp_query->query['product_type'] ) ) {
				$output .= selected( $term->slug, $wp_query->query['product_type'], false );
			}

			$output .= '>';

			switch ( $term->name ) {
				case 'grouped' :
					$output .= __( 'Grouped product', 'woocommerce' );
					break;
				case 'external' :
					$output .= __( 'External/Affiliate product', 'woocommerce' );
					break;
				case 'variable' :
					$output .= __( 'Variable product', 'woocommerce' );
					break;
				case 'simple' :
					$output .= __( 'Simple product', 'woocommerce' );
					break;
				default :
					// Assuming that we have other types in future
					$output .= ucfirst( $term->name );
					break;
			}

			$output .= '</option>';

			if ( 'simple' == $term->name ) {

				$output .= '<option value="downloadable" ';

				if ( isset( $wp_query->query['product_type'] ) ) {
					$output .= selected( 'downloadable', $wp_query->query['product_type'], false );
				}

				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __( 'Downloadable', 'woocommerce' ) . '</option>';

				$output .= '<option value="virtual" ';

				if ( isset( $wp_query->query['product_type'] ) ) {
					$output .= selected( 'virtual', $wp_query->query['product_type'], false );
				}

				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __( 'Virtual', 'woocommerce' ) . '</option>';
			}
		}

		$output .= '</select>';

		echo apply_filters( 'woocommerce_product_filters', $output );
	}

	/**
	 * Show custom filters to filter coupons by type.
	 */
	public function shop_coupon_filters() {
		?>
		<select name="coupon_type" id="dropdown_shop_coupon_type">
			<option value=""><?php _e( 'Show all types', 'woocommerce' ); ?></option>
			<?php
				$types = wc_get_coupon_types();

				foreach ( $types as $name => $type ) {
					echo '<option value="' . esc_attr( $name ) . '"';

					if ( isset( $_GET['coupon_type'] ) ) {
						selected( $name, $_GET['coupon_type'] );
					}

					echo '>' . esc_html( $type ) . '</option>';
				}
			?>
		</select>
		<?php
	}

	/**
	 * Show custom filters to filter orders by status/customer.
	 */
	public function shop_order_filters() {
		$user_string = '';
		$user_id     = '';
		if ( ! empty( $_GET['_customer_user'] ) ) {
			$user_id     = absint( $_GET['_customer_user'] );
			$user        = get_user_by( 'id', $user_id );
			/* translators: 1: user display name 2: user ID 3: user email */
			$user_string = sprintf(
				esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
				$user->display_name,
				absint( $user->ID ),
				$user->user_email
			);
		}
		?>
		<select class="wc-customer-search" name="_customer_user" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'woocommerce' ); ?>" data-allow_clear="true">
			<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo htmlspecialchars( $user_string ); ?><option>
		</select>
		<?php
	}

	/**
	 * Filters and sorting handler.
	 *
	 * @param  array $vars
	 * @return array
	 */
	public function request_query( $vars ) {
		global $typenow, $wp_query, $wp_post_statuses;

		if ( 'product' === $typenow ) {
			// Sorting
			if ( isset( $vars['orderby'] ) ) {
				if ( 'price' == $vars['orderby'] ) {
					$vars = array_merge( $vars, array(
						'meta_key'  => '_price',
						'orderby'   => 'meta_value_num',
					) );
				}
				if ( 'sku' == $vars['orderby'] ) {
					$vars = array_merge( $vars, array(
						'meta_key'  => '_sku',
						'orderby'   => 'meta_value',
					) );
				}
			}
		} elseif ( 'shop_coupon' === $typenow ) {

			if ( ! empty( $_GET['coupon_type'] ) ) {
				$vars['meta_key']   = 'discount_type';
				$vars['meta_value'] = wc_clean( $_GET['coupon_type'] );
			}
		} elseif ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) {

			// Filter the orders by the posted customer.
			if ( isset( $_GET['_customer_user'] ) && $_GET['_customer_user'] > 0 ) {
				$vars['meta_query'] = array(
					array(
						'key'   => '_customer_user',
						'value' => (int) $_GET['_customer_user'],
						'compare' => '=',
					),
				);
			}

			// Sorting
			if ( isset( $vars['orderby'] ) ) {
				if ( 'order_total' == $vars['orderby'] ) {
					$vars = array_merge( $vars, array(
						'meta_key'  => '_order_total',
						'orderby'   => 'meta_value_num',
					) );
				}
			}

			// Status
			if ( ! isset( $vars['post_status'] ) ) {
				$post_statuses = wc_get_order_statuses();

				foreach ( $post_statuses as $status => $value ) {
					if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
						unset( $post_statuses[ $status ] );
					}
				}

				$vars['post_status'] = array_keys( $post_statuses );
			}
		}

		return $vars;
	}

	/**
	 * Filter the products in admin based on options.
	 *
	 * @param mixed $query
	 */
	public function product_filters_query( $query ) {
		global $typenow, $wp_query;

		if ( 'product' == $typenow ) {

			if ( isset( $query->query_vars['product_type'] ) ) {
				// Subtypes
				if ( 'downloadable' == $query->query_vars['product_type'] ) {
					$query->query_vars['product_type']  = '';
					$query->is_tax = false;
					$query->query_vars['meta_value']    = 'yes';
					$query->query_vars['meta_key']      = '_downloadable';
				} elseif ( 'virtual' == $query->query_vars['product_type'] ) {
					$query->query_vars['product_type']  = '';
					$query->is_tax = false;
					$query->query_vars['meta_value']    = 'yes';
					$query->query_vars['meta_key']      = '_virtual';
				}
			}

			// Categories
			if ( isset( $_GET['product_cat'] ) && '0' === $_GET['product_cat'] ) {
				$query->query_vars['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'id',
					'terms'    => get_terms( 'product_cat', array( 'fields' => 'ids' ) ),
					'operator' => 'NOT IN',
				);
			}

			// Shipping classes
			if ( isset( $_GET['product_shipping_class'] ) && '0' === $_GET['product_shipping_class'] ) {
				$query->query_vars['tax_query'][] = array(
					'taxonomy' => 'product_shipping_class',
					'field'    => 'id',
					'terms'    => get_terms( 'product_shipping_class', array( 'fields' => 'ids' ) ),
					'operator' => 'NOT IN',
				);
			}
		}
	}

	/**
	 * Search by SKU or ID for products.
	 *
	 * @param string $where
	 * @return string
	 */
	public function product_search( $where ) {
		global $pagenow, $wpdb, $wp;

		if ( 'edit.php' != $pagenow || ! is_search() || ! isset( $wp->query_vars['s'] ) || 'product' != $wp->query_vars['post_type'] ) {
			return $where;
		}

		$search_ids = array();
		$terms      = explode( ',', $wp->query_vars['s'] );

		foreach ( $terms as $term ) {
			if ( is_numeric( $term ) ) {
				$search_ids[] = $term;
			}

			// Attempt to get a SKU
			$sku_to_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_parent FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE meta_key='_sku' AND meta_value LIKE %s;", '%' . $wpdb->esc_like( wc_clean( $term ) ) . '%' ) );
			$sku_to_id = array_merge( wp_list_pluck( $sku_to_id, 'ID' ), wp_list_pluck( $sku_to_id, 'post_parent' ) );

			if ( sizeof( $sku_to_id ) > 0 ) {
				$search_ids = array_merge( $search_ids, $sku_to_id );
			}
		}

		$search_ids = array_filter( array_unique( array_map( 'absint', $search_ids ) ) );

		if ( sizeof( $search_ids ) > 0 ) {
			$where = str_replace( 'AND (((', "AND ( ({$wpdb->posts}.ID IN (" . implode( ',', $search_ids ) . ")) OR ((", $where );
		}

		return $where;
	}

	/**
	 * Disable the auto-save functionality for Orders.
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), wc_get_order_types( 'order-meta-boxes' ) ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Output extra data on post forms.
	 * @param  WP_Post $post
	 */
	public function edit_form_top( $post ) {
		echo '<input type="hidden" id="original_post_title" name="original_post_title" value="' . esc_attr( $post->post_title ) . '" />';
	}

	/**
	 * Change title boxes in admin.
	 * @param  string $text
	 * @param  object $post
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		switch ( $post->post_type ) {
			case 'product' :
				$text = __( 'Product name', 'woocommerce' );
			break;
			case 'shop_coupon' :
				$text = __( 'Coupon code', 'woocommerce' );
			break;
		}

		return $text;
	}

	/**
	 * Print coupon description textarea field.
	 * @param WP_Post $post
	 */
	public function edit_form_after_title( $post ) {
		if ( 'shop_coupon' === $post->post_type ) {
			?>
			<textarea id="woocommerce-coupon-description" name="excerpt" cols="5" rows="2" placeholder="<?php esc_attr_e( 'Description (optional)', 'woocommerce' ); ?>"><?php echo $post->post_excerpt; // This is already escaped in core ?></textarea>
			<?php
		}
	}

	/**
	 * Hidden default Meta-Boxes.
	 * @param  array  $hidden
	 * @param  object $screen
	 * @return array
	 */
	public function hidden_meta_boxes( $hidden, $screen ) {
		if ( 'product' === $screen->post_type && 'post' === $screen->base ) {
			$hidden = array_merge( $hidden, array( 'postcustom' ) );
		}

		return $hidden;
	}

	/**
	 * Output product visibility options.
	 */
	public function product_data_visibility() {
		global $post, $thepostid, $product_object;

		if ( 'product' !== $post->post_type ) {
			return;
		}

		$thepostid          = $post->ID;
		$product_object     = $thepostid ? wc_get_product( $thepostid ) : new WC_Product;
		$current_visibility = $product_object->get_catalog_visibility();
		$current_featured   = wc_bool_to_string( $product_object->get_featured() );
		$visibility_options = wc_get_product_visibility_options();
		?>
		<div class="misc-pub-section" id="catalog-visibility">
			<?php _e( 'Catalog visibility:', 'woocommerce' ); ?> <strong id="catalog-visibility-display"><?php
				echo isset( $visibility_options[ $current_visibility ] ) ? esc_html( $visibility_options[ $current_visibility ] ) : esc_html( $current_visibility );

				if ( 'yes' === $current_featured ) {
					echo ', ' . __( 'Featured', 'woocommerce' );
				}
			?></strong>

			<a href="#catalog-visibility" class="edit-catalog-visibility hide-if-no-js"><?php _e( 'Edit', 'woocommerce' ); ?></a>

			<div id="catalog-visibility-select" class="hide-if-js">

				<input type="hidden" name="current_visibility" id="current_visibility" value="<?php echo esc_attr( $current_visibility ); ?>" />
				<input type="hidden" name="current_featured" id="current_featured" value="<?php echo esc_attr( $current_featured ); ?>" />

				<?php
					echo '<p>' . __( 'Choose where this product should be displayed in your catalog. The product will always be accessible directly.', 'woocommerce' ) . '</p>';

					foreach ( $visibility_options as $name => $label ) {
						echo '<input type="radio" name="_visibility" id="_visibility_' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . checked( $current_visibility, $name, false ) . ' data-label="' . esc_attr( $label ) . '" /> <label for="_visibility_' . esc_attr( $name ) . '" class="selectit">' . esc_html( $label ) . '</label><br />';
					}

					echo '<p>' . __( 'Enable this option to feature this product.', 'woocommerce' ) . '</p>';

					echo '<input type="checkbox" name="_featured" id="_featured" ' . checked( $current_featured, 'yes', false ) . ' /> <label for="_featured">' . __( 'Featured product', 'woocommerce' ) . '</label><br />';
				?>
				<p>
					<a href="#catalog-visibility" class="save-post-visibility hide-if-no-js button"><?php _e( 'OK', 'woocommerce' ); ?></a>
					<a href="#catalog-visibility" class="cancel-post-visibility hide-if-no-js"><?php _e( 'Cancel', 'woocommerce' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Filter the directory for uploads.
	 *
	 * @param array $pathdata
	 * @return array
	 */
	public function upload_dir( $pathdata ) {

		// Change upload dir for downloadable files
		if ( isset( $_POST['type'] ) && 'downloadable_product' == $_POST['type'] ) {

			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/woocommerce_uploads';
				$pathdata['url']    = $pathdata['url'] . '/woocommerce_uploads';
				$pathdata['subdir'] = '/woocommerce_uploads';
			} else {
				$new_subdir = '/woocommerce_uploads' . $pathdata['subdir'];

				$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
				$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
				$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
			}
		}

		return $pathdata;
	}

	/**
	 * Run a filter when uploading a downloadable product.
	 */
	public function woocommerce_media_upload_downloadable_product() {
		do_action( 'media_upload_file' );
	}

	/**
	 * Grant downloadable file access to any newly added files on any existing.
	 * orders for this product that have previously been granted downloadable file access.
	 *
	 * @param int $product_id product identifier
	 * @param int $variation_id optional product variation identifier
	 * @param array $downloadable_files newly set files
	 * @deprecated and moved to post-data class.
	 */
	public function process_product_file_download_paths( $product_id, $variation_id, $downloadable_files ) {
		WC_Post_Data::process_product_file_download_paths( $product_id, $variation_id, $downloadable_files );
	}

	/**
	 * Disable DFW feature pointer.
	 */
	public function disable_dfw_feature_pointer() {
		$screen = get_current_screen();

		if ( $screen && 'product' === $screen->id && 'post' === $screen->base ) {
			remove_action( 'admin_print_footer_scripts', array( 'WP_Internal_Pointers', 'pointer_wp410_dfw' ) );
		}
	}

	/**
	 * Removes products, orders, and coupons from the list of post types that support "View Mode" switching.
	 * View mode is seen on posts where you can switch between list or excerpt. Our post types don't support
	 * it, so we want to hide the useless UI from the screen options tab.
	 *
	 * @since 2.6
	 * @param  array $post_types Array of post types supporting view mode
	 * @return array             Array of post types supporting view mode, without products, orders, and coupons
	 */
	public function disable_view_mode_options( $post_types ) {
		unset( $post_types['product'], $post_types['shop_order'], $post_types['shop_coupon'] );
		return $post_types;
	}

	/**
	 * Show blank slate.
	 */
	public function maybe_render_blank_state( $which ) {
		global $post_type;

		if ( in_array( $post_type, array( 'shop_order', 'product', 'shop_coupon' ) ) && 'bottom' === $which ) {
			$counts = (array) wp_count_posts( $post_type );
			unset( $counts['auto-draft'] );
			$count  = array_sum( $counts );

			if ( 0 < $count ) {
				return;
			}

			echo '<div class="woocommerce-BlankState">';

			switch ( $post_type ) {
				case 'shop_order' :
					?>
					<h2 class="woocommerce-BlankState-message"><?php _e( 'When you receive a new order, it will appear here.', 'woocommerce' ); ?></h2>
					<a class="woocommerce-BlankState-cta button-primary button" target="_blank" href="https://docs.woocommerce.com/document/managing-orders/?utm_source=blankslate&utm_medium=product&utm_content=ordersdoc&utm_campaign=woocommerceplugin"><?php _e( 'Learn more about orders', 'woocommerce' ); ?></a>
					<?php
				break;
				case 'shop_coupon' :
					?>
					<h2 class="woocommerce-BlankState-message"><?php _e( 'Coupons are a great way to offer discounts and rewards to your customers. They will appear here once created.', 'woocommerce' ); ?></h2>
					<a class="woocommerce-BlankState-cta button-primary button" target="_blank" href="https://docs.woocommerce.com/document/coupon-management/?utm_source=blankslate&utm_medium=product&utm_content=couponsdoc&utm_campaign=woocommerceplugin"><?php _e( 'Learn more about coupons', 'woocommerce' ); ?></a>
					<?php
				break;
				case 'product' :
					?>
					<h2 class="woocommerce-BlankState-message"><?php _e( 'Ready to start selling something awesome?', 'woocommerce' ); ?></h2>
					<a class="woocommerce-BlankState-cta button-primary button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ); ?>"><?php _e( 'Create your first product!', 'woocommerce' ); ?></a>
					<?php
				break;
			}

			echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub  { display: none; } </style></div>';
		}
	}

	/**
	 * When editing the shop page, we should hide templates.
	 * @return array
	 */
	public function hide_cpt_archive_templates( $page_templates, $class, $post ) {
		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $post && absint( $shop_page_id ) === absint( $post->ID ) ) {
			$page_templates = array();
		}

		return $page_templates;
	}

	/**
	 * Show a notice above the CPT archive.
	 */
	public function show_cpt_archive_notice( $post ) {
		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $post && absint( $shop_page_id ) === absint( $post->ID ) ) {
			?>
			<div class="notice notice-info">
				<p><?php printf( __( 'This is the WooCommerce shop page. The shop page is a special archive that lists your products. <a href="%s">You can read more about this here</a>.', 'woocommerce' ), 'https://docs.woocommerce.com/document/woocommerce-pages/#section-4' ); ?></p>
			</div>
			<?php
		}
	}
}

endif;

new WC_Admin_Post_Types();
