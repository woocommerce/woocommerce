/**
 * Internal dependencies
 */
import reportExport from './export';
import items from './items';
import imports from './imports';
import reportItems from './reports/items';
import reportStats from './reports/stats';

function createWcApiSpec() {
	return {
		name: 'wcApi',
		mutations: {
			...reportExport.mutations,
			...items.mutations,
		},
		selectors: {
			...imports.selectors,
			...items.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
		},
		operations: {
			read( resourceNames ) {
				if ( document.hidden ) {
					// Don't do any read updates while the tab isn't active.
					return [];
				}

				return [
					...imports.operations.read( resourceNames ),
					...items.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
				];
			},
			update( resourceNames, data ) {
				return [
					...reportExport.operations.update( resourceNames, data ),
					...items.operations.update( resourceNames, data ),
				];
			},
			updateLocally( resourceNames, data ) {
				return [
					...items.operations.updateLocally( resourceNames, data ),
				];
			},
		},
	};
}

export default createWcApiSpec();
