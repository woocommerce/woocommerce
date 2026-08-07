/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { useState, useEffect, useMemo } from '@wordpress/element';
import {
	paymentGatewaysStore,
	optionsStore,
	paymentSettingsStore,
} from '@woocommerce/data';
import { DataForm } from '@wordpress/dataviews';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { Settings } from '~/settings-payments/components/settings';
import { FieldPlaceholder } from '~/settings-payments/components/field-placeholder';
import { BankAccountsList } from '~/settings-payments/components/bank-accounts-list';
import { BankAccount } from '~/settings-payments/components/bank-accounts-list/types';
import {
	CheckboxEdit,
	TextEdit,
	TextareaEdit,
	type OfflineFormValues,
} from './dataform-controls';

/**
 * This page is used to manage the settings for the BACS (Direct bank transfer) payment gateway.
 */
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

	const { invalidateResolution, invalidateResolutionForStoreSelector } =
		useDispatch( paymentSettingsStore );

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

	const [ formValues, setFormValues ] = useState< OfflineFormValues >( {} );

	const [ isSaving, setIsSaving ] = useState( false );
	const [ hasChanges, setHasChanges ] = useState( false );

	useEffect( () => {
		if ( bacsSettings ) {
			setFormValues( {
				enabled: bacsSettings.enabled,
				title: bacsSettings.settings.title.value,
				description: bacsSettings.description,
				instructions: bacsSettings.settings.instructions.value,
			} );
			setHasChanges( false );
		}
	}, [ bacsSettings ] );

	const [ accounts, setAccounts ] = useState< BankAccount[] >( [] );

	useEffect( () => {
		if ( accountsOption ) {
			setAccounts( accountsOption );
		}
	}, [ accountsOption ] );

	const { updateOptions } = useDispatch( optionsStore );
	const { updatePaymentGateway } = useDispatch( paymentGatewaysStore );

	const fields: Field< OfflineFormValues >[] = useMemo(
		() => [
			{
				id: 'enabled',
				label: __( 'Enable direct bank transfers', 'woocommerce' ),
				Edit: CheckboxEdit,
			},
			{
				id: 'title',
				label: __( 'Title', 'woocommerce' ),
				description: __(
					'Payment method name that the customer will see during checkout.',
					'woocommerce'
				),
				placeholder: __(
					'Direct bank transfer payments',
					'woocommerce'
				),
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
		],
		[]
	);

	const saveSettings = async () => {
		if ( ! bacsSettings ) {
			return;
		}

		setIsSaving( true );
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
							country_code,
						} ) => ( {
							account_name,
							account_number,
							bank_name,
							sort_code,
							iban,
							bic,
							country_code,
						} )
					),
				} ),
				updatePaymentGateway( 'bacs', {
					enabled: Boolean( formValues.enabled ),
					description: String( formValues.description ),
					settings,
				} ),
			] );
			setHasChanges( false );
			createSuccessNotice(
				__( 'Settings updated successfully', 'woocommerce' )
			);
		} catch ( error ) {
			createErrorNotice(
				__( 'Failed to update settings', 'woocommerce' )
			);
		} finally {
			setIsSaving( false );
			void invalidateResolution( 'getPaymentProviders', [] );
			void invalidateResolutionForStoreSelector(
				'getOfflinePaymentGateways'
			);
		}
	};

	return (
		<Settings>
			<Settings.Layout>
				<Settings.Form
					onSubmit={ ( e ) => {
						e.preventDefault();
						void saveSettings();
					} }
				>
					<Settings.Section
						title={ __( 'Enable and customise', 'woocommerce' ) }
						description={ __(
							'Choose how you want to present bank transfer to your customers during checkout.',
							'woocommerce'
						) }
					>
						{ isLoading ? (
							<>
								<FieldPlaceholder size="small" />
								<FieldPlaceholder size="medium" />
								<FieldPlaceholder size="large" />
								<FieldPlaceholder size="large" />
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

					<Settings.Section
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
								onChange={ ( bankAccounts ) => {
									setAccounts( bankAccounts );
									setHasChanges( true );
								} }
								defaultCountry={ storeCountryCode }
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

export default SettingsPaymentsBacs;
