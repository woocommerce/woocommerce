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

export const MOXStep: React.FC = () => {
	const { currentStep } = useOnboardingContext();

	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step--content">
				<div className="settings-payments-onboarding-modal__step--content-mox">
					<h1 className="settings-payments-onboarding-modal__step--content-mox-title">
						{ __( 'Let’s get your store ready to accept payments', 'woocommerce' ) }
					</h1>
					<p className="settings-payments-onboarding-modal__step--content-mox-description">
						{ __(
							'We’ll use these details to enable payments for your store. This information can’t be changed after your account is created.',
							'woocommerce'
						) }
					</p>
				</div>
			</div>
		</>
	);
};

export default MOXStep;