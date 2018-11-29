/** @format */

/**
 * Internal dependencies
 */
import orders from './orders';

function createWcApiSpec() {
	return {
		selectors: {
			...orders.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [ ...orders.operations.read( resourceNames ) ];
			},
		},
	};
}

export default createWcApiSpec();
