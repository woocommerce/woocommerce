/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const ListNotes = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ notes, setNotes ] = useState( [] );

	async function getNotes() {
		try {
			const _notes = await apiFetch( {
				path: '/wc-admin-test-helper/admin-notes/notes',
			} );
			setNotes( _notes );
		} catch ( ex ) {}

		setIsLoading( false );
	}

	return (
		<>
			<p>
				<strong>List notes</strong>
			</p>
			<div>
				<br />
				<div className="woocommerce-admin-test-helper__add-notes">
					<Button
						onClick={ getNotes }
						disabled={ isLoading }
						isPrimary
					>
						List all notes
					</Button>
				</div>
				<div>
					{ notes && notes.length > 0 && (
						<>
							<br />
							<table className="wp-list-table striped table-view-list widefat">
								<thead>
									<tr>
										<th>note_id</th>
										<th>name</th>
										<th>type</th>
										<th>status</th>
									</tr>
								</thead>
								<tbody>
									{ notes.map( ( note ) => (
										<tr key={ note.note_id }>
											<td>{ note.note_id }</td>
											<td>{ note.name }</td>
											<td>{ note.type }</td>
											<td>{ note.status }</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</>
					) }
				</div>
			</div>
		</>
	);
};
