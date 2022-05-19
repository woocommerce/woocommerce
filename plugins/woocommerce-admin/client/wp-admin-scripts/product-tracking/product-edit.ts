/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { initProductScreenTracks } from './shared';

const initTracks = () => {
	recordEvent( 'product_edit_view' );
	initProductScreenTracks();
};

if ( window.location.pathname.split( '/' ).pop() === 'post.php' ) {
	initTracks();
}
