/**
 * External dependencies
 */
import { findIndex } from 'lodash';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { colorScales, selectionLimit } from '../../constants';

export const getColor = ( orderedKeys, colorScheme ) => ( key ) => {
	const len =
		orderedKeys.length > selectionLimit
			? selectionLimit
			: orderedKeys.length;
	const idx = findIndex( orderedKeys, ( d ) => d.key === key );

	/**
	 * Color to be used for a chart item.
	 *
	 * @filter woocommerce_admin_chart_item_color
	 * @example
	 * addFilter(
	 * 	'woocommerce_admin_chart_item_color',
	 * 	'example',
	 * ( idx ) => {
	 * 	const colorScales = [
	 *	  "#0A2F51",
	 *	  "#0E4D64",
	 *	  "#137177",
	 *	  "#188977",
	 *	];
	 * 	return colorScales[ idx ] || false;
	 * });
	 *
	 */
	const color = applyFilters(
		'woocommerce_admin_chart_item_color',
		idx,
		key,
		orderedKeys
	);

	if ( color && color.toString().startsWith( '#' ) ) {
		return color;
	}

	const keyValue = idx <= selectionLimit - 1 ? colorScales[ len ][ idx ] : 0;
	return colorScheme( keyValue );
};
