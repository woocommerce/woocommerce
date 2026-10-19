/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Spinner } from '@wordpress/components';
import {
	store as coreStore,
	useEntityRecord,
	useEntityRecords,
} from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Form } from '@wordpress/dataviews';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';
import { Button, Notice, Stack } from '@wordpress/ui';
import { useViewConfig } from '@wordpress/views';
import { useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import {
	ENTITY_KIND,
	SUPPORT_ENTITY_KIND,
	getOfflineGateway,
} from './entities';
import type { OfflineGateway, OfflineSettings } from './entities';
import { getOfflineFields } from './fields';

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
 * Render and save a Core offline gateway using WordPress entity and form APIs.
 */
function OfflinePaymentSettings( { gateway }: { gateway: OfflineGateway } ) {
	const { id: gatewayId } = gateway;
	const { record, editedRecord, hasEdits, edit, save } =
		useEntityRecord< OfflineSettings >( ENTITY_KIND, gatewayId, gatewayId );
	const edited = editedRecord as OfflineSettings | undefined;
	const countryData = useEntityRecords< { code: string; name: string } >(
		SUPPORT_ENTITY_KIND,
		'country',
		undefined,
		{ enabled: gatewayId === 'bacs' }
	);
	const locationData = useEntityRecord< { value: unknown } >(
		SUPPORT_ENTITY_KIND,
		'general-setting',
		'woocommerce_default_country',
		{ enabled: gatewayId === 'bacs' }
	);
	const { form } = useViewConfig( { kind: ENTITY_KIND, name: gatewayId } );
	const [ notice, setNotice ] = useState< {
		status: 'success' | 'error';
		message: string;
	} >();
	const { isSaving, loadError, supportError, formResolved } = useSelect(
		( select ) => {
			const core = select( coreStore );
			const metadata = core as unknown as ResolutionSelectors;
			return {
				isSaving: core.isSavingEntityRecord(
					ENTITY_KIND,
					gatewayId,
					gatewayId
				),
				loadError:
					metadata.getResolutionError( 'getEntityRecord', [
						ENTITY_KIND,
						gatewayId,
						gatewayId,
					] ) ??
					metadata.getResolutionError( 'getViewConfig', [
						ENTITY_KIND,
						gatewayId,
					] ),
				supportError:
					gatewayId === 'bacs'
						? ( metadata.getResolutionError( 'getEntityRecords', [
								SUPPORT_ENTITY_KIND,
								'country',
							] ) ??
							metadata.getResolutionError( 'getEntityRecord', [
								SUPPORT_ENTITY_KIND,
								'general-setting',
								'woocommerce_default_country',
							] ) )
						: undefined,
				formResolved: metadata.hasFinishedResolution( 'getViewConfig', [
					ENTITY_KIND,
					gatewayId,
				] ),
			};
		},
		[ gatewayId ]
	);
	const data = useMemo< OfflineSettings >(
		() => ( {
			id: gatewayId,
			enabled: false,
			title: '',
			description: '',
			instructions: '',
			enable_for_methods: [],
			enable_for_virtual: true,
			settings: {},
			...edited,
			countries: Object.fromEntries(
				( countryData.records ?? [] ).map( ( country ) => [
					country.code,
					decodeEntities( country.name ),
				] )
			),
			default_country:
				typeof locationData.record?.value === 'string'
					? ( locationData.record.value.split( ':' )[ 0 ] ?? '' )
					: '',
		} ),
		[ gatewayId, edited, countryData.records, locationData.record ]
	);
	const fields = useMemo(
		() => getOfflineFields( gatewayId, record ?? undefined ),
		[ gatewayId, record ]
	);
	const { validity, isValid } = useFormValidity(
		data,
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
	if ( loadError || supportError || ( formResolved && ! form ) ) {
		return (
			<Notice.Root intent="error">
				<Notice.Description>
					{ getErrorMessage(
						loadError ?? supportError,
						__( 'Unable to load settings.', 'woocommerce' )
					) }
				</Notice.Description>
			</Notice.Root>
		);
	}
	if (
		! edited ||
		! form ||
		( gatewayId === 'bacs' &&
			( ! countryData.hasResolved || ! locationData.hasResolved ) )
	) {
		return <Spinner />;
	}
	const paymentsUrl = new URL(
		'admin.php?page=wc-settings&tab=checkout',
		window.location.href
	).toString();
	return (
		<Page
			ariaLabel={ gateway.title }
			breadcrumbs={
				<Breadcrumbs
					items={ [
						{
							label: __( 'Payments', 'woocommerce' ),
							to: paymentsUrl,
						},
						{ label: gateway.title },
					] }
				/>
			}
			showSidebarToggle={ false }
			hasPadding
			actions={
				<Stack direction="row" gap="sm" wrap="wrap">
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
			}
		>
			<Stack direction="column" gap="lg">
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
			</Stack>
		</Page>
	);
}

/**
 * Resolve the Core gateway from the route instead of a separate screen registry.
 */
function SettingsStage() {
	const { page }: { page?: string } = useParams( { strict: false } );
	const gateway = getOfflineGateway( page );
	return gateway ? (
		<OfflinePaymentSettings key={ gateway.id } gateway={ gateway } />
	) : null;
}

export const stage = SettingsStage;
