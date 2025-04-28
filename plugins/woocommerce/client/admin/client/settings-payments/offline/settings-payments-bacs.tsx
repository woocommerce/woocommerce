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
import { useState } from '@wordpress/element';
import { paymentGatewaysStore, optionsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';
import { BankAccountsList } from '~/settings-payments/components/bank-accounts-list';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { GatewaySettingsForm } from '~/settings-payments/components/gateway-settings-form';

export const SettingsPaymentsBacs = () => {
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
			accountsOption: selectors.getOption( 'woocommerce_bacs_accounts' ),
			isLoadingAccounts: ! selectors.hasFinishedResolution( 'getOption', [
				'woocommerce_bacs_accounts',
			] ),
		};
	}, [] );

	const [ formValues, setFormValues ] = useState( {
		enabled: bacsSettings?.enabled ?? false,
		title: bacsSettings?.settings.title?.value ?? '',
		description: bacsSettings?.settings.description?.value ?? '',
		instructions: bacsSettings?.settings.instructions?.value ?? '',
	} );

	type BankAccount = {
		account_name: string;
		account_number: string;
		sort_code?: string;
		iban?: string;
		bic?: string;
		bank_name?: string;
	};

	const [ accounts, setAccounts ] = useState< BankAccount[] >(
		accountsOption?.value ?? []
	);

	const { updateOptions } = useDispatch( optionsStore );
	const { updatePaymentGateway } = useDispatch( paymentGatewaysStore );

	const saveSettings = async () => {
		if ( ! bacsSettings ) {
			return;
		}

		const settings: Record< string, string > = {
			title: formValues.title,
			description: formValues.description,
			instructions: formValues.instructions,
		};

		try {
			await Promise.all( [
				updateOptions( {
					woocommerce_bacs_accounts: accounts,
				} ),
				updatePaymentGateway( 'bacs', {
					enabled: formValues.enabled,
					description: formValues.description,
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
							setAccounts={ setAccounts }
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
