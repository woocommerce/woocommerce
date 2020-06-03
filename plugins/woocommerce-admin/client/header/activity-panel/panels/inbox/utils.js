/**
 * External dependencies
 */
import { filter } from 'lodash';

/**
 * Get the count of the unread notes.
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
		} = note;
		if ( ! isDeleted ) {
			return (
				! lastRead ||
				! dateCreatedGmt ||
				new Date( dateCreatedGmt + 'Z' ).getTime() > lastRead
			);
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
