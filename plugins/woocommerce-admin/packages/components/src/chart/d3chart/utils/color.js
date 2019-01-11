/** @format */

/**
 * External dependencies
 */
import { findIndex } from 'lodash';

export const getColor = ( key, params ) => {
	const smallColorScales = [
		[],
		[ 0.5 ],
		[ 0.333, 0.667 ],
		[ 0.2, 0.5, 0.8 ],
		[ 0.12, 0.375, 0.625, 0.88 ],
	];
	let keyValue = 0;
	const len = params.orderedKeys.length;
	const idx = findIndex( params.orderedKeys, d => d.key === key );
	if ( len < 5 ) {
		keyValue = smallColorScales[ len ][ idx ];
	} else {
		keyValue = idx / ( params.orderedKeys.length - 1 );
	}
	return params.colorScheme( keyValue );
};
