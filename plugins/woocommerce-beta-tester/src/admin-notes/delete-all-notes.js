/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const DeleteAllNotes = () => {
	const [ isDeleting, setIsDeleting ] = useState( false );
	const [ deleteStatus, setDeleteStatus ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( false );

	async function triggerDeleteAllNotes() {
		setIsDeleting( true );
		setErrorMessage( false );
		setDeleteStatus( false );

		try {
			const response = await apiFetch( {
				path: '/wc-admin-test-helper/admin-notes/delete-all-notes/v1',
				method: 'POST',
			} );

			setDeleteStatus( response );
		} catch ( ex ) {
			setErrorMessage( ex.message );
		}

		setIsDeleting( false );
	}

	return (
		<>
			<p>
				<strong>Delete all admin notes</strong>
			</p>
			<p>
				This will delete all notes from the{ ' ' }
				<code>wp_wc_admin_notes</code>
				table, and actions from the{ ' ' }
				<code>wp_wc_admin_note_actions</code>
				table.
				<br />
				<Button
					onClick={ triggerDeleteAllNotes }
					disabled={ isDeleting }
					isPrimary
				>
					Delete all notes
				</Button>
				<br />
				<span className="woocommerce-admin-test-helper__action-status">
					{ isDeleting && 'Deleting, please wait.' }
					{ deleteStatus && (
						<>
							Deleted{ ' ' }
							<strong>{ deleteStatus.deleted_note_count }</strong>{ ' ' }
							admin notes and{ ' ' }
							<strong>
								{ deleteStatus.deleted_action_count }
							</strong>{ ' ' }
							actions.
						</>
					) }
					{ errorMessage && (
						<>
							<strong>Error: </strong>
							{ errorMessage }
						</>
					) }
				</span>
			</p>
		</>
	);
};
