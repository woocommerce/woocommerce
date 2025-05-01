/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { TreeSelectControl } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentGatewaysStore } from '@woocommerce/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { mapShippingMethodsOptions } from '~/settings-payments/offline/utils';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';
import { Settings } from '~/settings-payments/components/settings';

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

	const [ formValues, setFormValues ] = useState<
		Record< string, string | boolean | string[] >
	>( {} );
	const [ isSaving, setIsSaving ] = useState( false );

	useEffect( () => {
		if ( codSettings ) {
			setFormValues( {
				enabled: codSettings.enabled,
				title: codSettings.settings.title.value,
				description: codSettings.description,
				instructions: codSettings.settings.instructions.value,
				enable_for_methods:
					codSettings.settings.enable_for_methods.value,
				enable_for_virtual:
					codSettings.settings.enable_for_virtual.value,
			} );
		}
	}, [ codSettings ] );

	const saveSettings = () => {
		if ( ! codSettings ) {
			return;
		}

		setIsSaving( true );

		const settings: Record< string, string | string[] > = {
			title: String( formValues.title ),
			instructions: String( formValues.instructions ),
			enable_for_methods: Array.isArray( formValues.enable_for_methods )
				? formValues.enable_for_methods
				: [],
			enable_for_virtual: String( formValues.enable_for_virtual ),
		};

		updatePaymentGateway( 'cod', {
			enabled: Boolean( formValues.enabled ),
			description: String( formValues.description ),
			settings,
		} )
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentGateway' );
				createSuccessNotice(
					__( 'Settings updated successfully', 'woocommerce' )
				);
				setIsSaving( false );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
				setIsSaving( false );
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
							<FieldPlaceholder size="small" />
						) : (
							<CheckboxControl
								label={ __(
									'Enable cash on delivery payments',
									'woocommerce'
								) }
								checked={ Boolean( formValues.enabled ) }
								onChange={ ( checked ) => {
									setFormValues( {
										...formValues,
										enabled: checked,
									} );
								} }
							/>
						) }
						{ isLoading ? (
							<FieldPlaceholder size="medium" />
						) : (
							<TextControl
								label={ __( 'Title', 'woocommerce' ) }
								help={ __(
									'This controls the title which the user sees during checkout.',
									'woocommerce'
								) }
								placeholder={ __(
									'Cash on delivery payments',
									'woocommerce'
								) }
								value={ String( formValues.title ) }
								onChange={ ( value ) => {
									setFormValues( {
										...formValues,
										title: value,
									} );
								} }
							/>
						) }
						{ isLoading ? (
							<FieldPlaceholder size="large" />
						) : (
							<TextareaControl
								label={ __( 'Description', 'woocommerce' ) }
								help={ __(
									'Payment method description that the customer will see on your checkout.',
									'woocommerce'
								) }
								value={ String( formValues.description ) }
								onChange={ ( value ) => {
									setFormValues( {
										...formValues,
										description: value,
									} );
								} }
							/>
						) }
						{ isLoading ? (
							<FieldPlaceholder size="large" />
						) : (
							<TextareaControl
								label={ __( 'Instructions', 'woocommerce' ) }
								help={ __(
									'Instructions that will be added to the thank you page and emails.',
									'woocommerce'
								) }
								value={ String( formValues.instructions ) }
								onChange={ ( value ) => {
									setFormValues( {
										...formValues,
										instructions: value,
									} );
								} }
							/>
						) }
						{ isLoading || ! codSettings ? (
							<FieldPlaceholder size="medium" />
						) : (
							<TreeSelectControl
								label={ __(
									'Enable for shipping methods',
									'woocommerce'
								) }
								help={ __(
									'Select shipping methods for which this payment method is enabled.',
									'woocommerce'
								) }
								options={
									codSettings.settings.enable_for_methods?.options
										? mapShippingMethodsOptions(
												codSettings.settings
													.enable_for_methods.options
										  )
										: []
								}
								value={ formValues.enable_for_methods as string[] }
								onChange={ ( value: string[] ) => {
									setFormValues( {
										...formValues,
										enable_for_methods: value,
									} );
								} }
								selectAllLabel={ false }
							/>
						) }
						{ isLoading ? (
							<FieldPlaceholder size="small" />
						) : (
							<CheckboxControl
								label={ __(
									'Accept for virtual orders',
									'woocommerce'
								) }
								help={ __(
									'Accept cash on delivery if the order is virtual',
									'woocommerce'
								) }
								checked={ Boolean( formValues.enable_for_virtual ) }
								onChange={ ( checked ) => {
									setFormValues( {
										...formValues,
										enable_for_virtual: checked,
									} );
								} }
							/>
						) }
					</Settings.Section>
					<Settings.Actions>
						<Button variant="primary" type="submit" isBusy={ isSaving } disabled={ isSaving }>
							{ __( 'Save changes', 'woocommerce' ) }
						</Button>
					</Settings.Actions>
				</Settings.Form>
			</Settings.Layout>
		</Settings>
	);
};

export default SettingsPaymentsCod;
