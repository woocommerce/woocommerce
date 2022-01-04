const related = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const seeDocs =
		tags.filter( ( { name: tagName } ) => tagName === 'see' ) || [];

	return seeDocs && seeDocs.length
		? {
				ul: seeDocs.map( ( { refers, content = '' } ) => {
					return content ? refers + ' - ' + content : refers;
				} ),
		  }
		: null;
};

module.exports = { related };
