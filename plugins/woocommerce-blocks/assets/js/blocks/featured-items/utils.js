export function calculateBackgroundImagePosition( coords ) {
	if ( ! coords ) return {};

	return {
		objectPosition: calculatePercentPositionFromCoordinates( coords ),
	};
}

export function calculatePercentPositionFromCoordinates( coords ) {
	if ( ! coords ) return '';

	const x = Math.round( coords.x * 100 );
	const y = Math.round( coords.y * 100 );

	return `${ x }% ${ y }%`;
}

/**
 * Generate the style object of the background image of the block.
 *
 * It outputs styles for either an `img` element or a `div` with a background,
 * depending on what is needed.
 *
 * @param {Object} opts Options for the element.
 * @param {Object} opts.focalPoint X and Y coordinates of the image.
 * @param {'cover' | 'none'} opts.imageFit How to fit the image in the wrapper.
 * @param {boolean} opts.isImgElement Whether the rendered background is an `img` element.
 * @param {boolean} opts.isRepeated Whether the background is repeated (no effect if `isImgElement` is `true`).
 * @param {string} opts.url The url of the image.
 *
 * @return {Object} A style object with a backgroundImage set (if a valid image is provided).
 */
export function getBackgroundImageStyles( {
	focalPoint,
	imageFit,
	isImgElement,
	isRepeated,
	url,
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
			backgroundPosition: calculatePercentPositionFromCoordinates(
				focalPoint
			),
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
 *
 * @param {string} blockName The name of the block.
 * @return {string} The prefix for the HTML elements belonging to that block.
 */
export function getClassPrefixFromName( blockName ) {
	return `wc-block-${ blockName.split( '/' )[ 1 ] }`;
}

/**
 * Convert the selected ratio to the correct background class.
 *
 * @param {number} ratio Selected opacity from 0 to 100.
 * @return {string} The class name, if applicable (not used for ratio 0 or 50).
 */
export function dimRatioToClass( ratio ) {
	return ratio === 0 || ratio === 50
		? null
		: `has-background-dim-${ 10 * Math.round( ratio / 10 ) }`;
}
