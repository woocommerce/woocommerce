/** @format */

/**
 * Internal dependencies
 */
import categories from './categories';
import customers from './customers';
import notes from './notes';
import orders from './orders';
import reportItems from './reports/items';
import reportStats from './reports/stats';
import reviews from './reviews';
import user from './user';

function createWcApiSpec() {
	return {
		mutations: {
			...user.mutations,
		},
		selectors: {
			...categories.selectors,
			...customers.selectors,
			...notes.selectors,
			...orders.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
			...reviews.selectors,
			...user.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...categories.operations.read( resourceNames ),
					...customers.operations.read( resourceNames ),
					...notes.operations.read( resourceNames ),
					...orders.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
					...reviews.operations.read( resourceNames ),
					...user.operations.read( resourceNames ),
				];
			},
			update( resourceNames, data ) {
				return [ ...user.operations.update( resourceNames, data ) ];
			},
		},
	};
}

export default createWcApiSpec();
