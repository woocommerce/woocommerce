/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { pickEdits } from './page-edits';
import { saveAndReload } from './save-and-reload';
import type { SaveNotice } from './save-and-reload';
import { NO_KEY } from './screen';
import type { PaymentSettingsScreen, SettingsRecord } from './screen';

// Every store has these metadata selectors and actions, but core-data's types leave them out.
interface ResolutionSelectors {
	getResolutionError: ( selector: string, args: unknown[] ) => unknown;
}
interface ResolutionActions {
	invalidateResolution: (
		selector: string,
		args: unknown[]
	) => Promise< void >;
}

/**
 * Edit, save and discard the settings entity of a screen, one page at a time.
 *
 * Save and Discard only cover the current page: its fields, and any other values its edits changed,
 * such as a field whose `setValue` writes to another key. Each page works as its own form.
 */
export function useSettingsEntity(
	screen: PaymentSettingsScreen,
	fieldIds: string[]
) {
	const { kind, name } = screen.entity;
	const [ notice, setNotice ] = useState< SaveNotice >();
	const [ editedKeys, setEditedKeys ] = useState< string[] >( [] );
	const { record, data, edits, isSaving, loadError } = useSelect(
		( select ) => {
			const core = select( coreStore );
			return {
				record: core.getEntityRecord( kind, name, NO_KEY ),
				data: core.getEditedEntityRecord( kind, name, NO_KEY ) as
					SettingsRecord | undefined,
				edits: core.getEntityRecordNonTransientEdits(
					kind,
					name,
					NO_KEY
				) as SettingsRecord | undefined,
				isSaving: core.isSavingEntityRecord( kind, name, NO_KEY ),
				loadError: (
					core as unknown as ResolutionSelectors
				 ).getResolutionError( 'getEntityRecord', [
					kind,
					name,
					NO_KEY,
				] ) as Error | undefined,
			};
		},
		[ kind, name ]
	);
	const { editEntityRecord, saveEntityRecord } = useDispatch( coreStore );
	const { invalidateResolution } = useDispatch(
		coreStore
	) as unknown as ResolutionActions;
	const pageEdits = pickEdits( edits ?? {}, [ ...fieldIds, ...editedKeys ] );
	const isDirty = Object.keys( pageEdits ).length > 0;

	const edit = ( changes: SettingsRecord ) => {
		if ( isSaving ) {
			return;
		}
		setNotice( undefined );
		setEditedKeys( ( keys ) => [
			...new Set( [ ...keys, ...Object.keys( changes ) ] ),
		] );
		void editEntityRecord( kind, name, NO_KEY, changes );
	};

	const save = async () => {
		if ( isSaving || ! isDirty ) {
			return;
		}
		setNotice( undefined );
		setNotice(
			await saveAndReload( {
				save: () =>
					saveEntityRecord( kind, name, pageEdits, {
						throwOnError: true,
					} ),
				reload: async () => {
					await invalidateResolution( 'getEntityRecord', [
						kind,
						name,
						NO_KEY,
					] );
					// Selecting the record again resolves it from the server.
				},
			} )
		);
	};

	const discard = () => {
		setNotice( undefined );
		if ( record && isDirty ) {
			void editEntityRecord(
				kind,
				name,
				NO_KEY,
				pickEdits( record, Object.keys( pageEdits ) )
			);
		}
	};

	return {
		record,
		data,
		isDirty,
		isSaving,
		loadError,
		notice,
		clearNotice: () => {
			setNotice( undefined );
		},
		edit,
		save,
		discard,
	};
}
