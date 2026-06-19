/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef, useState } from 'react';
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
import {
	type EmbeddedAccountInitializationFailure,
	EmbeddedAccountOnboarding,
} from '../components/embedded';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';

interface Props {
	continueKyc?: boolean;
	collectPayoutRequirements?: boolean;
}

type EmbeddedKycLoadFailureReason =
	| 'timeout'
	| 'load_error'
	| 'bad_session'
	| 'init_error';

type EmbeddedKycLoadFailure = {
	reason: EmbeddedKycLoadFailureReason;
	message?: string;
	errorType?: string;
	receivedKeys?: string[];
};

const embeddedKycLoadTimeoutMs = 20000;
const embeddedKycTroubleshootingUrl =
	'https://woocommerce.com/document/woopayments/startup-guide/#requirements';
const embeddedKycFailureMessage = __(
	"Unable to start onboarding. This may be caused by your site's security or server configuration. Please check our documentation and contact support if this error persists.",
	'woocommerce'
);

const getFailureTrackingDetails = (
	failure: EmbeddedKycLoadFailure
): Record< string, string > => {
	const details: Record< string, string > = {};

	if ( failure.errorType ) {
		details.error_type = failure.errorType;
	}

	if ( failure.message ) {
		details.error_message = failure.message;
	}

	if ( failure.receivedKeys ) {
		details.received_keys = failure.receivedKeys.join( ',' ) || 'none';
	}

	return details;
};

const EmbeddedKyc: React.FC< Props > = ( {
	collectPayoutRequirements = false,
} ) => {
	const { data } = useBusinessVerificationContext();
	const {
		currentStep,
		navigateToNextStep,
		sessionEntryPoint: onboardingSource,
	} = useOnboardingContext();
	const [ finalizingSession, setFinalizingSession ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	const [ loadFailure, setLoadFailure ] =
		useState< EmbeddedKycLoadFailure | null >( null );
	const loadFailureRef = useRef( false );
	const fallbackUrl = currentStep?.actions?.kyc_fallback?.href ?? '';

	const failEmbeddedKycLoad = useCallback(
		( failure: EmbeddedKycLoadFailure ) => {
			if ( loadFailureRef.current ) {
				return;
			}

			loadFailureRef.current = true;
			setLoading( false );
			setLoadFailure( failure );
			recordPaymentsOnboardingEvent(
				'woopayments_onboarding_modal_kyc_load_failed',
				{
					reason: failure.reason,
					collect_payout_requirements: collectPayoutRequirements,
					source: onboardingSource,
					...getFailureTrackingDetails( failure ),
				}
			);
		},
		[ collectPayoutRequirements, onboardingSource ]
	);

	useEffect( () => {
		if ( ! loading || loadFailure || finalizingSession ) {
			return;
		}

		const timerId = window.setTimeout( () => {
			failEmbeddedKycLoad( { reason: 'timeout' } );
		}, embeddedKycLoadTimeoutMs );

		return () => window.clearTimeout( timerId );
	}, [ failEmbeddedKycLoad, finalizingSession, loadFailure, loading ] );

	const handleStepChange = ( step: string ) => {
		recordPaymentsOnboardingEvent(
			'woopayments_onboarding_modal_kyc_step_change',
			{
				kyc_step_id: step, // This is the Stripe Embedded KYC step ID.
				collect_payout_requirements: collectPayoutRequirements,
				source: onboardingSource,
			}
		);
	};

	const handleOnExit = async () => {
		setFinalizingSession( true );

		try {
			const response = await finalizeEmbeddedKycSession(
				currentStep?.actions?.kyc_session_finish?.href ?? '',
				onboardingSource
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
		if ( loadFailureRef.current ) {
			return;
		}

		recordPaymentsOnboardingEvent(
			'woopayments_onboarding_modal_kyc_started_loading',
			{
				collect_payout_requirements: collectPayoutRequirements,
				source: onboardingSource,
			}
		);

		setLoading( false );
	};

	const handleLoadError = ( err: LoadError ) => {
		failEmbeddedKycLoad( {
			reason: 'load_error',
			errorType: err.error.type,
			message: err.error.message || 'no_message',
		} );
	};

	const handleInitializationError = (
		failure: EmbeddedAccountInitializationFailure
	) => {
		failEmbeddedKycLoad( failure );
	};

	return (
		<>
			{ loadFailure && (
				<BannerNotice
					className="woopayments-banner-notice--embedded-kyc"
					status="error"
					isDismissible={ false }
					actions={ [
						{
							label: __( 'Learn more', 'woocommerce' ),
							variant: 'primary',
							url: embeddedKycTroubleshootingUrl,
							urlTarget: '_blank',
						},
						{
							label: __( 'Cancel', 'woocommerce' ),
							variant: 'link',
							url: fallbackUrl,
						},
					] }
				>
					{ embeddedKycFailureMessage }
				</BannerNotice>
			) }
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
			{ ! loadFailure && (
				<EmbeddedAccountOnboarding
					onExit={ handleOnExit }
					onStepChange={ handleStepChange }
					onLoaderStart={ handleLoaderStart }
					onLoadError={ handleLoadError }
					onInitializationError={ handleInitializationError }
					onboardingData={ data }
					collectPayoutRequirements={ collectPayoutRequirements }
				/>
			) }
		</>
	);
};

export default EmbeddedKyc;
