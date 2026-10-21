/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import chequeFields from '../../../entity-fields/src/cheque';
import { ENTITY_KIND } from '../entities';
import { getEntityFields, registerEntityFields } from '../entity-fields';

// The editor's field registry is private, so this fails if WordPress renames or removes it.
it( 'registers and reads fields through the private editor field registry', () => {
	registerEntityFields( ENTITY_KIND, 'cheque', chequeFields );
	registerEntityFields( ENTITY_KIND, 'cheque', chequeFields );

	expect(
		getEntityFields( select( editorStore ), ENTITY_KIND, 'cheque' ).map(
			( { id } ) => id
		)
	).toEqual( [ 'enabled', 'title', 'description', 'instructions' ] );
} );
