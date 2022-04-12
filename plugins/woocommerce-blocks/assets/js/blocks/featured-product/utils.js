export function calculateBackgroundImagePosition( coords ) {
	if ( ! coords ) return {};

	const x = Math.round( coords.x * 100 );
	const y = Math.round( coords.y * 100 );

	return {
		objectPosition: `${ x }% ${ y }%`,
	};
}

/**
 * Convert the selected ratio to the correct background class.
 *
 * @param {number} ratio Selected opacity from 0 to 100.
 * @return {string?} The class name, if applicable (not used for ratio 0 or 50).
 */
export function dimRatioToClass( ratio ) {
	return ratio === 0 || ratio === 50
		? null
		: `has-background-dim-${ 10 * Math.round( ratio / 10 ) }`;
}
