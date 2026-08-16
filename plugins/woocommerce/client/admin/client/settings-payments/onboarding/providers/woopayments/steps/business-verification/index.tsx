/**
 * External dependencies
 */
import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

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
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import type { OnboardingError } from '~/settings-payments/onboarding/types';

/**
 * Maximum number of individual error messages to display before showing a summary.
 */
const MAX_DISPLAYED_ERRORS = 3;

/**
 * Fallback error message used when an error object lacks a valid message.
 */
const FALLBACK_ERROR_MESSAGE = __(
	'Something went wrong. Please try again.',
	'woocommerce'
);

/**
 * Normalizes an error message string to ensure it's safe for rendering.
 * Returns the fallback message if the input is not a valid, non-empty string.
 *
 * @param message - The error message to normalize.
 * @return A safe string suitable for rendering.
 */
const normalizeErrorMessage = ( message: unknown ): string => {
	if ( typeof message === 'string' && message.trim().length > 0 ) {
		return message.trim();
	}
	return FALLBACK_ERROR_MESSAGE;
};

export const BusinessVerificationStep: React.FC = () => {
	const { currentStep, closeModal, sessionEntryPoint } =
		useOnboardingContext();

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
		country: currentStep?.context?.fields?.location,
		...( currentStep?.context?.self_assessment ?? {} ),
	};
	const hasTestAccount = currentStep?.context?.has_test_account ?? false;
	const hasSandboxAccount =
		currentStep?.context?.has_sandbox_account ?? false;

	// Only include the activate step if the user has:
	// - a test OR;
	// - a sandbox account and the business verification step is not started;
	//   this is due to the fact that a sandbox account goes through the same onboarding flow as a live account,
	//   but with test KYC data.
	// The activate step can handle disabling the test or sandbox account and proceed to live onboarding.
	const showActivateSubStep =
		hasTestAccount ||
		( hasSandboxAccount && currentStep?.status === 'not_started' );
	const subStepsList = [
		...( showActivateSubStep ? [ 'activate' ] : [] ),
		'business',
		'embedded',
	];

	// Find the first not completed sub-step.
	const initialStep = subStepsList.find( ( stepId ) => {
		return (
			currentStep?.context?.sub_steps[ stepId ]?.status !== 'completed'
		);
	} );

	const handleStepChange = () => {
		window.scroll( 0, 0 );
	};

	return (
		<div className="settings-payments-onboarding-modal__step-business-verification">
			<WooPaymentsStepHeader onClose={ closeModal } />
			<div className="settings-payments-onboarding-modal__step-business-verification-content">
				{ currentStep?.errors && currentStep.errors.length > 0 && (
					<Notice
						status="error"
						isDismissible={ false }
						className="settings-payments-onboarding-modal__step-business-verification-error"
						// Adding role="alert" for explicit screen reader announcement.
						// While @wordpress/components Notice uses speak() internally,
						// role="alert" provides better backwards compatibility with older AT.
						// Type assertion needed as Notice component types don't include standard HTML attributes.
						{ ...( {
							role: 'alert',
						} as React.HTMLAttributes< HTMLDivElement > ) }
					>
						{ currentStep.errors.length <= MAX_DISPLAYED_ERRORS ? (
							// Display individual error messages when count is manageable.
							( currentStep.errors as OnboardingError[] ).map(
								( error, index ) => (
									<p key={ error?.code ?? index }>
										{ normalizeErrorMessage(
											error?.message
										) }
									</p>
								)
							)
						) : (
							// Display a summary when there are too many errors.
							<>
								<p>
									{ sprintf(
										/* translators: %d: number of errors */
										_n(
											'%d error occurred during setup.',
											'%d errors occurred during setup.',
											currentStep.errors.length,
											'woocommerce'
										),
										currentStep.errors.length
									) }
								</p>
								<p>{ FALLBACK_ERROR_MESSAGE }</p>
							</>
						) }
					</Notice>
				) }
				<BusinessVerificationContextProvider
					initialData={ initialData }
				>
					<Stepper
						initialStep={ initialStep }
						onStepView={ ( stepId ) => {
							recordPaymentsOnboardingEvent(
								'woopayments_onboarding_modal_step_view',
								{
									step: currentStep?.id || 'unknown',
									sub_step_id: stepId,
									source: sessionEntryPoint,
								}
							);
						} }
						onStepChange={ handleStepChange }
						onExit={ () => {
							recordPaymentsOnboardingEvent(
								'woopayments_onboarding_modal_step_exit',
								{
									step: currentStep?.id || 'unknown',
									source: sessionEntryPoint,
								}
							);
						} }
						onComplete={ () => {
							recordPaymentsOnboardingEvent(
								'woopayments_onboarding_modal_step_complete',
								{
									step: currentStep?.id || 'unknown',
									source: sessionEntryPoint,
								}
							);
						} }
					>
						{ showActivateSubStep && (
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
