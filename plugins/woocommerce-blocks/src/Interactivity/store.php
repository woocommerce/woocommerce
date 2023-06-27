<?php
/**
 * Merge data with the exsisting store.
 *
 * @param array $data Data that will be merged with the exsisting store.
 *
 * @return $data The current store data.
 */
function wc_store( $data = null ) {
	if ( $data ) {
		WC_Interactivity_Store::merge_data( $data );
	}
	return WC_Interactivity_Store::get_data();
}

/**
 * Render the Interactivity API store in the frontend.
 */
add_action( 'wp_footer', array( 'WC_Interactivity_Store', 'render' ), 8 );
