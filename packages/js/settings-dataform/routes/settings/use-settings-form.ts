/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { useFormValidity } from '@wordpress/dataviews';
import type { Field, Form } from '@wordpress/dataviews';
import { useMemo } from 'react';
import type { Settings } from '@woocommerce-settings-ui-experimental/settings-ui';

/**
 * Internal dependencies
 */
import { getDataFormFields, getFieldIds, getLegacyCards } from './legacy-adapter';
import type { LegacyConfig } from './legacy-adapter';
import type { LegacySettingsEntity, SettingsEntity } from './pages';

export type MainSettingsState = {
	entity: SettingsEntity;
	form?: Form;
	fields: Field< Settings >[];
	data: Settings;
	savedData: Settings;
	dirty: boolean;
	saving: boolean;
	saveError?: Error;
	loadError?: Error;
	ready: boolean;
};

export type LegacySettingsState = {
	entity: LegacySettingsEntity;
	record?: LegacyConfig;
	editedRecord?: LegacyConfig;
	ready: boolean;
	dirty: boolean;
	saving: boolean;
	saveError?: Error;
	loadError?: Error;
};

function getChangedValues(
	saved: Record< string, unknown >,
	edited: Record< string, unknown >
) {
	return Object.fromEntries( Object.entries( edited ).filter(
		( [ id, value ] ) => JSON.stringify( value ) !== JSON.stringify( saved[ id ] )
	) );
}

export function useSettingsForm( main: MainSettingsState, legacy: LegacySettingsState ) {
	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( coreStore );
	const config = legacy.record;
	const legacyValues = legacy.editedRecord?.values ?? config?.values ?? {};
	const mainIds = useMemo( () => getFieldIds( main.form?.fields ), [ main.form ] );
	const legacyEntries = useMemo(
		() => getLegacyCards( main.form, config ), [ main.form, config ]
	);
	const legacyIds = useMemo( () => getFieldIds( legacyEntries ), [ legacyEntries ] );
	const unsupportedControls = config?.unsupported.filter(
		( field ) => ! mainIds.has( field.id )
	) ?? [];
	const unsupportedLabels = Array.from( new Set(
		unsupportedControls.map( ( field ) => field.label || field.id )
	) );
	const form = useMemo< Form >(
		() => ( { layout: main.form?.layout ?? config?.form.layout ?? { type: 'regular' },
			fields: [ ...( main.form?.fields ?? [] ), ...legacyEntries ] } ),
		[ main.form, config, legacyEntries ]
	);
	const data = useMemo(
		() => ( { ...legacyValues, ...main.data } ) as Settings,
		[ legacyValues, main.data ]
	);
	const fields = useMemo(
		() => getDataFormFields( main.fields, config, form ),
		[ main.fields, config, form ]
	);
	const { validity, isValid } = useFormValidity( data, fields, form );
	const isDirty = main.dirty || legacy.dirty;
	const isSaving = main.saving || legacy.saving;

	const onChange = ( edits: Partial< Settings > ) => {
		if ( isSaving ) return;
		const mainEdits: Record< string, unknown > = {};
		const nextLegacy: Record< string, unknown > = {};
		Object.entries( edits ).forEach( ( [ id, value ] ) => {
			if ( mainIds.has( id ) ) mainEdits[ id ] = value;
			else if ( legacyIds.has( id ) ) nextLegacy[ id ] = value;
		} );
		if ( Object.keys( mainEdits ).length ) {
			void editEntityRecord( main.entity.kind, main.entity.name, undefined, mainEdits );
		}
		if ( Object.keys( nextLegacy ).length ) {
			void editEntityRecord( legacy.entity.kind, legacy.entity.name, undefined,
				{ values: { ...legacyValues, ...nextLegacy } } );
		}
	};
	const onDiscard = () => {
		if ( main.dirty ) {
			void editEntityRecord( main.entity.kind, main.entity.name, undefined, main.savedData );
		}
		if ( legacy.dirty && config ) {
			void editEntityRecord( legacy.entity.kind, legacy.entity.name, undefined,
				{ values: config.values } );
		}
	};
	const onSave = async () => {
		if ( isSaving || ! isDirty || ! isValid || ! config ) return;
		const requests: Promise< unknown >[] = [];
		if ( main.dirty ) {
			requests.push( saveEditedEntityRecord( main.entity.kind, main.entity.name, undefined,
				{ throwOnError: true } ) );
		}
		if ( legacy.dirty ) {
			const changed = getChangedValues( config.values, legacyValues );
			requests.push( saveEditedEntityRecord( legacy.entity.kind, legacy.entity.name, undefined, {
				throwOnError: true,
				__unstableFetch: ( request: Parameters< typeof apiFetch >[ 0 ] ) => apiFetch( {
					...request,
					data: { values: changed },
				} ),
			} ) );
		}
		await Promise.allSettled( requests );
	};

	return {
		form, data, fields, validity, isValid, isDirty, isSaving, onChange, onDiscard,
		onSave, unsupportedLabels,
	};
}
