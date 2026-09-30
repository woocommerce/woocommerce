// json2md only escapes the first unescaped pipe per cell; escape them all so
// values like `shipping|billing|other` don't add phantom table columns.
const cell = ( text ) => String( text ).replace( /\|/g, '\\|' );

const params = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const paramDocs =
		tags.filter( ( { name: tagName } ) => tagName === 'param' ) || [];

	return paramDocs && paramDocs.length
		? {
				table: {
					headers: [ 'Argument', 'Type', 'Description' ],
					rows: [
						...paramDocs.map(
							( { variable, types, content }, index ) => [
								variable ? cell( variable ) : index + 1,
								cell( types.join( ', ' ) ),
								cell( content ),
							]
						),
					],
				},
		  }
		: null;
};

module.exports = { params };
