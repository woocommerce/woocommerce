/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { getMshotsUrl } from './mshots-image';
import { PREVIEW_IMAGE_OPTION } from './';

export const fetchSitePreviewImage = async () => {
	const homeUrl: string = getSetting( 'homeUrl', '' );
	return window
		.fetch( getMshotsUrl( homeUrl, PREVIEW_IMAGE_OPTION ) )
		.catch( () => {
			// Ignore errors
		} );
};
