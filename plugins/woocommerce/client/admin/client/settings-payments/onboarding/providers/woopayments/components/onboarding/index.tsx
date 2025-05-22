/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Route, Routes, useLocation } from 'react-router-dom';
import { useEffect } from 'react';

/**
 * Internal dependencies
 */
import StripeSpinner from '../stripe-spinner';
import Stepper from '~/settings-payments/onboarding/components/stepper';
import { useOnboardingContext } from '../../data/onboarding-context';

/**
 * WooPaymentsOnboarding component for the WooPayments onboarding modal.
 */
export default function WooPaymentsOnboarding(): React.ReactNode {
	const {
		steps,
		isLoading,
		currentStep,
		navigateToStep,
		justCompletedStepId,
	} = useOnboardingContext();

	const location = useLocation();

	// Forces navigation to the current step only if the URL does not already match.
	useEffect( () => {
		if (
			currentStep &&
			location.pathname !== ( currentStep?.path ?? '' )
		) {
			navigateToStep( currentStep.id );
		}
	}, [ currentStep, navigateToStep, location.pathname ] );

	// Displays a loading indicator if the content is still loading.
	if ( isLoading ) {
		return (
			<div className="settings-payments-onboarding-modal__loading">
				<StripeSpinner />
			</div>
		);
	}

	// Renders the Stepper if there are steps available.
	if ( steps && steps.length > 0 ) {
		return (
			<Routes>
				<Route
					path="/woopayments/onboarding/*"
					element={
						<div className="settings-payments-onboarding-modal__wrapper">
							<Stepper
								steps={ steps }
								active={ currentStep?.id ?? '' }
								justCompletedStepId={ justCompletedStepId }
								includeSidebar
								sidebarTitle={ __(
									'Set up WooPayments',
									'woocommerce'
								) }
							/>
						</div>
					}
				/>
			</Routes>
		);
	}

	return null;
}
