/** @format */

/**
 * Internal dependencies
 */
import customers from './customers';
import notes from './notes';
import orders from './orders';
import reportItems from './reports/items';
import reportStats from './reports/stats';

function createWcApiSpec() {
	return {
		selectors: {
			...customers.selectors,
			...notes.selectors,
			...orders.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...customers.operations.read( resourceNames ),
					...notes.operations.read( resourceNames ),
					...orders.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
				];
			},
		},
	};
}

export default createWcApiSpec();
