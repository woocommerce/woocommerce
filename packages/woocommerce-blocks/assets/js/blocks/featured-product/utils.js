/**
 * External dependencies
 */
import { isObject } from 'lodash';

/**
 * Internal dependencies
 */
import { getImageSrcFromProduct } from '../../utils/products';

/**
 * Generate a style object given either a product object or URL to an image.
 *
 * @param {Object|string} url A product object as returned from the API, or an image URL.
 * @return {Object} A style object with a backgroundImage set (if a valid image is provided).
 */
function getBackgroundImageStyles( url ) {
	// If `url` is an object, it's actually a product.
	if ( isObject( url ) ) {
		url = getImageSrcFromProduct( url );
	}
	if ( url ) {
		return { backgroundImage: `url(${ url })` };
	}
	return {};
}

/**
 * Convert the selected ratio to the correct background class.
 *
 * @param {number} ratio Selected opacity from 0 to 100.
 * @return {string} The class name, if applicable (not used for ratio 0 or 50).
 */
function dimRatioToClass( ratio ) {
	return ratio === 0 || ratio === 50
		? null
		: `has-background-dim-${ 10 * Math.round( ratio / 10 ) }`;
}

export { getBackgroundImageStyles, dimRatioToClass };
