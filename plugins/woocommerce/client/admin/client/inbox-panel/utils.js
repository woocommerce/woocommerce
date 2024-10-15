/**
 * External dependencies
 */
import { filter } from 'lodash';
import GraphemeSplitter from 'grapheme-splitter';

/**
 * Get the count of the unread notes from the received list.
 *
 * @param {Array}  notes    - List of notes, contains read and unread notes.
 * @param {number} lastRead - The timestamp that the user read a note.
 * @return {number} - Unread notes count.
 */
export function getUnreadNotesCount( notes, lastRead ) {
	const unreadNotes = filter( notes, ( note ) => {
		const {
			is_deleted: isDeleted,
			date_created_gmt: dateCreatedGmt,
			status,
		} = note;
		if ( ! isDeleted ) {
			const unread =
				! lastRead ||
				! dateCreatedGmt ||
				new Date( dateCreatedGmt + 'Z' ).getTime() > lastRead;
			return unread && status === 'unactioned';
		}
	} );
	return unreadNotes.length;
}

/**
 * Verifies if there are any valid notes in the list.
 *
 * @param {Array} notes - List of notes, contains read and unread notes.
 * @return {boolean} - Whether there are valid notes or not.
 */
export function hasValidNotes( notes ) {
	const validNotes = filter( notes, ( note ) => {
		const { is_deleted: isDeleted } = note;
		return ! isDeleted;
	} );
	return validNotes.length > 0;
}

/**
 * Truncates array of text characters.
 *
 * @param {Array}  letters   The letter array to truncate.
 * @param {number} limit     number of characters to limit to
 * @param {string} separator The separator string to truncate to.
 */
export const truncate = ( letters, limit, separator = ' ' ) => {
	let truncatedLetters = letters.slice( 0, limit );

	if ( letters.indexOf( separator, limit ) !== limit ) {
		// If there's a space in the text, we need to truncate at the space to preserve whole words.
		const index = truncatedLetters.lastIndexOf( separator );
		if ( index > -1 ) {
			truncatedLetters = truncatedLetters.slice( 0, index );
		}
	}
	return truncatedLetters.join( '' );
};

/**
 * Truncates characters inside of an element.
 * Currently does not count <br> as a character even though it should.
 *
 * @param {HTMLElement} element HTML element
 * @param {number}      limit   number of characters to limit to
 */
const truncateElement = ( element, limit ) => {
	const truncatedNode = document.createElement( 'div' );
	const childNodes = Array.from( element.childNodes );
	const splitter = new GraphemeSplitter();
	let truncatedTextLength = 0;

	for ( let i = 0; i < childNodes.length; i++ ) {
		// Deep clone.
		let clone = childNodes[ i ].cloneNode( true );
		const cloneNodeLetters = splitter.splitGraphemes( clone.textContent );

		if ( truncatedTextLength + cloneNodeLetters.length <= limit ) {
			// No problem including a whole child node, no need to consider truncating at all.
			truncatedNode.appendChild( clone );
			truncatedTextLength += cloneNodeLetters.length;
			continue;
		}

		const charactersRemaining = limit - truncatedTextLength;
		if ( clone.hasChildNodes() ) {
			clone = truncateElement( clone, charactersRemaining );
		} else {
			clone.textContent = truncate(
				cloneNodeLetters,
				charactersRemaining
			);
		}
		truncatedNode.appendChild( clone );
		// Exceeded limit at this point, safe to exit loop.
		break;
	}
	return truncatedNode;
};

/**
 * Truncates characters from a HTML string excluding markup. Truncated strings will be appended with ellipsis.
 *
 * @param {string} originalHTML HTML string
 * @param {number} limit        number of characters to limit to
 */
export const truncateRenderableHTML = ( originalHTML, limit ) => {
	const tempNode = document.createElement( 'div' );
	const splitter = new GraphemeSplitter();

	tempNode.innerHTML = originalHTML;
	if ( splitter.splitGraphemes( tempNode.textContent ).length > limit ) {
		return truncateElement( tempNode, limit ).innerHTML + '...';
	}
	return originalHTML;
};
