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
export default function WooPaymentsOnboarding( {
	includeSidebar = true,
}: {
	includeSidebar?: boolean;
} ): React.ReactNode {
	const {
		steps,
		isLoading,
		currentTopLevelStep,
		currentStep,
		navigateToStep,
		justCompletedStepId,
		sessionEntryPoint,
	} = useOnboardingContext();

	const location = useLocation();

	// Forces navigation to the current step only if the URL does not already match.
	useEffect( () => {
		if (
			currentTopLevelStep &&
			! location.pathname.endsWith( currentTopLevelStep?.path ?? '' )
		) {
			navigateToStep( currentTopLevelStep.id );
		}
	}, [ currentTopLevelStep, navigateToStep, location.pathname ] );

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
					path="*"
					element={
						<div className="settings-payments-onboarding-modal__wrapper">
							<Stepper
								steps={ steps }
								activeTopLevelStep={
									currentTopLevelStep?.id ?? ''
								}
								activeSubStep={ currentStep }
								justCompletedStepId={ justCompletedStepId }
								includeSidebar={ includeSidebar }
								sidebarTitle={ __(
									'Set up WooPayments',
									'woocommerce'
								) }
								context={ {
									sessionEntryPoint,
								} }
							/>
						</div>
					}
				/>
			</Routes>
		);
	}

	return null;
}
