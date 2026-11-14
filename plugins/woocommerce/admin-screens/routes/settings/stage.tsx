/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Spinner } from '@wordpress/components';
import { useViewportMatch } from '@wordpress/compose';
import { store as coreStore, useEntityRecord } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
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
import type { SettingsRecord } from '../../entity-fields/types';
import { ENTITY_KIND } from './entities';
import type { RelatedEntity, SettingsEntityConfig } from './entities';
import { getEntityFields } from './entity-fields';
import './style.scss';

const EMPTY_FORM: Form = { fields: [] };

const NO_RELATED: RelatedEntity[] = [];

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
function SettingsForm( { config }: { config: SettingsEntityConfig } ) {
	const { kind, name, label } = config;
	const related = config.related ?? NO_RELATED;
	const { record, editedRecord, hasEdits, edit, save } =
		useEntityRecord< SettingsRecord >( kind, name, name );
	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const isNarrow = useViewportMatch( 'medium', '<' );
	const { form } = useViewConfig( { kind, name } );
	const [ notice, setNotice ] = useState< {
		status: 'success' | 'error';
		message: string;
	} >();
	const { fields, ownIsSaving, ownLoadError, ...relatedValues } = useSelect(
		( select ) => {
			const core = select( coreStore );
			// One top-level key per related value, so useSelect can tell nothing changed.
			const state: Record< string, unknown > = {};
			related.forEach(
				( { kind: relatedKind, name: relatedName, recordId } ) => {
					state[ `${ relatedName }:record` ] = core.getEntityRecord(
						relatedKind,
						relatedName,
						recordId
					);
					state[ `${ relatedName }:editedRecord` ] =
						core.getEditedEntityRecord(
							relatedKind,
							relatedName,
							recordId
						);
					state[ `${ relatedName }:hasEdits` ] =
						core.hasEditsForEntityRecord(
							relatedKind,
							relatedName,
							recordId
						);
					state[ `${ relatedName }:isSaving` ] =
						core.isSavingEntityRecord(
							relatedKind,
							relatedName,
							recordId
						);
					state[ `${ relatedName }:loadError` ] =
						core.getResolutionError( 'getEntityRecord', [
							relatedKind,
							relatedName,
							recordId,
						] );
				}
			);
			return {
				...state,
				fields: getEntityFields( select( editorStore ), kind, name ),
				ownIsSaving: core.isSavingEntityRecord( kind, name, name ),
				ownLoadError: core.getResolutionError( 'getEntityRecord', [
					kind,
					name,
					name,
				] ),
			};
		},
		[ kind, name, related ]
	);
	const relatedState: Record< string, unknown > = relatedValues;
	const relatedRecords = related.map( ( entity ) => ( {
		...entity,
		record: relatedState[ `${ entity.name }:record` ] as
			SettingsRecord | undefined,
		editedRecord: relatedState[ `${ entity.name }:editedRecord` ] as
			SettingsRecord | false | undefined,
		hasEdits: !! relatedState[ `${ entity.name }:hasEdits` ],
		isSaving: !! relatedState[ `${ entity.name }:isSaving` ],
		loadError: relatedState[ `${ entity.name }:loadError` ],
	} ) );
	const isSaving =
		ownIsSaving || relatedRecords.some( ( entry ) => entry.isSaving );
	const loadError =
		ownLoadError ??
		relatedRecords.find( ( entry ) => entry.loadError )?.loadError;
	const hasAnyEdits =
		hasEdits || relatedRecords.some( ( entry ) => entry.hasEdits );
	// Fields read each related record under its entity name.
	const ownRecord = editedRecord as SettingsRecord | undefined;
	const data =
		ownRecord &&
		relatedRecords.every( ( entry ) => entry.editedRecord && entry.record )
			? {
					...ownRecord,
					...Object.fromEntries(
						relatedRecords.map( ( entry ) => [
							entry.name,
							entry.editedRecord,
						] )
					),
				}
			: undefined;
	const onChange = ( edits: SettingsRecord ) => {
		const relatedNames = related.map( ( entity ) => entity.name );
		related.forEach( ( entity ) => {
			const relatedEdits = edits[ entity.name ];
			if ( relatedEdits && typeof relatedEdits === 'object' ) {
				void editEntityRecord(
					entity.kind,
					entity.name,
					entity.recordId,
					relatedEdits
				);
			}
		} );
		const ownEdits = Object.fromEntries(
			Object.entries( edits ).filter(
				( [ key ] ) => ! relatedNames.includes( key )
			)
		);
		if ( Object.keys( ownEdits ).length ) {
			edit( ownEdits );
		}
	};
	const { validity, isValid } = useFormValidity(
		data ?? {},
		fields,
		form ?? EMPTY_FORM
	);
	const saveSettings = async () => {
		if ( isSaving || ! hasAnyEdits || ! isValid ) {
			return;
		}
		setNotice( undefined );
		try {
			await Promise.all( [
				hasEdits && save(),
				...relatedRecords
					.filter( ( entry ) => entry.hasEdits )
					.map( ( entry ) =>
						saveEditedEntityRecord(
							entry.kind,
							entry.name,
							entry.recordId,
							{ throwOnError: true }
						)
					),
			] );
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
	const isReady = ! hasLoadError && !! data && !! form;
	// Breadcrumbs link through the router, which only leaves the screen for an absolute URL.
	const paymentsUrl = new URL(
		'admin.php?page=wc-settings&tab=checkout',
		window.location.href
	).toString();
	const formActions = (
		<Stack direction="row" gap="sm" justify="end">
			<Button
				variant="outline"
				size="compact"
				disabled={ isSaving || ! hasAnyEdits }
				onClick={ () => {
					if ( record ) {
						edit( record );
						relatedRecords.forEach( ( entry ) => {
							if ( entry.record ) {
								void editEntityRecord(
									entry.kind,
									entry.name,
									entry.recordId,
									entry.record
								);
							}
						} );
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
				disabled={ isSaving || ! hasAnyEdits || ! isValid }
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
						{
							label: __( 'Payments', 'woocommerce' ),
							to: paymentsUrl,
						},
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
								onChange( edits );
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
 * Show the settings entity whose name matches the route, as registered with addEntities().
 */
function SettingsStage() {
	const { page }: { page?: string } = useParams( { strict: false } );
	const config = useSelect(
		( select ) =>
			page
				? ( select( coreStore ).getEntityConfig( ENTITY_KIND, page ) as
						SettingsEntityConfig | undefined )
				: undefined,
		[ page ]
	);
	return config ? (
		<SettingsForm key={ config.name } config={ config } />
	) : null;
}

export const stage = SettingsStage;
