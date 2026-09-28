/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { saveAndReload } from './save-and-reload';
import type { SaveNotice } from './save-and-reload';
import type { PaymentSettingsScreen, SettingsRecord } from './screen';

/**
 * Edit, save and discard the settings entity of a screen.
 */
export function useSettingsEntity( screen: PaymentSettingsScreen ) {
	const { kind, name } = screen.entity;
	const [ notice, setNotice ] = useState< SaveNotice >();
	const { record, data, isDirty, isSaving, loadError } = useSelect(
		( select ) => {
			const core = select( coreStore );
			return {
				record: core.getEntityRecord( kind, name, undefined ) as
					| SettingsRecord
					| undefined,
				data: core.getEditedEntityRecord( kind, name, undefined ) as
					| SettingsRecord
					| undefined,
				isDirty: core.hasEditsForEntityRecord( kind, name, undefined ),
				isSaving: core.isSavingEntityRecord( kind, name, undefined ),
				loadError: core.getResolutionError( 'getEntityRecord', [
					kind,
					name,
					undefined,
				] ) as Error | undefined,
			};
		},
		[ kind, name ]
	);
	const { editEntityRecord, saveEditedEntityRecord, invalidateResolution } =
		useDispatch( coreStore );

	const edit = ( edits: SettingsRecord ) => {
		if ( isSaving ) {
			return;
		}
		setNotice( undefined );
		void editEntityRecord( kind, name, undefined, edits );
	};

	const save = async () => {
		if ( isSaving || ! isDirty ) {
			return;
		}
		setNotice( undefined );
		setNotice(
			await saveAndReload( {
				save: () =>
					saveEditedEntityRecord( kind, name, undefined, {
						throwOnError: true,
					} ),
				reload: async () => {
					await invalidateResolution( 'getEntityRecord', [
						kind,
						name,
						undefined,
					] );
					// Selecting the record again resolves it from the server.
				},
			} )
		);
	};

	const discard = () => {
		setNotice( undefined );
		if ( record ) {
			void editEntityRecord( kind, name, undefined, record );
		}
	};

	return {
		record,
		data,
		isDirty,
		isSaving,
		loadError,
		notice,
		clearNotice: () => setNotice( undefined ),
		edit,
		save,
		discard,
	};
}
