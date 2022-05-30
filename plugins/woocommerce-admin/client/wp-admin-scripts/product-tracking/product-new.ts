/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { initProductScreenTracks } from './shared';

const initTracks = () => {
	recordEvent( 'product_add_view' );
};

if ( productScreen === 'new' ) {
	initTracks();
	initProductScreenTracks();
}
