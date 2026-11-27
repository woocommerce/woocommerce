/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Spinner } from '@wordpress/components';
import { useViewportMatch } from '@wordpress/compose';
import { store as coreStore, useEntityRecord } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Form } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';
// eslint-disable-next-line @wordpress/use-recommended-components -- Fine on this experimental screen until the repo enables allowUseWithCaution.
import { Button, Notice, Stack } from '@wordpress/ui';
import { useViewConfig } from '@wordpress/views';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { getSection } from './entities';
import type { SettingsEntityConfig, SettingsSection } from './entities';
import { getEntityFields, getEntityFieldsStatus } from './entity-fields';
import type { SettingsField, SettingsRecord } from './types';
import './style.scss';

const EMPTY_FORM: Form = { fields: [] };
const EMPTY_FIELDS: SettingsField[] = [];

/**
 * Use REST error messages without assuming requests reject with Error instances.
 */
function getErrorMessage( error: unknown, fallback: string ): string {
	return typeof error === 'object' &&
		error !== null &&
		'message' in error &&
		typeof error.message === 'string' &&
		error.message !== ''
		? error.message
		: fallback;
}

/**
 * Render and save one settings entity using WordPress entity and form APIs.
 */
function SettingsForm( {
	section,
	config,
}: {
	section: SettingsSection;
	config: SettingsEntityConfig;
} ) {
	const { kind, name, label } = config;
	const { record, editedRecord, hasEdits, edit, save } =
		useEntityRecord< SettingsRecord >( kind, name, name );
	const isNarrow = useViewportMatch( 'medium', '<' );
	const { form } = useViewConfig( { kind, name } );
	const [ notice, setNotice ] = useState< {
		status: 'success' | 'error';
		message: string;
	} >();
	const { fields, hasFields, isSaving, loadError } = useSelect(
		( select ) => {
			const core = select( coreStore );
			const editor = select( editorStore );
			const fieldsStatus = getEntityFieldsStatus( editor, kind, name );
			return {
				fields: getEntityFields( editor, kind, name ),
				hasFields: fieldsStatus.hasLoaded,
				isSaving: core.isSavingEntityRecord( kind, name, name ),
				loadError:
					core.getResolutionError( 'getEntityRecord', [
						kind,
						name,
						name,
					] ) ?? fieldsStatus.error,
			};
		},
		[ kind, name ]
	);
	// The record is `false` until it loads. Validate without fields until then, so their getters never read it.
	const data = ( editedRecord as SettingsRecord | false ) || undefined;
	const { validity, isValid } = useFormValidity(
		data ?? {},
		data ? fields : EMPTY_FIELDS,
		form ?? EMPTY_FORM
	);
	const saveSettings = async () => {
		if ( isSaving || ! hasEdits || ! isValid ) {
			return;
		}
		setNotice( undefined );
		try {
			await save();
			setNotice( {
				status: 'success',
				message: __( 'Settings saved.', 'woocommerce' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: getErrorMessage(
					error,
					__( 'Unable to save settings.', 'woocommerce' )
				),
			} );
		}
	};
	const hasLoadError = !! loadError;
	const isReady = ! hasLoadError && !! data && !! form && hasFields;
	// Breadcrumbs link through the router, which only leaves the screen for an absolute URL.
	const sectionUrl = new URL( section.url, window.location.href ).toString();
	const formActions = (
		<Stack direction="row" gap="sm" justify="end">
			<Button
				variant="outline"
				size="compact"
				disabled={ isSaving || ! hasEdits }
				onClick={ () => {
					if ( record ) {
						edit( record );
						setNotice( undefined );
					}
				} }
			>
				{ __( 'Discard changes', 'woocommerce' ) }
			</Button>
			<Button
				variant="solid"
				size="compact"
				loading={ isSaving }
				disabled={ isSaving || ! hasEdits || ! isValid }
				onClick={ () => void saveSettings() }
			>
				{ __( 'Save changes', 'woocommerce' ) }
			</Button>
		</Stack>
	);
	return (
		<Page
			ariaLabel={ label }
			breadcrumbs={
				<Breadcrumbs
					items={ [
						{ label: section.label, to: sectionUrl },
						{ label },
					] }
				/>
			}
			showSidebarToggle={ false }
			hasPadding
			// The Page header doesn't wrap its actions, so they move below the form on small screens.
			actions={ isReady && ! isNarrow ? formActions : undefined }
		>
			<Stack
				direction="column"
				gap="lg"
				className="woocommerce-settings__content"
			>
				{ hasLoadError && (
					<Notice.Root intent="error">
						<Notice.Description>
							{ getErrorMessage(
								loadError,
								__( 'Unable to load settings.', 'woocommerce' )
							) }
						</Notice.Description>
					</Notice.Root>
				) }
				{ ! hasLoadError && ! isReady && (
					<Stack direction="row" justify="center">
						<Spinner />
					</Stack>
				) }
				{ notice && (
					<Notice.Root intent={ notice.status }>
						<Notice.Description>
							{ notice.message }
						</Notice.Description>
						<Notice.CloseIcon
							onClick={ () => {
								setNotice( undefined );
							} }
						/>
					</Notice.Root>
				) }
				{ isReady && (
					<DataForm
						data={ data }
						fields={ fields }
						form={ form }
						validity={ validity }
						onChange={ ( edits ) => {
							if ( ! isSaving ) {
								setNotice( undefined );
								edit( edits );
							}
						} }
					/>
				) }
				{ isReady && isNarrow && formActions }
			</Stack>
		</Page>
	);
}

/**
 * Show the settings entity whose name matches the route, from the entity kind of the route's area.
 */
function SettingsStage() {
	const { section: slug, page }: { section?: string; page?: string } =
		useParams( { strict: false } );
	const section = getSection( slug );
	const kind = section?.kind;
	const config = useSelect(
		( select ) =>
			kind && page
				? ( select( coreStore ).getEntityConfig( kind, page ) as
						SettingsEntityConfig | undefined )
				: undefined,
		[ kind, page ]
	);
	return section && config ? (
		<SettingsForm
			key={ config.name }
			section={ section }
			config={ config }
		/>
	) : null;
}

export const stage = SettingsStage;
