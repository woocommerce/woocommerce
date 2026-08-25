// Angle-bracket autolinks, so markdownlint (MD034) doesn't flag bare URLs.
const linkify = ( text ) =>
	/^https?:\/\//.test( text ) ? `<${ text }>` : text;

const related = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const seeDocs =
		tags.filter( ( { name: tagName } ) => tagName === 'see' ) || [];

	return seeDocs && seeDocs.length
		? {
				ul: seeDocs.map( ( { refers, content = '' } ) => {
					const reference = linkify( refers );
					return content ? reference + ' - ' + content : reference;
				} ),
		  }
		: null;
};

module.exports = { related };
