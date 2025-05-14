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
import BusinessDetails from './sections/business-details';
import EmbeddedKyc from './sections/embedded-kyc';
import ActivatePayments from './sections/activate-payments';
import { Stepper } from './components/stepper';
import Step from './components/step';
import { getMccFromIndustry, getComingSoonShareKey } from './utils';
import './style.scss';
export const BusinessVerificationStep: React.FC = () => {
	const { currentStep, closeModal } = useOnboardingContext();

	const initialData = {
		business_name: window.wcSettings?.siteTitle,
		mcc: getMccFromIndustry(
			( currentStep?.context?.fields?.mccs_display_tree ??
				[] ) as string[]
		),
		site:
			location.hostname === 'localhost'
				? 'https://wcpay.test'
				: window.wcSettings?.homeUrl + getComingSoonShareKey(),
		country: (
			window.wcSettings?.admin?.preloadSettings?.general
				?.woocommerce_default_country || 'US'
		).split( ':' )[ 0 ],
		...( currentStep?.context?.self_assessment ?? {} ),
	};
	const hasTestAccount = currentStep?.context?.has_test_account ?? false;

	const handleStepChange = () => window.scroll( 0, 0 );

	return (
		<div className="settings-payments-onboarding-modal__step-business-verification">
			<WooPaymentsStepHeader onClose={ closeModal } />
			<div className="settings-payments-onboarding-modal__step-business-verification-content">
				<BusinessVerificationContextProvider
					initialData={ initialData }
				>
					<Stepper
						onStepChange={ handleStepChange }
						onExit={ () => {} }
					>
						{ hasTestAccount && (
							<Step name="activate" showHeading={ false }>
								<ActivatePayments />
							</Step>
						) }
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
