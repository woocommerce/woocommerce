/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../data/onboarding-context';
import WooPaymentsStepHeader from '../../components/header';
import { BusinessVerificationContextProvider } from './data/business-verification-context';
import { OnboardingForm } from './components/form';
import BusinessDetails from './steps/business-details';
import EmbeddedKyc from './steps/embedded-kyc';
import { Stepper } from './components/stepper';
import Step from './components/step';
import { getMccFromIndustry } from './utils';
import './style.scss';
export const BusinessVerificationStep: React.FC = () => {
	const { currentStep } = useOnboardingContext();

	const initialData = {
		business_name: 'Test', // To-Do: Replace with wcSettings?.siteTitle,
		mcc: getMccFromIndustry(
			( currentStep?.context?.fields?.mccs_display_tree ??
				[] ) as string[]
		),
		site: 'https://wcpay.test', // To-Do: Replace with URL
		country: 'US', // To-Do: Replace with country from WooCommerce settings
		...( currentStep?.context?.self_assessment ?? {} ),
	};

	const handleStepChange = () => window.scroll( 0, 0 );

	return (
		<div className="settings-payments-onboarding-modal__step-business-verification">
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step-business-verification-content">
				<BusinessVerificationContextProvider
					initialData={ initialData }
				>
					<Stepper
						onStepChange={ handleStepChange }
						onExit={ () => {} }
					>
						<Step name="business">
							<OnboardingForm>
								<BusinessDetails />
							</OnboardingForm>
						</Step>
						<Step name="embedded" showHeading={ false }>
							<EmbeddedKyc />
						</Step>
					</Stepper>
				</BusinessVerificationContextProvider>
			</div>
		</div>
	);
};

export default BusinessVerificationStep;
