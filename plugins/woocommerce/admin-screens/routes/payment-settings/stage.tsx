/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Spinner } from '@wordpress/components';
import { useViewportMatch } from '@wordpress/compose';
import { store as coreStore, useEntityRecord } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Form } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';
import { Button, Notice, Stack } from '@wordpress/ui';
import { useViewConfig } from '@wordpress/views';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ENTITY_KIND } from './entities';
import type { SettingsEntityConfig, SettingsRecord } from './entities';
import './style.scss';

const EMPTY_FORM: Form = { fields: [] };

interface ResolutionSelectors {
	hasFinishedResolution: ( selector: string, args: unknown[] ) => boolean;
	getResolutionError: ( selector: string, args: unknown[] ) => unknown;
}

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
	const { kind, name, label, fields } = config;
	const { record, editedRecord, hasEdits, edit, save } =
		useEntityRecord< SettingsRecord >( kind, name, name );
	const isNarrow = useViewportMatch( 'medium', '<' );
	const { form } = useViewConfig( { kind, name } );
	const [ notice, setNotice ] = useState< {
		status: 'success' | 'error';
		message: string;
	} >();
	const { isSaving, loadError, formResolved } = useSelect(
		( select ) => {
			const core = select( coreStore );
			const metadata = core as unknown as ResolutionSelectors;
			return {
				isSaving: core.isSavingEntityRecord( kind, name, name ),
				loadError:
					metadata.getResolutionError( 'getEntityRecord', [
						kind,
						name,
						name,
					] ) ??
					metadata.getResolutionError( 'getViewConfig', [
						kind,
						name,
					] ),
				formResolved: metadata.hasFinishedResolution( 'getViewConfig', [
					kind,
					name,
				] ),
			};
		},
		[ kind, name ]
	);
	const data = editedRecord as SettingsRecord | undefined;
	const { validity, isValid } = useFormValidity(
		data ?? {},
		fields,
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
	const hasLoadError = !! loadError || ( formResolved && ! form );
	const isReady = ! hasLoadError && !! data && !! form;
	const paymentsUrl = new URL(
		'admin.php?page=wc-settings&tab=checkout',
		window.location.href
	).toString();
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
				className="woocommerce-payment-settings__content"
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
