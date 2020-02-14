/**
 * Internal dependencies
 */
import { colorScales, selectionLimit } from '../../constants';

/**
 * External dependencies
 */
import { findIndex } from 'lodash';

export const getColor = ( orderedKeys, colorScheme ) => ( key ) => {
	const len =
		orderedKeys.length > selectionLimit
			? selectionLimit
			: orderedKeys.length;
	const idx = findIndex( orderedKeys, ( d ) => d.key === key );
	const keyValue = idx <= selectionLimit - 1 ? colorScales[ len ][ idx ] : 0;
	return colorScheme( keyValue );
};
