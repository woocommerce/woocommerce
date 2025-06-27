<?php

/**
 * Disable Mailchimp landing site cookie in the Mailchimp for WooCommerce plugin.
 *
 * In testing, this cookie appears to get set on the first page load for each browser,
 * which means that the first experience for each new browser is a cache miss.
 *
 * @param string $cookie Cookie name.
 * @return bool
 */
add_filter( 'mailchimp_allowed_to_use_cookie', 'woocommerce_disable_mailchimp_landing_cookie' );
function woocommerce_disable_mailchimp_landing_cookie( $cookie ) {
	if ( 'mailchimp_landing_site' === $cookie ) {
		return false;
	}
	return $cookie;
}
