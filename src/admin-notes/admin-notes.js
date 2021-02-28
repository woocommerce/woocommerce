/**
 * Internal dependencies.
 */
import { DeleteAllNotes } from './delete-all-notes';

export const AdminNotes = () => {
    return (
        <>
            <h2>Admin notes</h2>
            <p>This section contains tools for managing admin notes.</p>
            <DeleteAllNotes/>
        </>
    );
};
