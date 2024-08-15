export const decodeHTMLEntities = ( text ) => {
	const parser = new window.DOMParser();
	const decodedString = parser.parseFromString( text, 'text/html' )
		.documentElement.textContent;
	return decodedString;
};

export const recursivelyTransformLabels = ( node, transform ) => {
	if ( Array.isArray( node ) ) {
		return node.map( ( element ) => {
			return recursivelyTransformLabels( element, transform );
		} );
	}
	if ( node.label ) {
		node.label = transform( node.label );
	}
	if ( node.children ) {
		node.children = recursivelyTransformLabels( node.children, transform );
	}
	return node;
};
