/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Spinner } from '@wordpress/components';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import { Button, Notice, Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { useSettingsEntity } from './save';
import { useScreenFields, useScreenForm } from './hooks';
import type { SettingsScreen, ScreenDefinition } from './definition';
import './style.scss';

function LoadError( { error }: { error: Error } ) {
	return (
		<Notice.Root intent="error">
			<Notice.Description>{ error.message }</Notice.Description>
		</Notice.Root>
	);
}

function SettingsForm( {
	screen,
	definition,
}: {
	screen: SettingsScreen;
	definition: ScreenDefinition;
} ) {
	const {
		data,
		isDirty,
		isSaving,
		loadError,
		notice,
		clearNotice,
		edit,
		save,
		discard,
	} = useSettingsEntity( screen );
	const { validity, isValid } = useFormValidity(
		data ?? {},
		definition.fields,
		definition.form
	);

	if ( loadError ) {
		return <LoadError error={ loadError } />;
	}
	if ( ! data ) {
		return <Spinner />;
	}

	// A field whose module failed to load renders with default controls, which may not
	// represent its value correctly, so saving waits until the screen loads completely.
	const canSave = isDirty && isValid && definition.errors.length === 0;
	return (
		<Page
			ariaLabel={ screen.title }
			breadcrumbs={ <Breadcrumbs items={ screen.breadcrumbs } /> }
			showSidebarToggle={ false }
			hasPadding
			actions={
				<>
					<Button
						variant="outline"
						size="compact"
						disabled={ isSaving || ! isDirty }
						onClick={ discard }
					>
						{ __( 'Discard changes', 'woocommerce' ) }
					</Button>
					<Button
						variant="solid"
						size="compact"
						loading={ isSaving }
						disabled={ isSaving || ! canSave }
						onClick={ () => void save() }
					>
						{ __( 'Save changes', 'woocommerce' ) }
					</Button>
				</>
			}
		>
			<Stack
				direction="column"
				gap="lg"
				className="wc-settings-screen__content"
			>
				{ definition.errors.map( ( error ) => (
					<Notice.Root key={ error.message } intent="warning">
						<Notice.Description>
							{ error.message }
						</Notice.Description>
					</Notice.Root>
				) ) }
				{ notice && (
					<Notice.Root intent={ notice.status }>
						<Notice.Description>
							{ notice.message }
						</Notice.Description>
						<Notice.CloseIcon onClick={ clearNotice } />
					</Notice.Root>
				) }
				<DataForm
					data={ data }
					fields={ definition.fields }
					form={ definition.form }
					validity={ validity }
					onChange={ edit }
				/>
			</Stack>
		</Page>
	);
}

function ScreenContent( { screen }: { screen: SettingsScreen } ) {
	const { fields, error } = useScreenFields( screen );
	const { form, error: formError } = useScreenForm( screen );

	const loadError = error ?? formError;
	if ( loadError ) {
		return <LoadError error={ loadError } />;
	}
	if ( ! fields || ! form ) {
		return <Spinner />;
	}

	return (
		<SettingsForm
			screen={ screen }
			definition={ {
				fields: fields.fields,
				form,
				errors: fields.errors,
			} }
		/>
	);
}

export { ScreenContent };
