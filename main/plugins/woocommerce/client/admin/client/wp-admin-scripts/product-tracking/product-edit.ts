/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	addExitPageListener,
	getProductData,
	initProductScreenTracks,
} from './shared';

const initTracks = () => {
	const productData = getProductData();

	recordEvent( 'product_edit_view', { product_id: productData?.product_id } );
	initProductScreenTracks();
};

if ( productScreen && productScreen.name === 'edit' ) {
	initTracks();

	addExitPageListener( 'product_edit_view' );
}
