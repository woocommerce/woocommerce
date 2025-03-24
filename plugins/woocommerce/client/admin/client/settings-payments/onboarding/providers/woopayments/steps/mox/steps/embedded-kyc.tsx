/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { LoadError } from '@stripe/connect-js';

/**
 * Internal dependencies
 */
import {
	useOnboardingContext,
} from '../../../data/onboarding-context';
import StripeSpinner from '../../../components/stripe-spinner';
import BannerNotice from '../../../components/banner-notice';
import { useMOXContext } from '../data/mox-context';
import { finalizeOnboarding, isPoEligible } from '../utils';
import { trackEmbeddedStepChange } from '../utils/tracking';
import { EmbeddedAccountOnboarding } from '../components/embedded';

interface Props {
	continueKyc?: boolean;
	collectPayoutRequirements?: boolean;
}

const EmbeddedKyc: React.FC< Props > = ( {
	continueKyc = false,
	collectPayoutRequirements = false,
} ) => {
	const { data } = useMOXContext();
	const { navigateToNextStep } = useOnboardingContext();
	const [ finalizingAccount, setFinalizingAccount ] = useState( false );
	const [ isEligible, setIsEligible ] = useState< boolean | null >( null );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState< LoadError | null >( null );

	// Fetch whether the account is eligible for progressive onboarding
	useEffect( () => {
		const checkEligibility = async () => {
			const eligibility = await isPoEligible( data );
			setIsEligible( eligibility );
		};

		if ( ! continueKyc ) {
			checkEligibility();
		} else {
			setIsEligible( false );
		}
	}, [ continueKyc, data ] );

	const handleStepChange = ( step: string ) => {
		trackEmbeddedStepChange( step );
	};

	const handleOnExit = async () => {
		setFinalizingAccount( true );

		try {
			const response = await finalizeOnboarding( 'NOX' ); // To-Do: Replace with the source.

			if ( response.success ) {
				navigateToNextStep();
			}
		} catch ( error ) {
			// To-Do: Handle error.
		}
	};

	const handleLoadError = ( err: LoadError ) => {
		setLoadError( err );
	};

	return (
		<>
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
								url:
									'https://woocommerce.com/document/woopayments/startup-guide/#requirements',
								urlTarget: '_blank',
							},
							{
								label: 'Cancel',
								variant: 'link',
								url: '', // To-Do: Reaplce with cancel URL
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
			{
				// Only render the embedded onboarding component once the PO eligibility has been determined.
				isEligible !== null && (
					<EmbeddedAccountOnboarding
						onExit={ handleOnExit }
						onStepChange={ handleStepChange }
						onLoaderStart={ () => setLoading( false ) }
						onLoadError={ handleLoadError }
						isPoEligible={ isEligible }
						onboardingData={ data }
						collectPayoutRequirements={ collectPayoutRequirements }
					/>
				)
			}
		</>
	);
};

export default EmbeddedKyc;
