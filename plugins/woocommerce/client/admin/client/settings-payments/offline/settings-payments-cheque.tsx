/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	Button,
	CheckboxControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentGatewaysStore } from '@woocommerce/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { GatewaySettingsForm } from '~/settings-payments/components/gateway-settings-form';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';

/**
 * Component for managing Cheque payment gateway settings.
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
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
			} );
	};

	return (
		<PaymentSettingsLayout>
			<GatewaySettingsForm>
				<PaymentSettingsSection
					title={ __( 'Enable and customise', 'woocommerce' ) }
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
								'Check payments',
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
				</PaymentSettingsSection>
			</GatewaySettingsForm>
			<Card className={ 'payment-settings-card__wrapper ' }>
				<CardBody className={ 'form__actions' }>
					<Button variant={ 'primary' } onClick={ saveSettings }>
						{ __( 'Save changes', 'woocommerce' ) }
					</Button>
				</CardBody>
			</Card>
		</PaymentSettingsLayout>
	);
};

export default SettingsPaymentsCheque;
