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
 * Edit, save and discard the settings entity of a screen.
 */
export function useSettingsEntity( screen: PaymentSettingsScreen ) {
	const { kind, name } = screen.entity;
	const [ notice, setNotice ] = useState< SaveNotice >();
	const { record, data, isDirty, isSaving, loadError } = useSelect(
		( select ) => {
			const core = select( coreStore );
			return {
				record: core.getEntityRecord( kind, name, NO_KEY ),
				data: core.getEditedEntityRecord( kind, name, NO_KEY ) as
					SettingsRecord | undefined,
				isDirty: core.hasEditsForEntityRecord( kind, name, NO_KEY ),
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
	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const { invalidateResolution } = useDispatch(
		coreStore
	) as unknown as ResolutionActions;

	const edit = ( edits: SettingsRecord ) => {
		if ( isSaving ) {
			return;
		}
		setNotice( undefined );
		void editEntityRecord( kind, name, NO_KEY, edits );
	};

	const save = async () => {
		if ( isSaving || ! isDirty ) {
			return;
		}
		setNotice( undefined );
		setNotice(
			await saveAndReload( {
				save: () =>
					saveEditedEntityRecord( kind, name, NO_KEY, {
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
		if ( record ) {
			void editEntityRecord( kind, name, NO_KEY, record );
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
