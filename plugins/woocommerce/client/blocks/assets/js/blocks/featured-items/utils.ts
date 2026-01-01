/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import { Coordinates, ImageFit } from './types';
import { BgImageDimensions } from './use-background-image';

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
export function getInvalidItemDescription( name: string ): string {
	return name === BLOCK_NAMES.featuredProduct
		? __(
				'Previously selected product is no longer available',
				'woocommerce'
		  )
		: __(
				'Previously selected category is no longer available',
				'woocommerce'
		  );
}

/**
 * Determines whether the background color behind an image will be visible,
 * based on the image's transparency, repetition, fit, and container size.
 */
export const getBackgroundColorVisibilityStatus = ( {
	isImageBgTransparent,
	originalImgDimension,
	parentContainerDimension,
	isRepeated,
	imageFit,
}: {
	isImageBgTransparent: boolean;
	originalImgDimension: BgImageDimensions;
	parentContainerDimension: BgImageDimensions;
	isRepeated: boolean;
	imageFit: 'cover' | 'none';
} ) => {
	if ( isImageBgTransparent ) {
		return {
			isBackgroundVisible: true,
			message: null,
		};
	}

	// Checks if bg-image is not transparent and repeated all-over parent div or covers available parent div space.
	if ( ! isImageBgTransparent && ( isRepeated || imageFit === 'cover' ) ) {
		if ( isRepeated ) {
			return {
				isBackgroundVisible: false,
				message: __(
					'You’ve set a background color behind an image set to repeat, the background color cannot be seen.',
					'woocommerce'
				),
			};
		}

		return {
			isBackgroundVisible: false,
			message: __(
				'You’ve set a background color behind an image set to cover, the background color cannot be seen.',
				'woocommerce'
			),
		};
	}

	// Checks if bg-image is not transparent and original-bg-image size is bigger than parent container's available space.
	if (
		! isImageBgTransparent &&
		originalImgDimension.height >= parentContainerDimension.height &&
		originalImgDimension.width >= parentContainerDimension.width
	) {
		return {
			isBackgroundVisible: false,
			message: __(
				"You've set background color to an opaque image, the background color cannot be seen.",
				'woocommerce'
			),
		};
	}

	// Checks if original-bg-image is smaller than the parent container's available space.
	if (
		originalImgDimension.height < parentContainerDimension.height ||
		originalImgDimension.width < parentContainerDimension.width
	) {
		return {
			isBackgroundVisible: true,
			message: null,
		};
	}

	// default case
	return {
		isBackgroundVisible: true,
		message: null,
	};
};
