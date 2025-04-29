/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	CheckboxControl,
	TextControl,
	TextareaControl,
	CardBody,
	Button,
	Card,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { paymentGatewaysStore, optionsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import './settings-payments-offline-method.scss';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';
import { BankAccountsList } from '~/settings-payments/components/bank-accounts-list';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { GatewaySettingsForm } from '~/settings-payments/components/gateway-settings-form';
import { BankAccount } from '~/settings-payments/components/bank-accounts-list/types';

export const SettingsPaymentsBacs = () => {
	const storeCountryCode =
		window.wcSettings?.admin?.preloadSettings?.general
			?.woocommerce_default_country || 'US';

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const { bacsSettings, isLoading } = useSelect(
		( select ) => ( {
			bacsSettings:
				select( paymentGatewaysStore ).getPaymentGateway( 'bacs' ),
			isLoading: ! select( paymentGatewaysStore ).hasFinishedResolution(
				'getPaymentGateway',
				[ 'bacs' ]
			),
		} ),
		[]
	);

	const { accountsOption, isLoadingAccounts } = useSelect( ( select ) => {
		const selectors = select( optionsStore );

		return {
			accountsOption: selectors.getOption(
				'woocommerce_bacs_accounts'
			) as BankAccount[] | undefined,
			isLoadingAccounts: ! selectors.hasFinishedResolution( 'getOption', [
				'woocommerce_bacs_accounts',
			] ),
		};
	}, [] );

	const [ formValues, setFormValues ] = useState<
		Record< string, string | boolean | string[] >
	>( {} );

	console.log( bacsSettings );
	useEffect( () => {
		if ( bacsSettings ) {
			setFormValues( {
				enabled: bacsSettings.enabled,
				title: bacsSettings.settings.title.value,
				description: bacsSettings.description,
				instructions: bacsSettings.settings.instructions.value,
			} );
		}
	}, [ bacsSettings ] );

	const [ accounts, setAccounts ] = useState< BankAccount[] >( [] );

	useEffect( () => {
		if ( accountsOption ) {
			setAccounts( accountsOption );
		}
	}, [ accountsOption ] );

	console.log( accountsOption );
	console.log( bacsSettings );

	const { updateOptions } = useDispatch( optionsStore );
	const { updatePaymentGateway } = useDispatch( paymentGatewaysStore );

	const saveSettings = async () => {
		if ( ! bacsSettings ) {
			return;
		}

		const settings: Record< string, string | string[] > = {
			title: String( formValues.title ),
			instructions: String( formValues.instructions ),
		};

		try {
			await Promise.all( [
				updateOptions( {
					woocommerce_bacs_accounts: accounts.map(
						( {
							account_name,
							account_number,
							bank_name,
							sort_code,
							iban,
							bic,
						} ) => ( {
							account_name,
							account_number,
							bank_name,
							sort_code,
							iban,
							bic,
						} )
					),
				} ),
				updatePaymentGateway( 'bacs', {
					enabled: Boolean( formValues.enabled ),
					description: String( formValues.description ),
					settings,
				} ),
			] );
			createSuccessNotice(
				__( 'Settings updated successfully', 'woocommerce' )
			);
		} catch ( error ) {
			createErrorNotice(
				__( 'Failed to update settings', 'woocommerce' )
			);
		}
	};

	return (
		<PaymentSettingsLayout>
			<GatewaySettingsForm>
				<PaymentSettingsSection
					title={ __( 'Enable and customise', 'woocommerce' ) }
					description={ __(
						'Choose how you want to present bank transfer to your customers during checkout.',
						'woocommerce'
					) }
				>
					{ isLoading ? (
						<FieldPlaceholder size="small" />
					) : (
						<CheckboxControl
							label={ __(
								'Enable direct bank transfers',
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
								'Direct bank transfer payments',
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

				<PaymentSettingsSection
					title={ __( 'Account details', 'woocommerce' ) }
					description={ __(
						'Configure your bank account details.',
						'woocommerce'
					) }
				>
					{ isLoadingAccounts ? (
						<FieldPlaceholder size="large" />
					) : (
						<BankAccountsList
							accounts={ accounts }
							onChange={ setAccounts }
							defaultCountry={ storeCountryCode }
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

export default SettingsPaymentsBacs;
