/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Button, Notice, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { DataForm } from '@wordpress/dataviews';
import type { Field } from '@wordpress/dataviews';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';
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
	getLegacySettingsEntity,
	getRegisteredSettingsPages,
} from './pages';
import type { SettingsEntity, SettingsPage } from './pages';
import { useSettingsForm } from './use-settings-form';
import type {
	LegacySettingsState,
	MainSettingsState,
} from './use-settings-form';

/**
 * Styles
 */
import './style.scss';

function SettingsPageContent( {
	page,
	main,
	legacy,
}: {
	page: SettingsPage;
	main: MainSettingsState;
	legacy: LegacySettingsState;
} ) {
	const formId = useId();
	const {
		form,
		data,
		fields,
		validity,
		isValid,
		isDirty,
		isSaving,
		onChange,
		onDiscard,
		onSave,
		unsupportedFields,
	} = useSettingsForm( main, legacy );

	if ( main.loadError || legacy.loadError ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ ( main.loadError ?? legacy.loadError )?.message ||
					__( 'Unable to load settings.', 'woocommerce' ) }
			</Notice>
		);
	}
	if ( ! main.ready || ! legacy.ready || ! legacy.record ) {
		return <Spinner />;
	}

	return (
		<Page
			className="wc-settings-dataform"
			title={
				page.id === 'products'
					? __( 'Product settings', 'woocommerce' )
					: page.label
			}
			showSidebarToggle={ false }
			hasPadding
			actions={
				<>
					<Button
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
			<div className="wc-settings-dataform__content">
				{ unsupportedFields.length > 0 && (
					<Notice status="warning" isDismissible={ false }>
						<p>
							{ __(
								'These settings are available only in classic settings:',
								'woocommerce'
							) }
						</p>
						<ul className="wc-settings-dataform__unsupported-list">
							{ unsupportedFields.map( ( field ) => (
								<li key={ field.id }>
									{ field.label || (
										<>
											{ __(
												'Setting ID:',
												'woocommerce'
											) }{ ' ' }
											<code>{ field.id }</code>
										</>
									) }
								</li>
							) ) }
						</ul>
						<a href={ page.url }>
							{ __( 'Open classic settings', 'woocommerce' ) }
						</a>
					</Notice>
				) }
				{ ( legacy.saveError || main.saveError ) && (
					<Notice status="error" isDismissible={ false }>
						{ ( legacy.saveError || main.saveError )?.message ||
							__( 'Unable to save settings.', 'woocommerce' ) }
					</Notice>
				) }
				<form
					id={ formId }
					onSubmit={ ( event ) => {
						event.preventDefault();
						void onSave();
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

function ProductSettingsStage( {
	page,
	entity,
	legacy,
}: {
	page: SettingsPage;
	entity: SettingsEntity;
	legacy: LegacySettingsState;
} ) {
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
	const {
		record,
		data,
		resolved,
		fields,
		dirty,
		saving,
		saveError,
		loadError,
	} = useSelect(
		( select ) => {
			const core = select( coreStore );
			return {
				record: core.getEntityRecord( ...settingsArgs ) as
					| Settings
					| undefined,
				data: core.getEditedEntityRecord( ...settingsArgs ) as
					| Settings
					| undefined,
				resolved: core.hasFinishedResolution(
					'getEntityRecord',
					settingsArgs
				),
				fields: unlock( select( editorStore ) ).getEntityFields(
					kind,
					name
				) as Field< Settings >[],
				dirty: core.hasEditsForEntityRecord( ...settingsArgs ),
				saving: core.isSavingEntityRecord( ...settingsArgs ),
				saveError: core.getLastEntitySaveError( ...settingsArgs ),
				loadError:
					core.getResolutionError(
						'getEntityRecord',
						settingsArgs
					) ||
					core.getResolutionError( 'getViewConfig', viewConfigArgs ),
			};
		},
		[ kind, name, settingsArgs, viewConfigArgs ]
	);
	const main: MainSettingsState = {
		entity,
		form,
		fields,
		data: data ?? {},
		savedData: record ?? {},
		dirty,
		saving,
		saveError,
		loadError,
		ready: resolved && !! record && !! form,
	};
	return (
		<SettingsPageContent page={ page } main={ main } legacy={ legacy } />
	);
}

function LegacySettingsStage( { page }: { page: SettingsPage } ) {
	const legacyEntity = getLegacySettingsEntity( page );
	const {
		record,
		editedRecord,
		resolved,
		dirty,
		saving,
		saveError,
		loadError,
	} = useSelect(
		( select ) => {
			const core = select( coreStore );
			const args = [
				legacyEntity.kind,
				legacyEntity.name,
				undefined,
			] as const;
			return {
				record: core.getEntityRecord( ...args ),
				editedRecord: core.getEditedEntityRecord( ...args ),
				resolved: core.hasFinishedResolution( 'getEntityRecord', args ),
				dirty: core.hasEditsForEntityRecord( ...args ),
				saving: core.isSavingEntityRecord( ...args ),
				saveError: core.getLastEntitySaveError( ...args ),
				loadError: core.getResolutionError( 'getEntityRecord', args ),
			};
		},
		[ legacyEntity.kind, legacyEntity.name ]
	);
	const legacy: LegacySettingsState = {
		entity: legacyEntity,
		record,
		editedRecord,
		ready: resolved,
		dirty,
		saving,
		saveError,
		loadError,
	};
	const entity = getSettingsEntity( page );
	if ( page.id === 'products' ) {
		return (
			<ProductSettingsStage
				page={ page }
				entity={ entity }
				legacy={ legacy }
			/>
		);
	}
	const main: MainSettingsState = {
		entity,
		fields: [],
		data: {},
		savedData: {},
		dirty: false,
		saving: false,
		ready: true,
	};
	return (
		<SettingsPageContent page={ page } main={ main } legacy={ legacy } />
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
	const page = getSettingsPage( getRegisteredSettingsPages( pages ?? [] ), {
		params,
	} );
	if ( ! page ) {
		return null;
	}
	if ( page.entity ) {
		return (
			<ProductSettingsStage
				page={ page }
				entity={ getSettingsEntity( page ) }
				legacy={ {
					entity: getLegacySettingsEntity( page ),
					ready: true,
					dirty: false,
					saving: false,
					record: {
						form: { fields: [] },
						fields: [],
						values: {},
						unsupported: [],
					},
				} }
			/>
		);
	}
	return <LegacySettingsStage page={ page } />;
}

export const stage = SettingsStage;
