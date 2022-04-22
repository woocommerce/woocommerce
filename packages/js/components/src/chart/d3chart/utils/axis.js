/**
 * Internal dependencies
 */
import { drawXAxis } from './axis-x';
import { drawYAxis } from './axis-y';

export const drawAxis = ( node, params, scales, formats, margin, isRTL ) => {
	drawXAxis( node, params, scales, formats );
	drawYAxis( node, scales, formats, margin, isRTL );

	node.selectAll( '.domain' ).remove();
	node.selectAll( '.axis .tick line' ).remove();
};
