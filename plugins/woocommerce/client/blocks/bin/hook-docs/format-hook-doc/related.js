// Angle-bracket autolinks, so markdownlint (MD034) doesn't flag bare URLs.
const linkify = ( text ) =>
	/^https?:\/\//.test( text ) ? `<${ text }>` : text;

const related = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	// A malformed @see comes through as a `see` tag without `refers`;
	// skip those so the docs don't render a literal "undefined".
	const seeDocs = tags.filter(
		( { name: tagName, refers } ) => tagName === 'see' && refers
	);

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
