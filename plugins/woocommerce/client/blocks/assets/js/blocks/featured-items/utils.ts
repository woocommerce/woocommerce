/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import { Coordinates, ImageFit } from './types';

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Given x and y coordinates between 0 and 1 returns a rounded percentage string.
 *
 * Useful for converting to a CSS-compatible position string.
 */
export function calculatePercentPositionFromCoordinates( coords: Coordinates ) {
	if ( ! coords ) return '';

	const x = Math.round( coords.x * 100 );
	const y = Math.round( coords.y * 100 );

	return `${ x }% ${ y }%`;
}

/**
 * Given x and y coordinates between 0 and 1 returns a CSS `objectPosition`.
 */
export function calculateBackgroundImagePosition( coords: Coordinates ) {
	if ( ! coords ) return {};

	return {
		objectPosition: calculatePercentPositionFromCoordinates( coords ),
	};
}

/**
 * Generate the style object of the background image of the block.
 *
 * It outputs styles for either an `img` element or a `div` with a background,
 * depending on what is needed.
 */
export function getBackgroundImageStyles( {
	focalPoint,
	imageFit,
	isImgElement,
	isRepeated,
	url,
}: {
	focalPoint: Coordinates;
	imageFit: ImageFit;
	isImgElement: boolean;
	isRepeated: boolean;
	url: string;
} ) {
	let styles = {};

	if ( isImgElement ) {
		styles = {
			...styles,
			...calculateBackgroundImagePosition( focalPoint ),
			objectFit: imageFit,
		};
	} else {
		styles = {
			...styles,
			...( url && {
				backgroundImage: `url(${ url })`,
			} ),
			backgroundPosition:
				calculatePercentPositionFromCoordinates( focalPoint ),
			...( ! isRepeated && {
				backgroundRepeat: 'no-repeat',
				backgroundSize: imageFit === 'cover' ? imageFit : 'auto',
			} ),
		};
	}

	return styles;
}

/**
 * Generates the CSS class prefix for scoping elements to a block.
 */
export function getClassPrefixFromName( blockName: string ) {
	return `wc-block-${ blockName.split( '/' )[ 1 ] }`;
}

/**
 * Convert the selected ratio to the correct background class.
 *
 * @param ratio Selected opacity from 0 to 100.
 * @return The class name, if applicable (not used for ratio 0 or 50).
 */
export function dimRatioToClass( ratio: number ) {
	return ratio === 0 || ratio === 50
		? null
		: `has-background-dim-${ 10 * Math.round( ratio / 10 ) }`;
}

/**
 * Return the description message when the selected product or category isn't available.
 *
 * @param {string} name current item name.
 * @return {string} The description message for unavailable item.
 */
export function getInvalidItemDescription( name: string ) {
	return name === BLOCK_NAMES.featuredProduct
		? __(
				'Previously selected Product is no longer available',
				'woocommerce'
		  )
		: __(
				'Previously selected Category is no longer available',
				'woocommerce'
		  );
}
