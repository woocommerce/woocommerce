/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const AddNote = () => {
	const [ isAdding, setIsAdding ] = useState( false );
	const [ hasAdded, setHasAdded ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( false );
	const [ noteType, setNoteType ] = useState( 'info' );
	const [ noteLayout, setNoteLayout ] = useState( 'plain' );

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
					type: noteType,
					layout: noteLayout,
					title,
				},
			} );
			setHasAdded( true );
		} catch ( ex ) {
			setErrorMessage( ex.message );
		}

		setIsAdding( false );
	}

	function onTypeChange( val ) {
		setNoteType( val );
		if ( val !== 'info' ) {
			setNoteLayout( 'plain' );
		}
	}

	function onLayoutChange( val ) {
		setNoteLayout( val );
	}

	function getAddNoteDescription() {
		switch ( noteType ) {
			case 'email':
				return (
					<>
						This will add a new <strong>email</strong> note. Enable
						email insights{ ' ' }
						<a href="/wp-admin/admin.php?page=wc-settings&tab=email">
							here
						</a>{ ' ' }
						and run the cron to send the note by email.
					</>
				);
			default:
				return (
					<>
						This will add a new note. Currently only the note name
						and title will be used to create the note.
					</>
				);
		}
	}

	return (
		<>
			<p>
				<strong>Add a note</strong>
			</p>
			<div>
				{ getAddNoteDescription() }
				<br />
				<div className="woocommerce-admin-test-helper__add-notes">
					<Button
						onClick={ triggerAddNote }
						disabled={ isAdding }
						isPrimary
					>
						Add admin note
					</Button>
					<SelectControl
						label="Type"
						onChange={ onTypeChange }
						labelPosition="side"
						options={ [
							{ label: 'Info', value: 'info' },
							{ label: 'Update', value: 'update' },
							{ label: 'Email', value: 'email' },
						] }
						value={ noteType }
					/>
					<SelectControl
						label="Layout"
						onChange={ onLayoutChange }
						labelPosition="side"
						options={ [
							{ label: 'Plain', value: 'plain' },
							{ label: 'Banner', value: 'banner' },
							{ label: 'Thumbnail', value: 'thumbnail' },
						] }
						disabled={ noteType !== 'info' }
						value={ noteLayout }
					/>
				</div>
				<br />
				<span className="woocommerce-admin-test-helper__action-status">
					{ isAdding && 'Adding, please wait' }
					{ hasAdded && 'Note added' }
					{ errorMessage && (
						<>
							<strong>Error:</strong> { errorMessage }
						</>
					) }
				</span>
			</div>
		</>
	);
};
