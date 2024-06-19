const exceptions = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const throwsDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'throws' )[ 0 ] ||
		undefined;

	return throwsDoc
		? {
				p: `\`${ throwsDoc.types.join( ', ' ) }\` ${
					throwsDoc.content
				}`,
		  }
		: null;
};

module.exports = { exceptions };
