const identifier = `<!-- comments-aggregator -->`;
const separator = '<!-- separator -->';
const footerText =
	'[comments-aggregator](https://github.com/woocommerce/woocommerce-blocks/tree/trunk/.github/comments-aggregator)';
const footer = `\n> <sub>${ footerText }</sub>\n${ identifier }`;

function getSectionId( section ) {
	const match = section.match( /-- section-id: ([^\s]+) --/ );
	return match ? match[ 1 ] : null;
}

function getSectionOrder( section ) {
	const match = section.match( /-- section-order: ([^\s]+) --/ );
	return match ? match[ 1 ] : null;
}

function parseComment( comment ) {
	if ( ! comment ) {
		return [];
	}
	const sections = comment.split( separator );
	return sections
		.map( ( section ) => {
			const sectionId = getSectionId( section );
			const order = getSectionOrder( section );
			/**
			 * This also remove the footer as it doesn't have a section id. This
			 * is intentional as we want the footer to always be the last
			 * section.
			 */
			if ( ! sectionId ) {
				return null;
			}
			return {
				id: sectionId,
				order: parseInt( order, 10 ),
				content: section.trim(),
			};
		} )
		.filter( Boolean );
}

function updateSection( sections, data ) {
	const { sectionId, content, order } = data;
	const index = sections.findIndex( ( section ) => section.id === sectionId );
	const formattedContent = `<!-- section-id: ${ sectionId } -->\n\n<!-- section-order: ${ order } -->\n\n${ content }`;
	if ( index === -1 ) {
		sections.push( {
			id: sectionId,
			content: formattedContent,
		} );
	} else {
		sections[ index ].content = formattedContent;
	}

	return sections;
}

function appendFooter( sections ) {
	return sections.concat( {
		id: 'footer',
		content: footer,
	} );
}

function sortSections( sections ) {
	return sections.sort( ( a, b ) => a.order - b.order );
}

function combineSections( sections ) {
	return sections
		.map( ( section ) => section.content )
		.join( `\n\n${ separator }\n\n` );
}

exports.updateComment = function ( comment, data ) {
	let sections = parseComment( comment );
	sections = updateSection( sections, data );
	sections = sortSections( sections );
	sections = appendFooter( sections );
	return combineSections( sections );
};

exports.isMergedComment = function ( comment ) {
	return (
		comment.body.includes( identifier ) &&
		comment.user.login === 'github-actions[bot]'
	);
};
