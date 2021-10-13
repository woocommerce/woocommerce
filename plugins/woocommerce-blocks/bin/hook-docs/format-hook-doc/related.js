const related = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const seeDocs =
		tags.filter( ( { name: tagName } ) => tagName === 'see' ) || [];

	return seeDocs && seeDocs.length
		? {
				ul: seeDocs.map( ( { refers } ) => refers ),
		  }
		: null;
};

module.exports = { related };
