<?php
register_woocommerce_admin_test_helper_rest_route(
	'/tools/toggle-emails/v1',
	'toggle_emails'
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-email-status/v1',
	'get_email_status',
	array(
		'methods' => 'GET',
	)
);

function toggle_emails() {
	$emails_disabled = 'yes';
	if ( $emails_disabled === get_option( 'wc_admin_test_helper_email_disabled', 'no' ) ) {
		$emails_disabled = 'no';
		remove_filter('woocommerce_email_get_option', 'disable_wc_emails' );
	}
	update_option('wc_admin_test_helper_email_disabled', $emails_disabled );
	return new WP_REST_Response( $emails_disabled, 200 );
}

function get_email_status() {
	$emails_disabled = get_option( 'wc_admin_test_helper_email_disabled', 'no' );
	return new WP_REST_Response( $emails_disabled, 200 );
}

if ( 'yes' === get_option( 'wc_admin_test_helper_email_disabled', 'no' ) ) {
	add_filter('woocommerce_email_get_option', 'disable_wc_emails' );
	add_action( 'woocommerce_email', 'unhook_other_wc_emails' );
}

function disable_wc_emails( $key ) {
	if ( $key === 'enabled' ) {
		return false;
	}
}

function unhook_other_wc_emails( $email ) {
	remove_action( 'woocommerce_low_stock_notification', array( $email, 'low_stock' ) );
	remove_action( 'woocommerce_no_stock_notification', array( $email, 'no_stock' ) );
	remove_action( 'woocommerce_product_on_backorder_notification', array( $email, 'backorder' ) );
	remove_action( 'woocommerce_new_customer_note_notification', array( $email->emails['WC_Email_Customer_Note'], 'trigger' ) );
}
