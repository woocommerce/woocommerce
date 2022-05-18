/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const initTracks = () => {
	recordEvent( 'product_add_view' );
};

if ( window.location.pathname.split( '/' ).pop() === 'post-new.php' ) {
	initTracks();
}
