'use strict';

/**
 * Normalize raw docblock Markdown for the generated docs.
 *
 * Docblock prose is soft-wrapped to fit the comment width, so consecutive
 * plain-text lines are joined into single-line paragraphs. List items keep
 * their own lines, with a blank line inserted between a paragraph and the
 * list that follows it (CommonMark needs it for reliable list parsing, and
 * markdownlint's MD032 requires it). Fenced code blocks pass through
 * untouched. Nested list indentation is not preserved — the same flat-list
 * limitation as json2md.
 */

const isListItem = ( line ) => /^([-*+]|\d+[.)])\s/.test( line );

const getFenceMarker = ( line ) => {
	const match = line.match( /^(`{3,}|~{3,})/ );
	return match ? match[ 1 ] : null;
};

const docblockToMarkdown = ( text ) => {
	if ( ! text ) {
		return text;
	}

	const out = [];
	// Whether the last emitted line is prose that a soft-wrapped
	// continuation line should be joined onto.
	let joinable = false;
	// The delimiter run that opened the current code fence, if any.
	let openFence = null;

	for ( const rawLine of String( text ).split( '\n' ) ) {
		const line = rawLine.trimEnd();
		const trimmed = line.trim();

		if ( openFence ) {
			// Per CommonMark, only a bare marker of the same character
			// with at least the opening length closes the fence; any
			// other line is code-block content.
			const closeMarker = getFenceMarker( trimmed );
			if (
				closeMarker === trimmed &&
				closeMarker[ 0 ] === openFence[ 0 ] &&
				closeMarker.length >= openFence.length
			) {
				openFence = null;
				out.push( trimmed );
				joinable = false;
			} else {
				out.push( line );
			}
			continue;
		}
		const fenceMarker = getFenceMarker( trimmed );
		if ( fenceMarker ) {
			openFence = fenceMarker;
			out.push( trimmed );
			joinable = false;
			continue;
		}
		if ( ! trimmed ) {
			if ( out.length && out[ out.length - 1 ] !== '' ) {
				out.push( '' );
			}
			joinable = false;
			continue;
		}
		if ( isListItem( trimmed ) ) {
			const prev = out.length ? out[ out.length - 1 ] : '';
			if ( prev && ! isListItem( prev ) ) {
				out.push( '' );
			}
			out.push( trimmed );
			joinable = true;
		} else if ( joinable ) {
			out[ out.length - 1 ] += ` ${ trimmed }`;
		} else {
			out.push( trimmed );
			joinable = true;
		}
	}

	return out.join( '\n' ).trim();
};

module.exports = { docblockToMarkdown };
