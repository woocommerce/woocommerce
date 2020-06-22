/**
 * External dependencies
 */
import { filter } from 'lodash';

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
