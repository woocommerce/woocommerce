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

	const {
		invalidateResolution,
		invalidateResolutionForStoreSelector:
			invalidateResolutionForPaymentSettings,
	} = useDispatch( paymentSettingsStore );

	const [ formValues, setFormValues ] = useState< OfflineFormValues >( {} );
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

	const fields: Field< OfflineFormValues >[] = useMemo(
		() => [
			{
				id: 'enabled',
				label: __( 'Enable check payments', 'woocommerce' ),
				Edit: CheckboxEdit,
			},
			{
				id: 'title',
				label: __( 'Title', 'woocommerce' ),
				description: __(
					'Payment method name that the customer will see during checkout.',
					'woocommerce'
				),
				placeholder: __( 'Check payments', 'woocommerce' ),
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
							'Choose how you want to present check payments to your customers during checkout.',
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

export default SettingsPaymentsCheque;
