/**
 * External dependencies
 */
import { forEach, groupBy, keyBy } from 'lodash';

/**
 * Returns terms in a tree form.
 *
 * @param {Array} filteredList Array of terms, possibly a subset of all terms, in flat format.
 * @param {Array} list         Array of the full list of terms, defaults to the filteredList.
 *
 * @return {Array} Array of terms in tree format.
 */
export function buildTermsTree( filteredList, list = filteredList ) {
	const termsByParent = groupBy( filteredList, 'parent' );
	const listById = keyBy( list, 'id' );

	const getParentsName = ( term = {} ) => {
		if ( ! term.parent ) {
			return term.name ? [ term.name ] : [];
		}

		const parentName = getParentsName( listById[ term.parent ] );
		return [ ...parentName, term.name ];
	};

	const fillWithChildren = ( terms ) => {
		return terms.map( ( term ) => {
			const children = termsByParent[ term.id ];
			delete termsByParent[ term.id ];
			return {
				...term,
				breadcrumbs: getParentsName( listById[ term.parent ] ),
				children:
					children && children.length
						? fillWithChildren( children )
						: [],
			};
		} );
	};

	const tree = fillWithChildren( termsByParent[ '0' ] || [] );
	delete termsByParent[ '0' ];

	// anything left in termsByParent has no visible parent
	forEach( termsByParent, ( terms ) => {
		tree.push( ...fillWithChildren( terms || [] ) );
	} );

	return tree;
}
