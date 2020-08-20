/**
 * Internal dependencies
 */
import imports from './imports';

function createWcApiSpec() {
	return {
		name: 'wcApi',
		mutations: {},
		selectors: {
			...imports.selectors,
		},
		operations: {
			read( resourceNames ) {
				if ( document.hidden ) {
					// Don't do any read updates while the tab isn't active.
					return [];
				}

				return [ ...imports.operations.read( resourceNames ) ];
			},
			update() {
				return [];
			},
			updateLocally() {
				return [];
			},
		},
	};
}

export default createWcApiSpec();
