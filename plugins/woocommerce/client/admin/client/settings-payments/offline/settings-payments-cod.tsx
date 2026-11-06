/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentGatewaysStore, paymentSettingsStore } from '@woocommerce/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { DataForm } from '@wordpress/dataviews';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { Settings } from '~/settings-payments/components/settings';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';
import {
	CheckboxEdit,
	TextEdit,
	TextareaEdit,
	type OfflineFormValues,
} from './dataform-controls';
import {
	getShippingRestrictionFields,
	getShippingRestrictionSettings,
	getShippingRestrictionValues,
} from './shipping-restriction-fields';

/**
 * This page is used to manage the settings for the Cash on delivery payment gateway.
 */
export const SettingsPaymentsCod = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );
	const { codSettings, isLoading } = useSelect(
		( select ) => ( {
			codSettings:
				select( paymentGatewaysStore ).getPaymentGateway( 'cod' ),
			isLoading: ! select( paymentGatewaysStore ).hasFinishedResolution(
				'getPaymentGateway',
				[ 'cod' ]
			),
		} ),
		[]
	);

	const { updatePaymentGateway, invalidateResolutionForStoreSelector } =
		useDispatch( paymentGatewaysStore );

	const {
		invalidateResolution,
		invalidateResolutionForStoreSelector:
			invalidateResolutionForPaymentSettings,
	} = useDispatch( paymentSettingsStore );

	const [ formValues, setFormValues ] = useState< OfflineFormValues >( {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ hasChanges, setHasChanges ] = useState( false );

	useEffect( () => {
		if ( codSettings ) {
			setFormValues( {
				enabled: codSettings.enabled,
				title: codSettings.settings.title.value,
				description: codSettings.description,
				instructions: codSettings.settings.instructions.value,
				...getShippingRestrictionValues( codSettings ),
			} );
			setHasChanges( false );
		}
	}, [ codSettings ] );

	const fields: Field< OfflineFormValues >[] = useMemo(
		() => [
			{
				id: 'enabled',
				label: __( 'Enable cash on delivery payments', 'woocommerce' ),
				Edit: CheckboxEdit,
			},
			{
				id: 'title',
				label: __( 'Title', 'woocommerce' ),
				description: __(
					'Payment method name that the customer will see during checkout.',
					'woocommerce'
				),
				placeholder: __( 'Cash on delivery payments', 'woocommerce' ),
				Edit: TextEdit,
			},
			{
				id: 'description',
				label: __( 'Description', 'woocommerce' ),
				description: __(
					'Payment method description that the customer will see during checkout.',
					'woocommerce'
				),
				Edit: TextareaEdit,
			},
			{
				id: 'instructions',
				label: __( 'Instructions', 'woocommerce' ),
				description: __(
					'Instructions that will be added to the thank you page and emails.',
					'woocommerce'
				),
				Edit: TextareaEdit,
			},
			...getShippingRestrictionFields(
				codSettings,
				__( 'cash on delivery', 'woocommerce' )
			),
		],
		[ codSettings ]
	);

	const saveSettings = () => {
		if ( ! codSettings ) {
			return;
		}

		setIsSaving( true );

		const settings: Record< string, string | string[] > = {
			title: String( formValues.title ),
			instructions: String( formValues.instructions ),
			...getShippingRestrictionSettings( formValues ),
		};

		updatePaymentGateway( 'cod', {
			enabled: Boolean( formValues.enabled ),
			description: String( formValues.description ),
			settings,
		} )
			.then( () => {
				setHasChanges( false );
				void invalidateResolutionForStoreSelector(
					'getPaymentGateway'
				);
				createSuccessNotice(
					__( 'Settings updated successfully', 'woocommerce' )
				);
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
			} )
			.finally( () => {
				setIsSaving( false );
				void invalidateResolution( 'getPaymentProviders', [] );
				void invalidateResolutionForPaymentSettings(
					'getOfflinePaymentGateways'
				);
			} );
	};

	return (
		<Settings>
			<Settings.Layout>
				<Settings.Form
					onSubmit={ ( e ) => {
						e.preventDefault();
						saveSettings();
					} }
				>
					<Settings.Section
						title={ __( 'Enable and customise', 'woocommerce' ) }
						description={ __(
							'Choose how you want to present cash on delivery payments to your customers during checkout.',
							'woocommerce'
						) }
					>
						{ isLoading ? (
							<>
								<FieldPlaceholder size="small" />
								<FieldPlaceholder size="medium" />
								<FieldPlaceholder size="large" />
								<FieldPlaceholder size="large" />
								<FieldPlaceholder size="medium" />
								<FieldPlaceholder size="small" />
							</>
						) : (
							<DataForm
								data={ formValues }
								fields={ fields }
								form={ {
									layout: { type: 'regular' },
									fields: [
										'enabled',
										'title',
										'description',
										'instructions',
										'enable_for_methods',
										'enable_for_virtual',
									],
								} }
								onChange={ ( edits: OfflineFormValues ) => {
									setFormValues( ( values ) => ( {
										...values,
										...edits,
									} ) );
									setHasChanges( true );
								} }
							/>
						) }
					</Settings.Section>
					<Settings.Actions>
						<Button
							variant="primary"
							type="submit"
							isBusy={ isSaving }
							disabled={ isSaving || ! hasChanges }
						>
							{ __( 'Save changes', 'woocommerce' ) }
						</Button>
					</Settings.Actions>
				</Settings.Form>
			</Settings.Layout>
		</Settings>
	);
};

export default SettingsPaymentsCod;
