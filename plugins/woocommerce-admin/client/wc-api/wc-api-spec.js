/** @format */

/**
 * Internal dependencies
 */
import items from './items';
import notes from './notes';
import products from './products';
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
			...items.selectors,
			...notes.selectors,
			...products.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
			...reviews.selectors,
			...settings.selectors,
			...user.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...items.operations.read( resourceNames ),
					...notes.operations.read( resourceNames ),
					...products.operations.read( resourceNames ),
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
