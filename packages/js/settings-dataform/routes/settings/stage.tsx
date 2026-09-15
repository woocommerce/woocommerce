/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Button, Notice, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Field, Form } from '@wordpress/dataviews';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { Link, useParams } from '@wordpress/route';
import { useViewConfig } from '@wordpress/views';
import { useId, useMemo } from 'react';
import {
	VIEW_CONFIG_FIELDS,
	unlock,
} from '@woocommerce-settings-ui-experimental/settings-ui';
import type { Settings } from '@woocommerce-settings-ui-experimental/settings-ui';

/**
 * Internal dependencies
 */
import {
	SETTINGS_PAGES_ARGS,
	getSettingsPage,
	getSettingsEntity,
	hasSettingsForm,
} from './pages';
import type { SettingsEntity, SettingsPage } from './pages';

/**
 * Styles
 */
import './style.scss';

function SettingsForm( {
	entity,
	settings,
	fields,
	form,
}: {
	entity: SettingsEntity;
	settings: Settings;
	fields: Field< Settings >[];
	form: Form;
} ) {
	const { kind, name } = entity;
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
				data: getEditedEntityRecord(
					kind,
					name,
					undefined
				) as Settings,
				isDirty: hasEditsForEntityRecord( kind, name, undefined ),
				isSaving: isSavingEntityRecord( kind, name, undefined ),
				saveError: getLastEntitySaveError( kind, name, undefined ),
			};
		},
		[ kind, name ]
	);
	const { validity, isValid } = useFormValidity( data, fields, form );

	const onChange = ( edits: Partial< Settings > ) => {
		if ( isSaving ) {
			return;
		}
		void editEntityRecord( kind, name, undefined, edits );
	};
	const onDiscard = () => {
		onChange( settings );
	};
	const onSave = () => {
		if ( isSaving || ! isDirty || ! isValid ) {
			return;
		}
		void saveEditedEntityRecord( kind, name, undefined );
	};

	return (
		<Page
			className="wc-settings-dataform"
			title={ __( 'Product settings', 'woocommerce' ) }
			subTitle={ __(
				'Manage your shop pages, measurements, reviews, and inventory.',
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
			<div
				style={ {
					boxSizing: 'border-box',
					marginInline: 'auto',
					maxWidth: '680px',
					padding: '24px',
					width: '100%',
				} }
			>
				{ saveError && (
					<Notice status="error" isDismissible={ false }>
						{ saveError.message ||
							__( 'Unable to save settings.', 'woocommerce' ) }
					</Notice>
				) }
				<form
					id={ formId }
					onSubmit={ ( event ) => {
						event.preventDefault();
						onSave();
					} }
				>
					<DataForm
						data={ data }
						fields={ fields }
						form={ form }
						validity={ validity }
						onChange={ onChange }
					/>
				</form>
			</div>
		</Page>
	);
}

function ProductSettingsStage( { entity }: { entity: SettingsEntity } ) {
	const { kind, name } = entity;
	const settingsArgs = useMemo(
		() => [ kind, name, undefined ] as const,
		[ kind, name ]
	);
	const viewConfigArgs = useMemo(
		() =>
			[ kind, name, { fields: VIEW_CONFIG_FIELDS.join( ',' ) } ] as const,
		[ kind, name ]
	);
	const { form } = useViewConfig( {
		kind,
		name,
		fields: VIEW_CONFIG_FIELDS,
	} );
	const { record, hasResolved, fields, loadError } = useSelect(
		( select ) => {
			const {
				getEntityRecord,
				hasFinishedResolution,
				getResolutionError,
			} = select( coreStore );
			return {
				record: getEntityRecord( ...settingsArgs ) as
					| Settings
					| undefined,
				hasResolved: hasFinishedResolution(
					'getEntityRecord',
					settingsArgs
				),
				fields: unlock( select( editorStore ) ).getEntityFields(
					kind,
					name
				) as Field< Settings >[],
				loadError:
					getResolutionError( 'getEntityRecord', settingsArgs ) ||
					getResolutionError( 'getViewConfig', viewConfigArgs ),
			};
		},
		[ kind, name ]
	);
	const availableFields = useMemo(
		() =>
			fields.filter( ( { id } ) =>
				Object.prototype.hasOwnProperty.call( record ?? {}, id )
			),
		[ record, fields ]
	);

	if ( loadError ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ loadError.message ||
					__( 'Unable to load settings.', 'woocommerce' ) }
			</Notice>
		);
	}

	if ( ! hasResolved || ! record || ! form ) {
		return <Spinner />;
	}

	return (
		<SettingsForm
			entity={ entity }
			settings={ record }
			fields={ availableFields }
			form={ form }
		/>
	);
}

function SettingsStage() {
	const params = useParams( { strict: false } );
	const pages = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( ...SETTINGS_PAGES_ARGS ) as
				| SettingsPage[]
				| undefined,
		[]
	);
	const page = getSettingsPage( pages ?? [], { params } );
	if ( ! page ) {
		return null;
	}
	const entity = getSettingsEntity( page );

	return (
		<>
			<ProductSettingsStage entity={ entity } />
		</>
	);
}

export const stage = SettingsStage;
