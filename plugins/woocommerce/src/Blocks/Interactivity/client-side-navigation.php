<?php

/**
 * Print client-side navigation meta tag (hard-coded for now).
 */
function woocommerce_interactivity_add_client_side_navigation_meta_tag() {
	echo '<meta itemprop="wc-client-side-navigation" content="active">';
}
add_action( 'wp_head', 'woocommerce_interactivity_add_client_side_navigation_meta_tag' );
