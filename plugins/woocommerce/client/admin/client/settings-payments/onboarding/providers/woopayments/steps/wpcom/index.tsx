/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';


/**
 * Internal dependencies
 */
import {
	useOnboardingContext,
} from '../../data/onboarding-context';
import WooPaymentsStepHeader from '../../components/header';
import './style.scss';

export const JetpackStep: React.FC = () => {
	const { currentStep } = useOnboardingContext();

	const connectionLink = 'http://woocommerce.com'; // Retrieve the URL from currentStep.

	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
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
						onClick={ () => {
							// TODO: Implement Jetpack connection logic
							window.location.href = connectionLink;
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
