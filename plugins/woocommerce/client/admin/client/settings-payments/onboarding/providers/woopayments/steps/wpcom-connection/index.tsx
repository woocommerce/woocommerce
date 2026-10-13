/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../data/onboarding-context';
import WooPaymentsStepHeader from '../../components/header';
import './style.scss';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';

export const JetpackStep: React.FC = () => {
	const { currentStep, closeModal, sessionEntryPoint } =
		useOnboardingContext();
	const [ isConnectButtonLoading, setIsConnectButtonLoading ] =
		useState( false );
	const [ authorizeError, setAuthorizeError ] = useState< string | null >(
		null
	);

	return (
		<>
			<WooPaymentsStepHeader onClose={ closeModal } />
			{ ( authorizeError ||
				( currentStep?.errors && currentStep.errors.length > 0 ) ) && (
				<Notice
					status="error"
					isDismissible={ false }
					className="settings-payments-onboarding-modal__step-jetpack-error"
					// Adding role="alert" for explicit screen reader announcement.
					// While @wordpress/components Notice uses speak() internally,
					// role="alert" provides better backwards compatibility with older AT.
					{ ...{ role: 'alert' } }
				>
					<p>
						{ authorizeError ||
							currentStep?.errors?.[ 0 ]?.message ||
							__(
								'Something went wrong. Please try again.',
								'woocommerce'
							) }
					</p>
				</Notice>
			) }
			<div className="settings-payments-onboarding-modal__step--content">
				<div className="settings-payments-onboarding-modal__step--content-jetpack">
					<h1 className="settings-payments-onboarding-modal__step--content-jetpack-title">
						{ __( 'Connect to WordPress.com', 'woocommerce' ) }
					</h1>
					<p className="settings-payments-onboarding-modal__step--content-jetpack-description">
						{ __(
							'You’ll be briefly redirected to connect your store to your WordPress.com account and unlock the full features and functionality of WooPayments',
							'woocommerce'
						) }
					</p>
					<Button
						variant="primary"
						className="settings-payments-onboarding-modal__step--content-jetpack-button"
						isBusy={ isConnectButtonLoading }
						disabled={ isConnectButtonLoading }
						onClick={ () => {
							setIsConnectButtonLoading( true );

							// Mark the step as started.
							const startUrl = currentStep?.actions?.start?.href;
							if ( startUrl ) {
								// No need to wait for the response.
								void apiFetch( {
									url: startUrl,
									method: 'POST',
									data: {
										source: sessionEntryPoint,
									},
								} );
							}

							// Track the connection button click event.
							recordPaymentsOnboardingEvent(
								'woopayments_onboarding_modal_click',
								{
									step: currentStep?.id || 'unknown',
									action: 'connect_to_wpcom',
									source: sessionEntryPoint,
								}
							);

							const authorizeUrl =
								currentStep?.actions?.authorize?.href;
							if ( ! authorizeUrl ) {
								// Redirect to the WordPress.com connection authorization URL.
								window.location.href =
									currentStep?.actions?.auth?.href ?? '';
								return;
							}

							// The store is not registered with WordPress.com yet: register it,
							// then redirect to the authorization URL the backend returns.
							setAuthorizeError( null );
							apiFetch< { url: string } >( {
								url: authorizeUrl,
								method: 'POST',
								data: {
									source: sessionEntryPoint,
								},
							} )
								.then( ( response ) => {
									window.location.href = response.url;
								} )
								.catch( ( error ) => {
									setAuthorizeError(
										error?.message ||
											__(
												'Something went wrong. Please try again.',
												'woocommerce'
											)
									);
									setIsConnectButtonLoading( false );
								} );
						} }
					>
						{ __( 'Connect', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</>
	);
};

export default JetpackStep;
