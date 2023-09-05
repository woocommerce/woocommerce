// Copy-pasted from https://github.com/brankosekulic/trimHtml/blob/master/index.js
// the published npm version of this code contains a bug that causes it throw exceptions.
export function trimHtml( html, options ) {
	options = options || {};

	const limit = options.limit || 100,
		preserveTags =
			typeof options.preserveTags !== 'undefined'
				? options.preserveTags
				: true,
		wordBreak =
			typeof options.wordBreak !== 'undefined'
				? options.wordBreak
				: false,
		suffix = options.suffix || '...',
		moreLink = options.moreLink || '',
		moreText = options.moreText || '»',
		preserveWhiteSpace = options.preserveWhiteSpace || false;

	const arr = html
		.replace( /</g, '\n<' )
		.replace( />/g, '>\n' )
		.replace( /\n\n/g, '\n' )
		.replace( /^\n/g, '' )
		.replace( /\n$/g, '' )
		.split( '\n' );

	let sum = 0,
		row,
		cut,
		add,
		rowCut,
		tagMatch,
		tagName,
		// eslint-disable-next-line prefer-const
		tagStack = [],
		more = false;

	for ( let i = 0; i < arr.length; i++ ) {
		row = arr[ i ];

		// count multiple spaces as one character
		if ( ! preserveWhiteSpace ) {
			rowCut = row.replace( /[ ]+/g, ' ' );
		} else {
			rowCut = row;
		}

		if ( ! row.length ) {
			continue;
		}

		const charArr = getCharArr( rowCut );

		if ( row[ 0 ] !== '<' ) {
			if ( sum >= limit ) {
				row = '';
			} else if ( sum + charArr.length >= limit ) {
				cut = limit - sum;

				if ( charArr[ cut - 1 ] === ' ' ) {
					while ( cut ) {
						cut -= 1;
						if ( charArr[ cut - 1 ] !== ' ' ) {
							break;
						}
					}
				} else {
					add = charArr.slice( cut ).indexOf( ' ' );

					// break on halh of word
					if ( ! wordBreak ) {
						if ( add !== -1 ) {
							cut += add;
						} else {
							cut = row.length;
						}
					}
				}

				row = charArr.slice( 0, cut ).join( '' ) + suffix;

				if ( moreLink ) {
					row +=
						'<a href="' +
						moreLink +
						'" style="display:inline">' +
						moreText +
						'</a>';
				}

				sum = limit;
				more = true;
			} else {
				sum += charArr.length;
			}
		} else if ( ! preserveTags ) {
			row = '';
		} else if ( sum >= limit ) {
			tagMatch = row.match( /[a-zA-Z]+/ );
			tagName = tagMatch ? tagMatch[ 0 ] : '';

			if ( tagName ) {
				if ( row.substring( 0, 2 ) !== '</' ) {
					tagStack.push( tagName );
					row = '';
				} else {
					while (
						tagStack[ tagStack.length - 1 ] !== tagName &&
						tagStack.length
					) {
						tagStack.pop();
					}

					if ( tagStack.length ) {
						row = '';
					}

					tagStack.pop();
				}
			} else {
				row = '';
			}
		}

		arr[ i ] = row;
	}

	return {
		html: arr.join( '\n' ).replace( /\n/g, '' ),
		more,
	};
}

// count symbols like one char
function getCharArr( rowCut ) {
	// eslint-disable-next-line prefer-const
	let charArr = [],
		subRow,
		match,
		char;

	for ( let i = 0; i < rowCut.length; i++ ) {
		subRow = rowCut.substring( i );
		match = subRow.match( /^&[a-z0-9#]+;/ );

		if ( match ) {
			char = match[ 0 ];
			charArr.push( char );
			i += char.length - 1;
		} else {
			charArr.push( rowCut[ i ] );
		}
	}

	return charArr;
}
