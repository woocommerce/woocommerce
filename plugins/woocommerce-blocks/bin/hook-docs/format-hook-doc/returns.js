const returns = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const returnDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'return' )[ 0 ] ||
		undefined;

	return returnDoc
		? {
				p: `\`${ returnDoc.types.join( ', ' ) }\` ${
					returnDoc.content
				}`,
		  }
		: null;
};

module.exports = { returns };
