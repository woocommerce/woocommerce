/**
 * Internal dependencies
 */
import reportExport from './export';
import items from './items';
import imports from './imports';
import notes from './notes';
import reportItems from './reports/items';
import reportStats from './reports/stats';
import reviews from './reviews';

function createWcApiSpec() {
	return {
		name: 'wcApi',
		mutations: {
			...reportExport.mutations,
			...items.mutations,
			...notes.mutations,
		},
		selectors: {
			...imports.selectors,
			...items.selectors,
			...notes.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
			...reviews.selectors,
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
					...notes.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
					...reviews.operations.read( resourceNames ),
				];
			},
			update( resourceNames, data ) {
				return [
					...reportExport.operations.update( resourceNames, data ),
					...items.operations.update( resourceNames, data ),
					...notes.operations.update( resourceNames, data ),
				];
			},
			remove( resourceNames, data ) {
				return [ ...notes.operations.remove( resourceNames, data ) ];
			},
			removeAll( resourceNames ) {
				return [ ...notes.operations.removeAll( resourceNames ) ];
			},
			updateLocally( resourceNames, data ) {
				return [
					...items.operations.updateLocally( resourceNames, data ),
				];
			},
			undoRemoveAll( resourceNames, data ) {
				return [
					...notes.operations.undoRemoveAll( resourceNames, data ),
				];
			},
		},
	};
}

export default createWcApiSpec();
