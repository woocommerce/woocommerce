/** @format */

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

const updateItem = operations => ( type, id, itemData ) => {
	const resourceName = getResourceName( `items-query-${ type }-item`, id );
	operations.update( [ resourceName ], { [ resourceName ]: { id, ...itemData } } );
};

export default {
	updateItem,
};
