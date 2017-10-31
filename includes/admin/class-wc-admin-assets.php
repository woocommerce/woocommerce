<?php
/**
 * Load assets
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Admin_Assets', false ) ) :

/**
 * WC_Admin_Assets Class.
 */
class WC_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		global $wp_scripts;

		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';

		// Register admin styles
		wp_register_style( 'woocommerce_admin_menu_styles', WC()->plugin_url() . '/assets/css/menu.css', array(), WC_VERSION );
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css', array(), $jquery_version );
		wp_register_style( 'woocommerce_admin_dashboard_styles', WC()->plugin_url() . '/assets/css/dashboard.css', array(), WC_VERSION );
		wp_register_style( 'woocommerce_admin_print_reports_styles', WC()->plugin_url() . '/assets/css/reports-print.css', array(), WC_VERSION, 'print' );

		// Add RTL support for admin styles
		wp_style_add_data( 'woocommerce_admin_menu_styles', 'rtl', 'replace' );
		wp_style_add_data( 'woocommerce_admin_styles', 'rtl', 'replace' );
		wp_style_add_data( 'woocommerce_admin_dashboard_styles', 'rtl', 'replace' );
		wp_style_add_data( 'woocommerce_admin_print_reports_styles', 'rtl', 'replace' );

		// Sitewide menu CSS
		wp_enqueue_style( 'woocommerce_admin_menu_styles' );

		// Admin styles for WC pages only
		if ( in_array( $screen_id, wc_get_screen_ids() ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
		}

		if ( in_array( $screen_id, array( 'dashboard' ) ) ) {
			wp_enqueue_style( 'woocommerce_admin_dashboard_styles' );
		}

		if ( in_array( $screen_id, array( 'woocommerce_page_wc-reports', 'toplevel_page_wc-reports' ) ) ) {
			wp_enqueue_style( 'woocommerce_admin_print_reports_styles' );
		}

		/**
		 * @deprecated 2.3
		 */
		if ( has_action( 'woocommerce_admin_css' ) ) {
			do_action( 'woocommerce_admin_css' );
			wc_deprecated_function( 'The woocommerce_admin_css action', '2.3', 'admin_enqueue_scripts' );
		}
	}


	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $wp_query, $post;

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WC_VERSION );
		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
		wp_register_script( 'accounting', WC()->plugin_url() . '/assets/js/accounting/accounting' . $suffix . '.js', array( 'jquery' ), '0.4.2' );
		wp_register_script( 'round', WC()->plugin_url() . '/assets/js/round/round' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_register_script( 'wc-admin-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'accounting', 'round', 'wc-enhanced-select', 'plupload-all', 'stupidtable', 'jquery-tiptip' ), WC_VERSION );
		wp_register_script( 'zeroclipboard', WC()->plugin_url() . '/assets/js/zeroclipboard/jquery.zeroclipboard' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_register_script( 'qrcode', WC()->plugin_url() . '/assets/js/jquery-qrcode/jquery.qrcode' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_register_script( 'stupidtable', WC()->plugin_url() . '/assets/js/stupidtable/stupidtable' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_register_script( 'serializejson', WC()->plugin_url() . '/assets/js/jquery-serializejson/jquery.serializejson' . $suffix . '.js', array( 'jquery' ), '2.8.1' );
		wp_register_script( 'flot', WC()->plugin_url() . '/assets/js/jquery-flot/jquery.flot' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_register_script( 'flot-resize', WC()->plugin_url() . '/assets/js/jquery-flot/jquery.flot.resize' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
		wp_register_script( 'flot-time', WC()->plugin_url() . '/assets/js/jquery-flot/jquery.flot.time' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
		wp_register_script( 'flot-pie', WC()->plugin_url() . '/assets/js/jquery-flot/jquery.flot.pie' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
		wp_register_script( 'flot-stack', WC()->plugin_url() . '/assets/js/jquery-flot/jquery.flot.stack' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
		wp_register_script( 'wc-settings-tax', WC()->plugin_url() . '/assets/js/admin/settings-views-html-settings-tax' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone', 'jquery-blockui' ), WC_VERSION );
		wp_register_script( 'wc-backbone-modal', WC()->plugin_url() . '/assets/js/admin/backbone-modal' . $suffix . '.js', array( 'underscore', 'backbone', 'wp-util' ), WC_VERSION );
		wp_register_script( 'wc-shipping-zones', WC()->plugin_url() . '/assets/js/admin/wc-shipping-zones' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone', 'jquery-ui-sortable', 'wc-enhanced-select', 'wc-backbone-modal' ), WC_VERSION );
		wp_register_script( 'wc-shipping-zone-methods', WC()->plugin_url() . '/assets/js/admin/wc-shipping-zone-methods' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone', 'jquery-ui-sortable', 'wc-backbone-modal' ), WC_VERSION );
		wp_register_script( 'wc-shipping-classes', WC()->plugin_url() . '/assets/js/admin/wc-shipping-classes' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone' ), WC_VERSION );
		wp_register_script( 'wc-clipboard', WC()->plugin_url() . '/assets/js/admin/wc-clipboard' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.1' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_products_nonce'     => wp_create_nonce( 'search-products' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
			'search_categories_nonce'   => wp_create_nonce( 'search-categories' ),
		) );

		// Accounting
		wp_localize_script( 'accounting', 'accounting_params', array(
			'mon_decimal_point' => wc_get_price_decimal_separator(),
		) );

		// WooCommerce admin pages
		if ( in_array( $screen_id, wc_get_screen_ids() ) ) {
			wp_enqueue_script( 'iris' );
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );

			$locale  = localeconv();
			$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

			$params = array(
				/* translators: %s: decimal */
				'i18n_decimal_error'                => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', 'woocommerce' ), $decimal ),
				/* translators: %s: price decimal separator */
				'i18n_mon_decimal_error'            => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'woocommerce' ), wc_get_price_decimal_separator() ),
				'i18n_country_iso_error'            => __( 'Please enter in country code with two capital letters.', 'woocommerce' ),
				'i18_sale_less_than_regular_error'  => __( 'Please enter in a value less than the regular price.', 'woocommerce' ),
				'decimal_point'                     => $decimal,
				'mon_decimal_point'                 => wc_get_price_decimal_separator(),
				'strings' => array(
					'import_products' => __( 'Import', 'woocommerce' ),
					'export_products' => __( 'Export', 'woocommerce' ),
				),
				'urls' => array(
					'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
					'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
				),
			);

			wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
		}

		// Edit product category pages
		if ( in_array( $screen_id, array( 'edit-product_cat' ) ) ) {
			wp_enqueue_media();
		}

		// Products
		if ( in_array( $screen_id, array( 'edit-product' ) ) ) {
			wp_enqueue_script( 'woocommerce_quick-edit', WC()->plugin_url() . '/assets/js/admin/quick-edit' . $suffix . '.js', array( 'jquery', 'woocommerce_admin' ), WC_VERSION );

			$params = array(
				'strings' => array(
					'allow_reviews' => esc_js( __( 'Enable reviews', 'woocommerce' ) ),
				),
			);

			wp_localize_script( 'woocommerce_quick-edit', 'woocommerce_quick_edit', $params );
		}

		// Meta boxes
		if ( in_array( $screen_id, array( 'product', 'edit-product' ) ) ) {
			wp_enqueue_media();
			wp_register_script( 'wc-admin-product-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-product' . $suffix . '.js', array( 'wc-admin-meta-boxes', 'media-models' ), WC_VERSION );
			wp_register_script( 'wc-admin-variation-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-product-variation' . $suffix . '.js', array( 'wc-admin-meta-boxes', 'serializejson', 'media-models' ), WC_VERSION );

			wp_enqueue_script( 'wc-admin-product-meta-boxes' );
			wp_enqueue_script( 'wc-admin-variation-meta-boxes' );

			$params = array(
				'post_id'                             => isset( $post->ID ) ? $post->ID : '',
				'plugin_url'                          => WC()->plugin_url(),
				'ajax_url'                            => admin_url( 'admin-ajax.php' ),
				'woocommerce_placeholder_img_src'     => wc_placeholder_img_src(),
				'add_variation_nonce'                 => wp_create_nonce( 'add-variation' ),
				'link_variation_nonce'                => wp_create_nonce( 'link-variations' ),
				'delete_variations_nonce'             => wp_create_nonce( 'delete-variations' ),
				'load_variations_nonce'               => wp_create_nonce( 'load-variations' ),
				'save_variations_nonce'               => wp_create_nonce( 'save-variations' ),
				'bulk_edit_variations_nonce'          => wp_create_nonce( 'bulk-edit-variations' ),
				'i18n_link_all_variations'            => esc_js( sprintf( __( 'Are you sure you want to link all variations? This will create a new variation for each and every possible combination of variation attributes (max %d per run).', 'woocommerce' ), defined( 'WC_MAX_LINKED_VARIATIONS' ) ? WC_MAX_LINKED_VARIATIONS : 50 ) ),
				'i18n_enter_a_value'                  => esc_js( __( 'Enter a value', 'woocommerce' ) ),
				'i18n_enter_menu_order'               => esc_js( __( 'Variation menu order (determines position in the list of variations)', 'woocommerce' ) ),
				'i18n_enter_a_value_fixed_or_percent' => esc_js( __( 'Enter a value (fixed or %)', 'woocommerce' ) ),
				'i18n_delete_all_variations'          => esc_js( __( 'Are you sure you want to delete all variations? This cannot be undone.', 'woocommerce' ) ),
				'i18n_last_warning'                   => esc_js( __( 'Last warning, are you sure?', 'woocommerce' ) ),
				'i18n_choose_image'                   => esc_js( __( 'Choose an image', 'woocommerce' ) ),
				'i18n_set_image'                      => esc_js( __( 'Set variation image', 'woocommerce' ) ),
				'i18n_variation_added'                => esc_js( __( "variation added", 'woocommerce' ) ),
				'i18n_variations_added'               => esc_js( __( "variations added", 'woocommerce' ) ),
				'i18n_no_variations_added'            => esc_js( __( "No variations added", 'woocommerce' ) ),
				'i18n_remove_variation'               => esc_js( __( 'Are you sure you want to remove this variation?', 'woocommerce' ) ),
				'i18n_scheduled_sale_start'           => esc_js( __( 'Sale start date (YYYY-MM-DD format or leave blank)', 'woocommerce' ) ),
				'i18n_scheduled_sale_end'             => esc_js( __( 'Sale end date (YYYY-MM-DD format or leave blank)', 'woocommerce' ) ),
				'i18n_edited_variations'              => esc_js( __( 'Save changes before changing page?', 'woocommerce' ) ),
				'i18n_variation_count_single'         => esc_js( __( '%qty% variation', 'woocommerce' ) ),
				'i18n_variation_count_plural'         => esc_js( __( '%qty% variations', 'woocommerce' ) ),
				'variations_per_page'                 => absint( apply_filters( 'woocommerce_admin_meta_boxes_variations_per_page', 15 ) ),
			);

			wp_localize_script( 'wc-admin-variation-meta-boxes', 'woocommerce_admin_meta_boxes_variations', $params );
		}
		if ( in_array( str_replace( 'edit-', '', $screen_id ), wc_get_order_types( 'order-meta-boxes' ) ) ) {
			$default_location = wc_get_customer_default_location();

			wp_enqueue_script( 'wc-admin-order-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-order' . $suffix . '.js', array( 'wc-admin-meta-boxes', 'wc-backbone-modal', 'selectWoo' ), WC_VERSION );
			wp_localize_script( 'wc-admin-order-meta-boxes', 'woocommerce_admin_meta_boxes_order', array(
				'countries'              => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
				'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
				'default_country'        => isset( $default_location['country'] ) ? $default_location['country'] : '',
				'default_state'          => isset( $default_location['state'] ) ? $default_location['state'] : '',
				'placeholder_name'       => esc_attr__( 'Name (required)', 'woocommerce' ),
				'placeholder_value'      => esc_attr__( 'Value (required)', 'woocommerce' ),
			) );
		}
		if ( in_array( $screen_id, array( 'shop_coupon', 'edit-shop_coupon' ) ) ) {
			wp_enqueue_script( 'wc-admin-coupon-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-coupon' . $suffix . '.js', array( 'wc-admin-meta-boxes' ), WC_VERSION );
		}
		if ( in_array( str_replace( 'edit-', '', $screen_id ), array_merge( array( 'shop_coupon', 'product' ), wc_get_order_types( 'order-meta-boxes' ) ) ) ) {
			$post_id  = isset( $post->ID ) ? $post->ID : '';
			$currency = '';

			if ( $post_id && in_array( get_post_type( $post_id ), wc_get_order_types( 'order-meta-boxes' ) ) && ( $order = wc_get_order( $post_id ) ) ) {
				$currency = $order->get_currency();
			}

			$params = array(
				'remove_item_notice'            => __( "Are you sure you want to remove the selected items? If you have previously reduced this item's stock, or this order was submitted by a customer, you will need to manually restore the item's stock.", 'woocommerce' ),
				'i18n_select_items'             => __( 'Please select some items.', 'woocommerce' ),
				'i18n_do_refund'                => __( 'Are you sure you wish to process this refund? This action cannot be undone.', 'woocommerce' ),
				'i18n_delete_refund'            => __( 'Are you sure you wish to delete this refund? This action cannot be undone.', 'woocommerce' ),
				'i18n_delete_tax'               => __( 'Are you sure you wish to delete this tax column? This action cannot be undone.', 'woocommerce' ),
				'remove_item_meta'              => __( 'Remove this item meta?', 'woocommerce' ),
				'remove_attribute'              => __( 'Remove this attribute?', 'woocommerce' ),
				'name_label'                    => __( 'Name', 'woocommerce' ),
				'remove_label'                  => __( 'Remove', 'woocommerce' ),
				'click_to_toggle'               => __( 'Click to toggle', 'woocommerce' ),
				'values_label'                  => __( 'Value(s)', 'woocommerce' ),
				'text_attribute_tip'            => __( 'Enter some text, or some attributes by pipe (|) separating values.', 'woocommerce' ),
				'visible_label'                 => __( 'Visible on the product page', 'woocommerce' ),
				'used_for_variations_label'     => __( 'Used for variations', 'woocommerce' ),
				'new_attribute_prompt'          => __( 'Enter a name for the new attribute term:', 'woocommerce' ),
				'calc_totals'                   => __( 'Recalculate totals? This will calculate taxes based on the customers country (or the store base country) and update totals.', 'woocommerce' ),
				'copy_billing'                  => __( 'Copy billing information to shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
				'load_billing'                  => __( "Load the customer's billing information? This will remove any currently entered billing information.", 'woocommerce' ),
				'load_shipping'                 => __( "Load the customer's shipping information? This will remove any currently entered shipping information.", 'woocommerce' ),
				'featured_label'                => __( 'Featured', 'woocommerce' ),
				'prices_include_tax'            => esc_attr( get_option( 'woocommerce_prices_include_tax' ) ),
				'tax_based_on'                  => esc_attr( get_option( 'woocommerce_tax_based_on' ) ),
				'round_at_subtotal'             => esc_attr( get_option( 'woocommerce_tax_round_at_subtotal' ) ),
				'no_customer_selected'          => __( 'No customer selected', 'woocommerce' ),
				'plugin_url'                    => WC()->plugin_url(),
				'ajax_url'                      => admin_url( 'admin-ajax.php' ),
				'order_item_nonce'              => wp_create_nonce( 'order-item' ),
				'add_attribute_nonce'           => wp_create_nonce( 'add-attribute' ),
				'save_attributes_nonce'         => wp_create_nonce( 'save-attributes' ),
				'calc_totals_nonce'             => wp_create_nonce( 'calc-totals' ),
				'get_customer_details_nonce'    => wp_create_nonce( 'get-customer-details' ),
				'search_products_nonce'         => wp_create_nonce( 'search-products' ),
				'grant_access_nonce'            => wp_create_nonce( 'grant-access' ),
				'revoke_access_nonce'           => wp_create_nonce( 'revoke-access' ),
				'add_order_note_nonce'          => wp_create_nonce( 'add-order-note' ),
				'delete_order_note_nonce'       => wp_create_nonce( 'delete-order-note' ),
				'calendar_image'                => WC()->plugin_url() . '/assets/images/calendar.png',
				'post_id'                       => isset( $post->ID ) ? $post->ID : '',
				'base_country'                  => WC()->countries->get_base_country(),
				'currency_format_num_decimals'  => wc_get_price_decimals(),
				'currency_format_symbol'        => get_woocommerce_currency_symbol( $currency ),
				'currency_format_decimal_sep'   => esc_attr( wc_get_price_decimal_separator() ),
				'currency_format_thousand_sep'  => esc_attr( wc_get_price_thousand_separator() ),
				'currency_format'               => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting JS
				'rounding_precision'            => wc_get_rounding_precision(),
				'tax_rounding_mode'             => WC_TAX_ROUNDING_MODE,
				'product_types'                 => array_unique( array_merge( array( 'simple', 'grouped', 'variable', 'external' ), array_keys( wc_get_product_types() ) ) ),
				'i18n_download_permission_fail' => __( 'Could not grant access - the user may already have permission for this file or billing email is not set. Ensure the billing email is set, and the order has been saved.', 'woocommerce' ),
				'i18n_permission_revoke'        => __( 'Are you sure you want to revoke access to this download?', 'woocommerce' ),
				'i18n_tax_rate_already_exists'  => __( 'You cannot add the same tax rate twice!', 'woocommerce' ),
				'i18n_delete_note'              => __( 'Are you sure you wish to delete this note? This action cannot be undone.', 'woocommerce' ),
				'i18n_apply_coupon'             => __( 'Enter a coupon code to apply to this order.', 'woocommerce' ),
				'i18n_add_fee'                  => __( 'Enter a fixed amount or percentage to apply as a fee.', 'woocommerce' ),
			);

			wp_localize_script( 'wc-admin-meta-boxes', 'woocommerce_admin_meta_boxes', $params );
		}

		// Term ordering - only when sorting by term_order
		if ( ( strstr( $screen_id, 'edit-pa_' ) || ( ! empty( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) ) ) ) ) && ! isset( $_GET['orderby'] ) ) {

			wp_register_script( 'woocommerce_term_ordering', WC()->plugin_url() . '/assets/js/admin/term-ordering' . $suffix . '.js', array( 'jquery-ui-sortable' ), WC_VERSION );
			wp_enqueue_script( 'woocommerce_term_ordering' );

			$taxonomy = isset( $_GET['taxonomy'] ) ? wc_clean( $_GET['taxonomy'] ) : '';

			$woocommerce_term_order_params = array(
				'taxonomy' => $taxonomy,
			);

			wp_localize_script( 'woocommerce_term_ordering', 'woocommerce_term_ordering_params', $woocommerce_term_order_params );
		}

		// Product sorting - only when sorting by menu order on the products page
		if ( current_user_can( 'edit_others_pages' ) && 'edit-product' === $screen_id && isset( $wp_query->query['orderby'] ) && 'menu_order title' === $wp_query->query['orderby'] ) {
			wp_register_script( 'woocommerce_product_ordering', WC()->plugin_url() . '/assets/js/admin/product-ordering' . $suffix . '.js', array( 'jquery-ui-sortable' ), WC_VERSION, true );
			wp_enqueue_script( 'woocommerce_product_ordering' );
		}

		// Reports Pages
		if ( in_array( $screen_id, apply_filters( 'woocommerce_reports_screen_ids', array( $wc_screen_id . '_page_wc-reports', 'toplevel_page_wc-reports', 'dashboard' ) ) ) ) {
			wp_register_script( 'wc-reports', WC()->plugin_url() . '/assets/js/admin/reports' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_VERSION );

			wp_enqueue_script( 'wc-reports' );
			wp_enqueue_script( 'flot' );
			wp_enqueue_script( 'flot-resize' );
			wp_enqueue_script( 'flot-time' );
			wp_enqueue_script( 'flot-pie' );
			wp_enqueue_script( 'flot-stack' );
		}

		// API settings
		if ( $wc_screen_id . '_page_wc-settings' === $screen_id && isset( $_GET['section'] ) && 'keys' == $_GET['section'] ) {
			wp_register_script( 'wc-api-keys', WC()->plugin_url() . '/assets/js/admin/api-keys' . $suffix . '.js', array( 'jquery', 'woocommerce_admin', 'underscore', 'backbone', 'wp-util', 'qrcode', 'wc-clipboard' ), WC_VERSION, true );
			wp_enqueue_script( 'wc-api-keys' );
			wp_localize_script(
				'wc-api-keys',
				'woocommerce_admin_api_keys',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'update_api_nonce' => wp_create_nonce( 'update-api-key' ),
					'clipboard_failed' => esc_html__( 'Copying to clipboard failed. Please press Ctrl/Cmd+C to copy.', 'woocommerce' ),
				)
			);
		}

		// System status.
		if ( $wc_screen_id . '_page_wc-status' === $screen_id ) {
			wp_register_script( 'wc-admin-system-status', WC()->plugin_url() . '/assets/js/admin/system-status' . $suffix . '.js', array( 'wc-clipboard' ), WC_VERSION );
			wp_enqueue_script( 'wc-admin-system-status' );
			wp_localize_script(
				'wc-admin-system-status',
				'woocommerce_admin_system_status',
				array(
					'delete_log_confirmation' => esc_js( __( 'Are you sure you want to delete this log?', 'woocommerce' ) ),
				)
			);
		}

		if ( in_array( $screen_id, array( 'user-edit', 'profile' ) ) ) {
			wp_register_script( 'wc-users', WC()->plugin_url() . '/assets/js/admin/users' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select', 'selectWoo' ), WC_VERSION, true );
			wp_enqueue_script( 'wc-users' );
			wp_localize_script(
				'wc-users',
				'wc_users_params',
				array(
					'countries'              => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
					'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
				)
			);
		}
	}
}

endif;

return new WC_Admin_Assets();
