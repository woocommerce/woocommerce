/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Button, Notice } from '@wordpress/components';
import { store as coreStore, useEntityRecords } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Field, Form } from '@wordpress/dataviews';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useViewConfig } from '@wordpress/views';
import { useId, useMemo } from 'react';

/**
 * Internal dependencies
 */
import {
	SETTINGS_ENTITY,
	SETTINGS_QUERY,
	SETTINGS_ARGS,
	VIEW_CONFIG_FIELDS,
	VIEW_CONFIG_ARGS,
} from './constants';
import { unlock } from './unlock';
import './style.scss';

type Settings = Record< string, string >;
type Setting = {
	id: string;
	value: string;
};

const { kind, name } = SETTINGS_ENTITY;

function SettingsForm( {
	settings,
	fields,
	form,
}: {
	settings: Setting[];
	fields: Field< Settings >[];
	form: Form;
} ) {
	const formId = useId();
	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const { data, isDirty, isSaving, saveError } = useSelect(
		( select ) => {
			const {
				getEditedEntityRecord,
				hasEditsForEntityRecord,
				isSavingEntityRecord,
				getLastEntitySaveError,
			} = select( coreStore );

			return {
				data: Object.fromEntries(
					settings.map( ( { id } ) => [
						id,
						String( getEditedEntityRecord( kind, name, id ).value ),
					] )
				) as Settings,
				isDirty: settings.some( ( { id } ) =>
					hasEditsForEntityRecord( kind, name, id )
				),
				isSaving: settings.some( ( { id } ) =>
					isSavingEntityRecord( kind, name, id )
				),
				saveError: settings
					.map( ( { id } ) =>
						getLastEntitySaveError( kind, name, id )
					)
					.find( Boolean ),
			};
		},
		[ settings ]
	);
	const { validity, isValid } = useFormValidity( data, fields, form );

	const onChange = ( edits: Partial< Settings > ) => {
		if ( isSaving ) {
			return;
		}
		Object.entries( edits ).forEach( ( [ id, value ] ) => {
			void editEntityRecord( kind, name, id, { value } );
		} );
	};
	const onDiscard = () => {
		onChange(
			Object.fromEntries(
				settings.map( ( { id, value } ) => [ id, value ] )
			)
		);
	};
	const onSave = () => {
		if ( isSaving || ! isDirty || ! isValid ) {
			return;
		}
		settings.forEach( ( { id } ) => {
			void saveEditedEntityRecord( kind, name, id );
		} );
	};

	return (
		<Page
			className="wc-settings-dataform"
			title={ __( 'General settings', 'woocommerce' ) }
			subTitle={ __(
				'Manage your store address and currency.',
				'woocommerce'
			) }
			showSidebarToggle={ false }
			hasPadding
			actions={
				<>
					<Button
						type="button"
						variant="secondary"
						size="compact"
						disabled={ isSaving || ! isDirty }
						onClick={ onDiscard }
					>
						{ __( 'Discard changes', 'woocommerce' ) }
					</Button>
					<Button
						type="submit"
						form={ formId }
						variant="primary"
						size="compact"
						isBusy={ isSaving }
						disabled={ isSaving || ! isDirty || ! isValid }
					>
						{ isSaving
							? __( 'Saving…', 'woocommerce' )
							: __( 'Save changes', 'woocommerce' ) }
					</Button>
				</>
			}
		>
			<DataForm
				data={ data }
				fields={ fields }
				form={ form }
				validity={ validity }
				onChange={ onChange }
			/>
		</Page>
	);
}

function SettingsStage() {
	const { form } = useViewConfig( {
		kind,
		name,
		fields: VIEW_CONFIG_FIELDS,
	} );
	const { records } = useEntityRecords< Setting >(
		kind,
		name,
		SETTINGS_QUERY
	);
	const { fields, loadError } = useSelect( ( select ) => {
		const { getResolutionError } = select( coreStore );
		return {
			fields: unlock( select( editorStore ) ).getEntityFields(
				kind,
				name
			) as Field< Settings >[],
			loadError:
				getResolutionError( 'getEntityRecords', SETTINGS_ARGS ) ||
				getResolutionError( 'getViewConfig', VIEW_CONFIG_ARGS ),
		};
	}, [] );
	const settings = useMemo(
		() =>
			records?.filter( ( { id } ) =>
				fields.some( ( field ) => field.id === id )
			) ?? [],
		[ records, fields ]
	);

	if ( loadError ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ loadError.message }
			</Notice>
		);
	}

	if ( ! form || ! records ) {
		return null;
	}

	return (
		<SettingsForm settings={ settings } fields={ fields } form={ form } />
	);
}

export const stage = SettingsStage;
