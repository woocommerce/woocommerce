/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

const updateNote = ( operations ) => ( noteId, noteFields ) => {
	const resourceKey = 'note';
	operations.update( [ resourceKey ], {
		[ resourceKey ]: { noteId, ...noteFields },
	} );
};

const removeNote = ( operations ) => async ( noteId ) => {
	const { createNotice } = dispatch( 'core/notices' );
	const resourceKey = 'note';
	const resourceName = getResourceName( 'note', noteId );
	const result = await operations.remove( [ resourceKey ], {
		[ resourceKey ]: { noteId },
	} );

	const response = result[ 0 ]
		? result[ 0 ][ resourceName ]
		: { data: false, error: true };

	if ( response && response.data ) {
		createNotice(
			'success',
			__( 'Message dismissed.', 'woocommerce-admin' ),
			{
				actions: [
					{
						label: __( 'Undo', 'woocommerce-admin' ),
						onClick: () => {
							operations.update( [ resourceKey ], {
								[ resourceKey ]: { noteId, is_deleted: 0 },
							} );
						},
					},
				],
			}
		);
	}
	if ( response && response.error ) {
		createNotice(
			'error',
			__( 'Message could not be dismissed.', 'woocommerce-admin' )
		);
	}
};

const removeAllNotes = ( operations ) => async () => {
	const { createNotice } = dispatch( 'core/notices' );
	const resourceKey = 'note';
	const result = await operations.removeAll( [ resourceKey ] );

	const response = result ? result[ 0 ] : { error: true };

	if ( ! response.error ) {
		createNotice(
			'success',
			__( 'All messages dismissed.', 'woocommerce-admin' ),
			{
				actions: [
					{
						label: __( 'Undo', 'woocommerce-admin' ),
						onClick: () => {
							const notesIds = [];
							for ( const note in response ) {
								notesIds.push( response[ note ].data.id );
							}
							operations.undoRemoveAll( [ resourceKey ], {
								notesIds,
								is_deleted: 0,
							} );
						},
					},
				],
			}
		);
	} else {
		createNotice(
			'error',
			__( 'Messages could not be dismissed.', 'woocommerce-admin' )
		);
	}
};

const triggerNoteAction = ( operations ) => ( noteId, actionId ) => {
	const resourceKey = 'note-action';
	operations.update( [ resourceKey ], {
		[ resourceKey ]: { noteId, actionId },
	} );
};

export default {
	updateNote,
	removeNote,
	removeAllNotes,
	triggerNoteAction,
};
