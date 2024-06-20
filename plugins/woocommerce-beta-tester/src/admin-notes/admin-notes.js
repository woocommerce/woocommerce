/**
 * Internal dependencies
 */
import { DeleteAllNotes } from './delete-all-notes';
import { AddNote } from './add-note';
import { ListNotes } from './list-notes';

export const AdminNotes = () => {
	return (
		<>
			<h2>Admin notes</h2>
			<p>This section contains tools for managing admin notes.</p>
			<AddNote />
			<DeleteAllNotes />
			<ListNotes />
		</>
	);
};
