/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Button, Notice, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Form } from '@wordpress/dataviews';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { useNavigate, useParams, useSearch } from '@wordpress/route';
import { useMemo } from 'react';
import type { ComponentType, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { getScreen, useScreenDefinition } from './screen';
import type {
	PaymentSettingsScreen,
	ScreenDefinition,
	SettingsRecord,
} from './screen';
import './style.scss';

/**
 * Props passed to a gateway's body renderer. Experimental: they can change in any release.
 */
export type BodyProps = {
	screen: PaymentSettingsScreen;
	definition: ScreenDefinition;
	data: SettingsRecord;
	view?: string;
	onChangeView: ( view?: string ) => void;
	renderForm: ( form: Form ) => ReactNode;
};

function SettingsForm( {
	screen,
	definition,
}: {
	screen: PaymentSettingsScreen;
	definition: ScreenDefinition;
} ) {
	const { kind, name } = screen.entity;
	const { record, data, isDirty, isSaving, saveError, loadError } = useSelect(
		( select ) => {
			const core = select( coreStore );
			return {
				record: core.getEntityRecord( kind, name, undefined ) as
					| SettingsRecord
					| undefined,
				data: core.getEditedEntityRecord( kind, name, undefined ) as
					| SettingsRecord
					| undefined,
				isDirty: core.hasEditsForEntityRecord( kind, name, undefined ),
				isSaving: core.isSavingEntityRecord( kind, name, undefined ),
				saveError: core.getLastEntitySaveError(
					kind,
					name,
					undefined
				) as Error | undefined,
				loadError: core.getResolutionError( 'getEntityRecord', [
					kind,
					name,
					undefined,
				] ) as Error | undefined,
			};
		},
		[ kind, name ]
	);
	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const { validity, isValid } = useFormValidity(
		data ?? {},
		definition.fields,
		definition.form
	);
	const { view } = useSearch( { strict: false } ) as { view?: string };
	const navigate = useNavigate();
	// Resolve the body once per screen, so a filter returning a new component can't remount it on every render.
	const Body = useMemo(
		() =>
			applyFilters(
				'woocommerce.experimentalPaymentSettings.body',
				null,
				screen.id
			) as ComponentType< BodyProps > | null,
		[ screen.id ]
	);

	if ( loadError ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ loadError.message ||
					__( 'Unable to load settings.', 'woocommerce' ) }
			</Notice>
		);
	}
	if ( ! record || ! data ) {
		return <Spinner />;
	}

	const onChange = ( edits: SettingsRecord ) => {
		if ( ! isSaving ) {
			void editEntityRecord( kind, name, undefined, edits );
		}
	};
	const onSave = async () => {
		if ( isSaving || ! isDirty || ! isValid ) {
			return;
		}
		try {
			await saveEditedEntityRecord( kind, name, undefined, {
				throwOnError: true,
			} );
		} catch {
			// The error is shown from the entity's last save error.
		}
	};
	const renderForm = ( form: Form ) => (
		<DataForm
			data={ data }
			fields={ definition.fields }
			form={ form }
			validity={ validity }
			onChange={ onChange }
		/>
	);

	return (
		<Page
			title={ screen.title }
			showSidebarToggle={ false }
			hasPadding
			actions={
				<>
					<Button
						variant="secondary"
						size="compact"
						disabled={ isSaving || ! isDirty }
						onClick={ () =>
							void editEntityRecord(
								kind,
								name,
								undefined,
								record
							)
						}
					>
						{ __( 'Discard changes', 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						size="compact"
						isBusy={ isSaving }
						disabled={ isSaving || ! isDirty || ! isValid }
						onClick={ () => void onSave() }
					>
						{ __( 'Save changes', 'woocommerce' ) }
					</Button>
				</>
			}
		>
			<div className="wc-payment-settings__content">
				{ definition.errors.map( ( error ) => (
					<Notice
						key={ error.message }
						status="warning"
						isDismissible={ false }
					>
						{ error.message }
					</Notice>
				) ) }
				{ saveError && (
					<Notice status="error" isDismissible={ false }>
						{ saveError.message ||
							__( 'Unable to save settings.', 'woocommerce' ) }
					</Notice>
				) }
				{ Body ? (
					<Body
						screen={ screen }
						definition={ definition }
						data={ data }
						view={ view }
						onChangeView={ ( nextView ) =>
							void navigate( {
								search: ( ( previous: object ) => ( {
									...previous,
									view: nextView,
								} ) ) as never,
							} )
						}
						renderForm={ renderForm }
					/>
				) : (
					renderForm( definition.form )
				) }
			</div>
		</Page>
	);
}

function ScreenContent( { screen }: { screen: PaymentSettingsScreen } ) {
	const { definition, error } = useScreenDefinition( screen );

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error.message ||
					__( 'Unable to load settings.', 'woocommerce' ) }
			</Notice>
		);
	}
	if ( ! definition ) {
		return <Spinner />;
	}

	return <SettingsForm screen={ screen } definition={ definition } />;
}

function SettingsStage() {
	const { page } = useParams( { strict: false } ) as { page?: string };
	const screen = getScreen( page );

	return screen ? <ScreenContent screen={ screen } /> : null;
}

export const stage = SettingsStage;
