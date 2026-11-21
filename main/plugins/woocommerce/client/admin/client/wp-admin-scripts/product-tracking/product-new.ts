/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { addExitPageListener, initProductScreenTracks } from './shared';

const initTracks = () => {
	recordEvent( 'product_add_view' );
};

if ( productScreen && productScreen.name === 'new' ) {
	initTracks();
	initProductScreenTracks();

	addExitPageListener( 'product_add_view' );
}
