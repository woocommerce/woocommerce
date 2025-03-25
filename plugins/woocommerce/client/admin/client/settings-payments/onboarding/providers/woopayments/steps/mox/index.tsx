/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	useOnboardingContext,
} from '../../data/onboarding-context';
import WooPaymentsStepHeader from '../../components/header';
import { MOXContextProvider } from './data/mox-context';
import { OnboardingForm } from './components/form';
import BusinessDetails from './steps/business-details';
import EmbeddedKyc from './steps/embedded-kyc';
import { Stepper } from './components/stepper';
import Step from './components/step';
import { getMccFromIndustry } from './utils';
import { MccsDisplayTreeItem } from './types'; 
import './style.scss';

export const MOXStep: React.FC = () => {
	const { currentStep } = useOnboardingContext();

    const initialData = {
        business_name: 'Test', // To-Do: Replace with wcSettings?.siteTitle,
        mcc: getMccFromIndustry( ( currentStep?.context?.fields?.mccs_display_tree ?? [] ) as MccsDisplayTreeItem[] ),
        site: 'https://wcpay.test', // To-Do: Replace with URL
        country: 'US', // To-Do: Replace with country from WooCommerce settings
    };

    const handleStepChange = () => window.scroll( 0, 0 );

	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />

            <div className="settings-payments-onboarding-modal__step-mox-content">
                <MOXContextProvider initialData={ initialData }>
                    <Stepper onStepChange={ handleStepChange } onExit={ () => {} }>
                        <Step name="business">
                            <OnboardingForm>
                                <BusinessDetails />
                            </OnboardingForm>
                        </Step>
                        <Step name="embedded" showHeading={ false }>
                            <EmbeddedKyc />
                        </Step>
                    </Stepper>
                </MOXContextProvider>
            </div>
		</>
	);
};

export default MOXStep;