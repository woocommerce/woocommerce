/**
 * External dependencies
 */
import { useCallback } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React, { useState } from '@wordpress/element';
import { pluginsStore, paymentSettingsStore } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';
import { WooPaymentsMethodsLogos } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import WooPaymentsOnboarding from '~/settings-payments/onboarding/providers/woopayments/components/onboarding';
import { useOnboardingContext } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';
import StripeSpinner from '~/settings-payments/onboarding/providers/woopayments/components//stripe-spinner';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './payments-content.scss';

const InstallWooPaymentsStep = ( {
	installWooPayments,
	isPluginInstalling,
}: {
	installWooPayments: () => void;
	isPluginInstalling: boolean;
} ) => {
	const isWooPayEligible = useSelect( ( select ) => {
		const store = select( paymentSettingsStore );
		return store.getIsWooPayEligible();
	}, [] );
	return (
		<div className="launch-your-store-payments-content__step--install-woopayments">
			<div className="launch-your-store-payments-content__step--install-woopayments-logo">
				<img
					src={ `${ WC_ASSET_URL }images/woo-logo.svg` }
					alt="Woo Logo"
				/>
			</div>
			<h1 className="launch-your-store-payments-content__step--install-woopayments-title">
				{ __( 'Accept payments with Woo', 'woocommerce' ) }
			</h1>
			<p className="launch-your-store-payments-content__step--install-woopayments-description">
				{ __(
					'Set up payments for your store in just a few steps. With WooPayments, you can accept online and in-person payments, track revenue, and handle all payment activity from one place.',
					'woocommerce'
				) }
			</p>
			<div className="launch-your-store-payments-content__step--install-woopayments-logos">
				<WooPaymentsMethodsLogos
					maxElements={ 10 }
					isWooPayEligible={ isWooPayEligible }
				/>
			</div>
			<Button
				className="launch-your-store-payments-content__step--install-woopayments-button"
				onClick={ () => {
					installWooPayments();
				} }
				isBusy={ isPluginInstalling }
				disabled={ isPluginInstalling }
				variant="primary"
			>
				{ __( 'Install', 'woocommerce' ) }
			</Button>
		</div>
	);
};

export const PaymentsContent = ( {} ) => {
	const {
		refreshStoreData,
		isStoreLoading,
		isWooPaymentsEnabled,
		setWooPaymentsRecentlyEnabled,
	} = useOnboardingContext();

	const [ isPluginInstalling, setIsPluginInstalling ] =
		useState< boolean >( false );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );

	const installWooPayments = useCallback( () => {
		// Set the plugin installation state to true to show a loading indicator.
		setIsPluginInstalling( true );

		// Install and activate the WooPayments plugin.
		installAndActivatePlugins( [ 'woocommerce-payments' ] )
			.then( async () => {
				setWooPaymentsRecentlyEnabled( true );
				// Refresh store data after installation.
				// This will trigger a re-render and initialize the onboarding flow.
				refreshStoreData();
				setIsPluginInstalling( false );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				// Handle errors during installation

				setIsPluginInstalling( false );
			} );
	}, [
		setIsPluginInstalling,
		installAndActivatePlugins,
		refreshStoreData,
		setWooPaymentsRecentlyEnabled,
	] );

	return (
		<div className="launch-your-store-payments-content">
			<div className="launch-your-store-payments-content__canvas">
				{ isStoreLoading && (
					<div className="settings-payments-onboarding-modal__loading">
						<StripeSpinner />
					</div>
				) }
				{ ! isWooPaymentsEnabled ? (
					<InstallWooPaymentsStep
						installWooPayments={ installWooPayments }
						isPluginInstalling={ isPluginInstalling }
					/>
				) : (
					<WooPaymentsOnboarding includeSidebar={ false } />
				) }
			</div>
		</div>
	);
};
