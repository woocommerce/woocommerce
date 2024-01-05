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
								variable ? variable : index + 1,
								types.join( ', ' ),
								content,
							]
						),
					],
				},
		  }
		: null;
};

module.exports = { params };
