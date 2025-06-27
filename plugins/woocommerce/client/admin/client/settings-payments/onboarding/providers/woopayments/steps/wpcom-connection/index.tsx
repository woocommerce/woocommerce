/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

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

	return (
		<>
			<WooPaymentsStepHeader onClose={ closeModal } />
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
							recordPaymentsOnboardingEvent(
								'woopayments_onboarding_modal_click',
								{
									step: currentStep?.id || 'unknown',
									action: 'connect_to_wpcom',
									source: sessionEntryPoint,
								}
							);

							setIsConnectButtonLoading( true );
							window.location.href =
								currentStep?.actions?.auth?.href ?? '';
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
