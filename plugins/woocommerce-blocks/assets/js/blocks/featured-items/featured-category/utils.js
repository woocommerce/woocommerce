/**
 * External dependencies
 */
import { isObject } from 'lodash';

/**
 * Get the src from a category object, unless null (no image).
 *
 * @param {Object|null} category A product category object from the API.
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
 * @param {Object|null} category A product category object from the API.
 * @return {number} The id of the category image.
 */
function getCategoryImageId( category ) {
	if ( category && isObject( category.image ) ) {
		return category.image.id;
	}
	return 0;
}

export { getCategoryImageSrc, getCategoryImageId };
