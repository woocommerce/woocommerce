/**
 * External dependencies.
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';


export const AddNote = () => {
	const [ isAdding, setIsAdding ] = useState( false );
	const [ hasAdded, setHasAdded ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( false );

	async function triggerAddNote() {
		setIsAdding( true );
		setHasAdded( false );
		setErrorMessage( false );

		const name = prompt( 'Enter the note name' );
		if ( ! name ) {
			setIsAdding( false );
			return;
		}

		const title = prompt( 'Enter the note title' );
		if ( ! title ) {
			setIsAdding( false );
			return;
		}

		try {
			await apiFetch( {
				path: '/wc-admin-test-helper/admin-notes/add-note/v1',
				method: 'POST',
				data: {
					name,
					title,
				},
			} );
			setHasAdded( true );
		}
		catch ( ex ) {
			setErrorMessage( ex.message );
		}

		setIsAdding( false );
	}

	return (
		<>
			<p><strong>Add a note</strong></p>
			<p>
				This will add a new note. Currently only the note name
				and title will be used to create the note.
				<br/>
				<Button
					onClick={ triggerAddNote }
					disabled={ isAdding }
					isPrimary
				>
					Add admin note
				</Button>
				<br/>
				<span className="woocommerce-admin-test-helper__action-status">
					{ isAdding && 'Adding, please wait' }
					{ hasAdded && 'Note added' }
					{ errorMessage && (
						<>
							<strong>Error:</strong> { errorMessage }
						</>
					) }
				</span>
			</p>
		</>
	);
};
