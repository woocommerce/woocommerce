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
import { finalizeOnboarding } from '../utils/actions';
import { EmbeddedAccountOnboarding } from '../components/embedded';

interface Props {
	continueKyc?: boolean;
	collectPayoutRequirements?: boolean;
}

const EmbeddedKyc: React.FC< Props > = ( {
	collectPayoutRequirements = false,
} ) => {
	const { data } = useBusinessVerificationContext();
	const { currentStep, navigateToNextStep } = useOnboardingContext();
	const [ finalizingAccount, setFinalizingAccount ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState< LoadError | null >( null );
	const fallbackUrl = currentStep?.actions?.kyc_fallback?.href ?? '';

	const handleStepChange = () => {
		// To-Do: Track step change.
	};

	const handleOnExit = async () => {
		setFinalizingAccount( true );

		try {
			const response = await finalizeOnboarding(
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

	const handleLoadError = ( err: LoadError ) => {
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
			{ finalizingAccount && (
				<div className="embedded-kyc-loader-wrapper">
					<StripeSpinner />
				</div>
			) }
			{
				<EmbeddedAccountOnboarding
					onExit={ handleOnExit }
					onStepChange={ handleStepChange }
					onLoaderStart={ () => setLoading( false ) }
					onLoadError={ handleLoadError }
					onboardingData={ data }
					collectPayoutRequirements={ collectPayoutRequirements }
				/>
			}
		</>
	);
};

export default EmbeddedKyc;
