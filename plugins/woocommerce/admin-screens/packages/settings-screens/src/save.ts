/**
 * External dependencies
 */
import { store as coreStore, useEntityRecord } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { NO_KEY, toError } from './definition';
import type {
	ResolutionSelectors,
	SettingsScreen,
	SettingsRecord,
} from './definition';

interface ClearEditsActions {
	clearEntityRecordEdits: ( kind: string, name: string, key: string ) => void;
}

export interface SaveNotice {
	status: 'success' | 'error';
	message: string;
}

/**
 * Edit, save and discard the settings entity of a screen.
 */
export function useSettingsEntity( screen: SettingsScreen ) {
	const { kind, name } = screen.entity;
	const [ notice, setNotice ] = useState< SaveNotice >();
	const {
		editedRecord,
		hasEdits: isDirty,
		edit: editRecord,
		save: saveRecord,
	} = useEntityRecord< SettingsRecord >( kind, name, NO_KEY );
	const { isSaving, resolutionError } = useSelect(
		( select ) => {
			const core = select( coreStore );
			return {
				isSaving: core.isSavingEntityRecord( kind, name, NO_KEY ),
				resolutionError: (
					core as unknown as ResolutionSelectors
				 ).getResolutionError( 'getEntityRecord', [
					kind,
					name,
					NO_KEY,
				] ),
			};
		},
		[ kind, name ]
	);
	// clearEntityRecordEdits ships with the Fields API this screen requires, but the pinned
	// core-data types predate it.
	const { clearEntityRecordEdits } = useDispatch(
		coreStore
	) as unknown as ClearEditsActions;

	const edit = ( edits: SettingsRecord ) => {
		if ( isSaving ) {
			return;
		}
		setNotice( undefined );
		editRecord( edits );
	};

	const save = async () => {
		if ( isSaving || ! isDirty ) {
			return;
		}
		setNotice( undefined );
		try {
			// core-data stores the record the server returns, so the page shows what was saved.
			await saveRecord();
			setNotice( {
				status: 'success',
				message: __( 'Settings saved.', 'woocommerce' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: toError(
					error,
					__( 'Unable to save settings.', 'woocommerce' )
				).message,
			} );
		}
	};

	const discard = () => {
		setNotice( undefined );
		clearEntityRecordEdits( kind, name, NO_KEY );
	};

	return {
		data: editedRecord as SettingsRecord | undefined,
		isDirty,
		isSaving,
		loadError: resolutionError ? toError( resolutionError ) : undefined,
		notice,
		clearNotice: () => {
			setNotice( undefined );
		},
		edit,
		save,
		discard,
	};
}
