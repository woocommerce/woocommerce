/**
 * External dependencies
 */
import { isObject } from 'lodash';

/**
 * Get the src from a category object, unless null (no image).
 *
 * @param {object|null} category A product category object from the API.
 * @return {string} The src of the category image.
 */
function getCategoryImageSrc( category ) {
	if ( category && isObject( category.image ) ) {
		return category.image.src;
	}
	return '';
}

/**
 * Get the attachment ID from a category object, unless null (no image).
 *
 * @param {object|null} category A product category object from the API.
 * @return {number} The id of the category image.
 */
function getCategoryImageId( category ) {
	if ( category && isObject( category.image ) ) {
		return category.image.id;
	}
	return 0;
}

/**
 * Generate a style object given either a product category image from the API or URL to an image.
 *
 * @param {string} url An image URL.
 * @return {Object} A style object with a backgroundImage set (if a valid image is provided).
 */
function getBackgroundImageStyles( url ) {
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

export {
	getCategoryImageSrc,
	getCategoryImageId,
	getBackgroundImageStyles,
	dimRatioToClass,
};
