/**
 * External dependencies
 */
import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { LoadError } from '@stripe/connect-js';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../../data/onboarding-context';
import StripeSpinner from '../../../components/stripe-spinner';
import BannerNotice from '../../../components/banner-notice';
import { useBusinessVerificationContext } from '../data/business-verification-context';
import { finalizeEmbeddedKycSession } from '../utils/actions';
import { EmbeddedAccountOnboarding } from '../components/embedded';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';

interface Props {
	continueKyc?: boolean;
	collectPayoutRequirements?: boolean;
}

const EmbeddedKyc: React.FC< Props > = ( {
	collectPayoutRequirements = false,
} ) => {
	const { data } = useBusinessVerificationContext();
	const { currentStep, navigateToNextStep, sessionEntryPoint } =
		useOnboardingContext();
	const [ finalizingSession, setFinalizingSession ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState< LoadError | null >( null );
	const fallbackUrl = currentStep?.actions?.kyc_fallback?.href ?? '';

	const handleStepChange = ( step: string ) => {
		recordPaymentsOnboardingEvent(
			'woopayments_onboarding_modal_kyc_step_change',
			{
				kyc_step_id: step, // This is the Stripe Embedded KYC step ID.
				collect_payout_requirements: collectPayoutRequirements,
				source: sessionEntryPoint,
			}
		);
	};

	const handleOnExit = async () => {
		setFinalizingSession( true );

		try {
			const response = await finalizeEmbeddedKycSession(
				currentStep?.actions?.kyc_session_finish?.href ?? ''
			);

			if ( response.success ) {
				navigateToNextStep();
			} else {
				window.location.href = fallbackUrl;
			}
		} catch ( error ) {
			window.location.href = fallbackUrl;
		}
	};

	const handleLoaderStart = () => {
		recordPaymentsOnboardingEvent(
			'woopayments_onboarding_modal_kyc_started_loading',
			{
				collect_payout_requirements: collectPayoutRequirements,
				source: sessionEntryPoint,
			}
		);

		setLoading( false );
	};

	const handleLoadError = ( err: LoadError ) => {
		recordPaymentsOnboardingEvent(
			'woopayments_onboarding_modal_kyc_load_error',
			{
				error_type: err.error.type,
				error_message: err.error.message || 'no_message',
				collect_payout_requirements: collectPayoutRequirements,
				source: sessionEntryPoint,
			}
		);

		setLoadError( err );
	};

	return (
		<>
			{ loadError &&
				( loadError.error.type === 'invalid_request_error' ? (
					<BannerNotice
						className={ 'woopayments-banner-notice--embedded-kyc' }
						status="warning"
						isDismissible={ false }
						actions={ [
							{
								label: 'Learn more',
								variant: 'primary',
								url: 'https://woocommerce.com/document/woopayments/startup-guide/#requirements',
								urlTarget: '_blank',
							},
							{
								label: 'Cancel',
								variant: 'link',
								url: fallbackUrl,
							},
						] }
					>
						{ __(
							'Payment activation through our financial partner requires HTTPS and cannot be completed.',
							'woocommerce'
						) }
					</BannerNotice>
				) : (
					<BannerNotice
						className={ 'woopayments-banner-notice--embedded-kyc' }
						status="error"
						isDismissible={ false }
					>
						{ loadError.error.message }
					</BannerNotice>
				) ) }
			{ loading && (
				<div className="embedded-kyc-loader-wrapper padded">
					<StripeSpinner />
				</div>
			) }
			{ finalizingSession && (
				<div className="embedded-kyc-loader-wrapper">
					<StripeSpinner />
				</div>
			) }
			{
				<EmbeddedAccountOnboarding
					onExit={ handleOnExit }
					onStepChange={ handleStepChange }
					onLoaderStart={ handleLoaderStart }
					onLoadError={ handleLoadError }
					onboardingData={ data }
					collectPayoutRequirements={ collectPayoutRequirements }
				/>
			}
		</>
	);
};

export default EmbeddedKyc;
