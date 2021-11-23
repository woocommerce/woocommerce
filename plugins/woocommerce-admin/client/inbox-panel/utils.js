/**
 * External dependencies
 */
import { filter, truncate } from 'lodash';

/**
 * Get the count of the unread notes from the received list.
 *
 * @param {Array} notes - List of notes, contains read and unread notes.
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
 * Truncates characters inside of an element.
 * Currently does not count <br> as a character even though it should.
 *
 * @param {HTMLElement} element HTML element
 * @param {number} limit number of characters to limit to
 */
const truncateElement = ( element, limit ) => {
	const truncatedNode = document.createElement( 'div' );
	const childNodes = Array.from( element.childNodes );
	for ( let i = 0; i < childNodes.length; i++ ) {
		// Deep clone.
		let clone = childNodes[ i ].cloneNode( true );
		if (
			truncatedNode.textContent.length + clone.textContent.length <=
			limit
		) {
			// No problem including a whole child node, no need to consider truncating at all.
			truncatedNode.appendChild( clone );
		} else {
			const charactersRemaining =
				limit - truncatedNode.textContent.length;
			if (
				! clone.innerHTML ||
				clone.textContent.slice( 0, charactersRemaining ) ===
					clone.innerHTML.slice( 0, charactersRemaining )
			) {
				// If text until the limit doesn't contain any markup, we're all good to truncate.
				clone.textContent = truncate( clone.textContent, {
					length: charactersRemaining,
					separator: ' ',
					omission: '',
				} );
			} else {
				// If it does, then we'd need to recursively run this with balance of characters remaining.
				clone = truncateElement( clone, charactersRemaining );
			}
			truncatedNode.appendChild( clone );
			// Exceeded limit at this point, safe to exit loop.
			break;
		}
	}
	return truncatedNode;
};

/**
 * Truncates characters from a HTML string excluding markup. Truncated strings will be appended with ellipsis.
 *
 * @param {string} originalHTML HTML string
 * @param {number} limit number of characters to limit to
 */
export const truncateRenderableHTML = ( originalHTML, limit ) => {
	const tempNode = document.createElement( 'div' );
	tempNode.innerHTML = originalHTML;
	if ( tempNode.textContent.length > limit ) {
		return truncateElement( tempNode, limit ).innerHTML + '...';
	}
	return originalHTML;
};
