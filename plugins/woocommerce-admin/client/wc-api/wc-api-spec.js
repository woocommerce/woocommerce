/** @format */

/**
 * Internal dependencies
 */
import categories from './categories';
import notes from './notes';
import orders from './orders';
import reportItems from './reports/items';
import reportStats from './reports/stats';
import reviews from './reviews';
import settings from './settings';
import user from './user';

function createWcApiSpec() {
	return {
		mutations: {
			...settings.mutations,
			...user.mutations,
		},
		selectors: {
			...categories.selectors,
			...notes.selectors,
			...orders.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
			...reviews.selectors,
			...settings.selectors,
			...user.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...categories.operations.read( resourceNames ),
					...notes.operations.read( resourceNames ),
					...orders.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
					...reviews.operations.read( resourceNames ),
					...settings.operations.read( resourceNames ),
					...user.operations.read( resourceNames ),
				];
			},
			update( resourceNames, data ) {
				return [
					...settings.operations.update( resourceNames, data ),
					...user.operations.update( resourceNames, data ),
				];
			},
		},
	};
}

export default createWcApiSpec();
