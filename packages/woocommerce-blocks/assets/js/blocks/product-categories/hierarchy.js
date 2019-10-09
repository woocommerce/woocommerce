/**
 * Returns terms in a tree form.
 *
 * @param {Array} list  Array of terms in flat format.
 *
 * @return {Array} Array of terms in tree format.
 */
export function buildTermsTree( list = [] ) {
	// Group terms by the parent ID.
	const termsByParent = list.reduce( ( r, v, i, a, k = v.parent ) => ( ( r[ k ] || ( r[ k ] = [] ) ).push( v ), r ), {} );

	const fillWithChildren = ( terms ) => {
		return terms.map( ( term ) => {
			const children = termsByParent[ term.term_id ];
			delete termsByParent[ term.term_id ];
			return {
				...term,
				children: children && children.length ? fillWithChildren( children ) : [],
			};
		} );
	};

	const tree = fillWithChildren( termsByParent[ '0' ] || [] );
	delete termsByParent[ '0' ];

	Object.keys( termsByParent ).forEach( function( terms ) {
		tree.push( ...fillWithChildren( terms || [] ) );
	} );

	return tree;
}
