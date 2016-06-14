<?php
/**
 * WooCommerce Account Functions
 *
 * Functions for account specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the url to the lost password endpoint url.
 *
 * @access public
 * @param  string $default_url
 * @return string
 */
function wc_lostpassword_url( $default_url = '' ) {
	$wc_password_reset_url = wc_get_page_permalink( 'myaccount' );

	if ( false !== $wc_password_reset_url ) {
		return wc_get_endpoint_url( 'lost-password', '', $wc_password_reset_url );
	} else {
		return $default_url;
	}
}

add_filter( 'lostpassword_url', 'wc_lostpassword_url', 10, 1 );

/**
 * Get the link to the edit account details page.
 *
 * @return string
 */
function wc_customer_edit_account_url() {
	$edit_account_url = wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) );

	return apply_filters( 'woocommerce_customer_edit_account_url', $edit_account_url );
}

/**
 * Get the edit address slug translation.
 *
 * @param  string  $id   Address ID.
 * @param  bool    $flip Flip the array to make it possible to retrieve the values ​​from both sides.
 *
 * @return string        Address slug i18n.
 */
function wc_edit_address_i18n( $id, $flip = false ) {
	$slugs = apply_filters( 'woocommerce_edit_address_slugs', array(
		'billing'  => sanitize_title( _x( 'billing', 'edit-address-slug', 'woocommerce' ) ),
		'shipping' => sanitize_title( _x( 'shipping', 'edit-address-slug', 'woocommerce' ) )
	) );

	if ( $flip ) {
		$slugs = array_flip( $slugs );
	}

	if ( ! isset( $slugs[ $id ] ) ) {
		return $id;
	}

	return $slugs[ $id ];
}

/**
 * Get My Account menu items.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_menu_items() {
	$items = array(
		'dashboard'       => __( 'Dashboard', 'woocommerce' ),
		'orders'          => __( 'Orders', 'woocommerce' ),
		'downloads'       => __( 'Downloads', 'woocommerce' ),
		'edit-address'    => __( 'Addresses', 'woocommerce' ),
		'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
		'edit-account'    => __( 'Account Details', 'woocommerce' ),
		'customer-logout' => __( 'Logout', 'woocommerce' ),
	);

	// Remove empty items.
	$downloads = WC()->customer->get_downloadable_products();

	if ( ! sizeof( $downloads ) ) {
		unset( $items['downloads'] );
	}

	// Check if payment gateways support add new payment methods.
	$support_payment_methods = false;
	foreach ( WC()->payment_gateways->get_available_payment_gateways() as $gateway ) {
		if ( $gateway->supports( 'add_payment_method' ) || $gateway->supports( 'tokenization' ) ) {
			$support_payment_methods = true;
			break;
		}
	}

	if ( ! $support_payment_methods ) {
		unset( $items['payment-methods'] );
	}

	return apply_filters( 'woocommerce_account_menu_items', $items );
}

/**
 * Get account menu item classes.
 *
 * @since 2.6.0
 * @param string $endpoint
 * @return string
 */
function wc_get_account_menu_item_classes( $endpoint ) {
	global $wp;

	$classes = array(
		'woocommerce-MyAccount-navigation-link',
		'woocommerce-MyAccount-navigation-link--' . $endpoint,
	);

	// Set current item class.
	$current = isset( $wp->query_vars[ $endpoint ] );
	if ( 'dashboard' === $endpoint && ( isset( $wp->query_vars['page'] ) || empty( $wp->query_vars ) ) ) {
		$current = true; // Dashboard is not an endpoint, so needs a custom check.
	}

	if ( $current ) {
		$classes[] = 'is-active';
	}

	$classes = apply_filters( 'woocommerce_account_menu_item_classes', $classes, $endpoint );

	return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/**
 * Get account endpoint URL.
 *
 * @since 2.6.0
 * @param string $endpoint
 * @return string
 */
function wc_get_account_endpoint_url( $endpoint ) {
	if ( 'dashboard' === $endpoint ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return wc_get_endpoint_url( $endpoint );
}

/**
 * Get My Account > Orders columns.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_orders_columns() {
	$columns = apply_filters( 'woocommerce_account_orders_columns', array(
		'order-number'  => __( 'Order', 'woocommerce' ),
		'order-date'    => __( 'Date', 'woocommerce' ),
		'order-status'  => __( 'Status', 'woocommerce' ),
		'order-total'   => __( 'Total', 'woocommerce' ),
		'order-actions' => '&nbsp;',
	) );

	// Deprecated filter since 2.6.0.
	return apply_filters( 'woocommerce_my_account_my_orders_columns', $columns );
}

/**
 * Get My Account > Downloads columns.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_downloads_columns() {
	return apply_filters( 'woocommerce_account_downloads_columns', array(
		'download-file'      => __( 'File', 'woocommerce' ),
		'download-remaining' => __( 'Remaining', 'woocommerce' ),
		'download-expires'   => __( 'Expires', 'woocommerce' ),
		'download-actions'   => '&nbsp;',
	) );
}

/**
 * Get My Account > Payment methods columns.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_payment_methods_columns() {
	return apply_filters( 'woocommerce_account_payment_methods_columns', array(
		'method'  => __( 'Method', 'woocommerce' ),
		'expires' => __( 'Expires', 'woocommerce' ),
		'actions' => '&nbsp;',
	) );
}

/**
 * Get My Account > Payment methods types
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_payment_methods_types() {
	return apply_filters( 'woocommerce_payment_methods_types', array(
		'cc'     => __( 'Credit Card', 'woocommerce' ),
		'echeck' => __( 'eCheck', 'woocommerce' ),
	) );
}

/**
 * Returns an array of a user's saved payments list for output on the account tab.
 *
 * @since  2.6
 * @param  array $list         List of payment methods passed from wc_get_customer_saved_methods_list()
 * @param  int   $customer_id  The customer to fetch payment methods for
 * @return array               Filtered list of customers payment methods
 */
function wc_get_account_saved_payment_methods_list( $list, $customer_id ) {
	$payment_tokens = WC_Payment_Tokens::get_customer_tokens( $customer_id );
	foreach ( $payment_tokens as $payment_token ) {
		$delete_url      = wc_get_endpoint_url( 'delete-payment-method', $payment_token->get_id() );
		$delete_url      = wp_nonce_url( $delete_url, 'delete-payment-method-' . $payment_token->get_id() );
		$set_default_url = wc_get_endpoint_url( 'set-default-payment-method', $payment_token->get_id() );
		$set_default_url = wp_nonce_url( $set_default_url, 'set-default-payment-method-' . $payment_token->get_id() );

		$type            = strtolower( $payment_token->get_type() );
		$list[ $type ][] = array(
			'method' => array(
				'gateway' => $payment_token->get_gateway_id(),
			),
			'expires'    => esc_html__( 'N/A', 'woocommerce' ),
			'is_default' => $payment_token->is_default(),
			'actions'    => array(
				'delete' => array(
					'url'  => $delete_url,
					'name' => esc_html__( 'Delete', 'woocommerce' ),
				),
			),
		);
		$key = key( array_slice( $list[ $type ], -1, 1, true ) );

		if ( ! $payment_token->is_default() ) {
			$list[ $type ][$key]['actions']['default'] = array(
				'url' => $set_default_url,
				'name' => esc_html__( 'Make Default', 'woocommerce' ),
			);
		}

		$list[ $type ][ $key ] = apply_filters( 'woocommerce_payment_methods_list_item', $list[ $type ][ $key ], $payment_token );
	}
	return $list;
}

add_filter( 'woocommerce_saved_payment_methods_list', 'wc_get_account_saved_payment_methods_list', 10, 2 );

/**
 * Controls the output for credit cards on the my account page.
 *
 * @since 2.6
 * @param  array             $item         Individual list item from woocommerce_saved_payment_methods_list
 * @param  WC_Payment_Token $payment_token The payment token associated with this method entry
 * @return array                           Filtered item
 */
function wc_get_account_saved_payment_methods_list_item_cc( $item, $payment_token ) {
	if ( 'cc' !== strtolower( $payment_token->get_type() ) ) {
		return $item;
	}

	$card_type               = $payment_token->get_card_type();
	$item['method']['last4'] = $payment_token->get_last4();
	$item['method']['brand'] = ( ! empty( $card_type ) ? ucfirst( $card_type ) : esc_html__( 'Credit Card', 'woocommerce' ) );
	$item['expires']         = $payment_token->get_expiry_month() . '/' . substr( $payment_token->get_expiry_year(), -2 );

	return $item;
}

add_filter( 'woocommerce_payment_methods_list_item', 'wc_get_account_saved_payment_methods_list_item_cc', 10, 2 );

/**
 * Controls the output for eChecks on the my account page.
 *
 * @since 2.6
 * @param  array             $item         Individual list item from woocommerce_saved_payment_methods_list
 * @param  WC_Payment_Token $payment_token The payment token associated with this method entry
 * @return array                           Filtered item
 */
function wc_get_account_saved_payment_methods_list_item_echeck( $item, $payment_token ) {
	if ( 'echeck' !== strtolower( $payment_token->get_type() ) ) {
		return $item;
	}

	$item['method']['last4'] = $payment_token->get_last4();
	$item['method']['brand'] =  esc_html__( 'eCheck', 'woocommerce' );

	return $item;
}

add_filter( 'woocommerce_payment_methods_list_item', 'wc_get_account_saved_payment_methods_list_item_echeck', 10, 2 );
