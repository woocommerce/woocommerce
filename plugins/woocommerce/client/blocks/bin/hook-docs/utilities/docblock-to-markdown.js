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

const isFenceMarker = ( line ) =>
	line.startsWith( '```' ) || line.startsWith( '~~~' );

const docblockToMarkdown = ( text ) => {
	if ( ! text ) {
		return text;
	}

	const out = [];
	// Whether the last emitted line is prose that a soft-wrapped
	// continuation line should be joined onto.
	let joinable = false;
	let inFence = false;

	for ( const rawLine of String( text ).split( '\n' ) ) {
		const line = rawLine.trimEnd();
		const trimmed = line.trim();

		if ( isFenceMarker( trimmed ) ) {
			inFence = ! inFence;
			out.push( trimmed );
			joinable = false;
			continue;
		}
		if ( inFence ) {
			out.push( line );
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
