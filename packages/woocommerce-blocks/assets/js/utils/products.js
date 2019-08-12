/**
 * Get the src of the first image attached to a product (the featured image).
 *
 * @param {array} images The array of images, destructured from the product object.
 * @return {string} The full URL to the image.
 */
export function getImageSrcFromProduct( { images = [] } ) {
	if ( images.length ) {
		return images[ 0 ].src || '';
	}
	return '';
}

/**
 * Get the ID of the first image attached to a product (the featured image).
 *
 * @param {array} images The array of images, destructured from the product object.
 * @return {number} The ID of the image.
 */
export function getImageIdFromProduct( { images = [] } ) {
	if ( images.length ) {
		return images[ 0 ].id || 0;
	}
	return 0;
}
