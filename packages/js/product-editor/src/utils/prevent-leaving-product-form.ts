/**
 * External dependencies
 */
import { Location } from 'react-router-dom';

/**
 * Allow switching between tabs without prompting for unsaved changes.
 */
export const preventLeavingProductForm = ( toUrl: URL, fromUrl: Location ) => {
	const toParams = new URLSearchParams( toUrl.search );
	const fromParams = new URLSearchParams( fromUrl.search );
	toParams.delete( 'tab' );
	fromParams.delete( 'tab' );
	return toParams.toString() !== fromParams.toString();
};
