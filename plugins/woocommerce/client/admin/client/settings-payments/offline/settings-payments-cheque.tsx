/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentGatewaysStore } from '@woocommerce/data';
import { useState, useEffect } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { Settings } from '~/settings-payments/components/settings';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';
import { BackButton } from '../components/buttons/back-button';

/**
 * This page is used to manage the settings for the Cheque payment gateway.
 * Noting that we refer to it as 'cheque' in the code, but use the American English spelling
 * 'check' in the UI.
 */
export const SettingsPaymentsCheque = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );
	const { chequeSettings, isLoading } = useSelect(
		( select ) => ( {
			chequeSettings:
				select( paymentGatewaysStore ).getPaymentGateway( 'cheque' ),
			isLoading: ! select( paymentGatewaysStore ).hasFinishedResolution(
				'getPaymentGateway',
				[ 'cheque' ]
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
	const [ hasChanges, setHasChanges ] = useState( false );

	useEffect( () => {
		if ( chequeSettings ) {
			setFormValues( {
				enabled: chequeSettings.enabled,
				title: chequeSettings.settings.title.value,
				description: chequeSettings.description,
				instructions: chequeSettings.settings.instructions.value,
			} );
		}
	}, [ chequeSettings ] );

	const saveSettings = () => {
		if ( ! chequeSettings ) {
			return;
		}

		setIsSaving( true );

		const settings: Record< string, string > = {
			title: String( formValues.title ),
			instructions: String( formValues.instructions ),
		};

		updatePaymentGateway( 'cheque', {
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
				setHasChanges( false );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
				setIsSaving( false );
			} );
	};

	return (
		<div className="settings-payments-offline__container">
			<div className="settings-payments-offline__header">
				<BackButton
					href={ getNewPath( {}, '/offline' ) }
					title={ __(
						'Return to offline payments settings',
						'woocommerce'
					) }
					isRoute={ true }
					from={ 'woopayments_payment_methods' }
				/>
				<h1 className="components-truncate components-text woocommerce-layout__header-heading settings-payments-offline__header-title">
					<span className="woocommerce-settings-payments-header__title settings-payments-offline__header-title">
						{ __( 'Check payments', 'woocommerce' ) }
					</span>
				</h1>
			</div>
			<Settings>
				<Settings.Layout>
					<Settings.Form
						onSubmit={ ( e ) => {
							e.preventDefault();
							saveSettings();
						} }
					>
						<Settings.Section
							title={ __(
								'Enable and customise',
								'woocommerce'
							) }
							description={ __(
								'Choose how you want to present check payments to your customers during checkout.',
								'woocommerce'
							) }
						>
							{ isLoading ? (
								<FieldPlaceholder size="small" />
							) : (
								<CheckboxControl
									label={ __(
										'Enable check payments',
										'woocommerce'
									) }
									checked={ Boolean( formValues.enabled ) }
									onChange={ ( checked ) => {
										setFormValues( {
											...formValues,
											enabled: checked,
										} );
										setHasChanges( true );
									} }
								/>
							) }
							{ isLoading ? (
								<FieldPlaceholder size="medium" />
							) : (
								<TextControl
									label={ __( 'Title', 'woocommerce' ) }
									help={ __(
										'Payment method name that the customer will see during checkout.',
										'woocommerce'
									) }
									placeholder={ __(
										'Check payments',
										'woocommerce'
									) }
									value={ String( formValues.title ) }
									onChange={ ( value ) => {
										setFormValues( {
											...formValues,
											title: value,
										} );
										setHasChanges( true );
									} }
								/>
							) }
							{ isLoading ? (
								<FieldPlaceholder size="large" />
							) : (
								<TextareaControl
									label={ __( 'Description', 'woocommerce' ) }
									help={ __(
										'Payment method description that the customer will see during checkout.',
										'woocommerce'
									) }
									value={ String( formValues.description ) }
									onChange={ ( value ) => {
										setFormValues( {
											...formValues,
											description: value,
										} );
										setHasChanges( true );
									} }
								/>
							) }
							{ isLoading ? (
								<FieldPlaceholder size="large" />
							) : (
								<TextareaControl
									label={ __(
										'Instructions',
										'woocommerce'
									) }
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
		</div>
	);
};

export default SettingsPaymentsCheque;
