/** @format */

/**
 * Internal dependencies
 */
import notes from './notes';
import orders from './orders';

function createWcApiSpec() {
	return {
		selectors: {
			...notes.selectors,
			...orders.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...notes.operations.read( resourceNames ),
					...orders.operations.read( resourceNames ),
				];
			},
		},
	};
}

export default createWcApiSpec();
