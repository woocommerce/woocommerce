/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { addExitPageListener, initProductScreenTracks } from './shared';

const initTracks = () => {
	recordEvent( 'product_edit_view' );
	initProductScreenTracks();
};

if ( productScreen && productScreen.name === 'edit' ) {
	initTracks();

	addExitPageListener( 'product_edit_view' );
}
