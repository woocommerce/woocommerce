/** @format */

/**
 * Internal dependencies
 */
import notes from './notes';
import orders from './orders';
import reportItems from './reports/items';
import reportStats from './reports/stats';
import reviews from './reviews';

function createWcApiSpec() {
	return {
		selectors: {
			...notes.selectors,
			...orders.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
			...reviews.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...notes.operations.read( resourceNames ),
					...orders.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
					...reviews.operations.read( resourceNames ),
				];
			},
		},
	};
}

export default createWcApiSpec();
