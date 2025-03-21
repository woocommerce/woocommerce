/**
 * External dependencies
 */
import { useEffect } from 'react';

/**
 * Internal dependencies
 */

import { useStepperContext } from '../components/stepper';
import { useOnboardingContext } from '../data/onboarding-context';
import { OnboardingFields } from '../types';
import { recordEvent } from '@woocommerce/tracks';

const trackedSteps: Set< string > = new Set();
let startTime: number;
let stepStartTime: number;

const elapsed = ( time: number ) => Math.round( ( Date.now() - time ) / 1000 );
const stepElapsed = () => {
	const result = elapsed( stepStartTime );
	stepStartTime = Date.now();
	return result;
};

export const trackStarted = (): void => {
	// Initialize the elapsed time tracking
	startTime = stepStartTime = Date.now();

	const urlParams = new URLSearchParams( window.location.search );

	recordEvent( 'wcpay_onboarding_flow_started', {
		source:
			urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' ) || 'unknown',
	} );
};

export const trackStepCompleted = ( step: string ): void => {
	// We only track a completed step once.
	if ( trackedSteps.has( step ) ) return;

	recordEvent( 'wcpay_onboarding_flow_step_completed', {
		step,
		elapsed: stepElapsed(),
	} );
	trackedSteps.add( step );
};

export const trackRedirected = (
	isPoEligible: boolean,
	isEmbedded = false
): void => {
	const urlParams = new URLSearchParams( window.location.search );

	recordEvent( 'wcpay_onboarding_flow_redirected', {
		is_po_eligible: isPoEligible,
		is_embedded_onboarding: isEmbedded,
		elapsed: elapsed( startTime ),
		source:
			urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' ) || 'unknown',
	} );
};

/**
 * Track a change in the embedded onboarding step.
 *
 * @param step The current step in the embedded onboarding flow. See:
 * https://docs.stripe.com/connect/supported-embedded-components/account-onboarding#step-values
 */
export const trackEmbeddedStepChange = ( step: string ): void => {
	const urlParams = new URLSearchParams( window.location.search );

	recordEvent( 'wcpay_onboarding_flow_embedded_step_change', {
		step: step,
		elapsed: elapsed( startTime ),
		source:
			urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' ) || 'unknown',
	} );
};

export const trackKycExit = (): void => {
	const urlParams = new URLSearchParams( window.location.search );

	recordEvent( 'wcpay_onboarding_kyc_exit', {
		source:
			urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' ) || 'unknown',
	} );
};

export const trackAccountReset = (): void =>
	recordEvent( 'wcpay_onboarding_flow_reset' );

export const trackEligibilityModalClosed = (
	action: 'dismiss' | 'setup_deposits' | 'enable_payments_only',
	source: string
): void =>
	recordEvent( 'wcpay_onboarding_flow_eligibility_modal_closed', {
		action,
		source,
	} );

export const useTrackAbandoned = (): {
	trackAbandoned: ( method: 'hide' | 'exit' ) => void;
	removeTrackListener: () => void;
} => {
	const { errors, touched } = useOnboardingContext();
	const { currentStep: step } = useStepperContext();

	const trackEvent = ( method = 'hide' ) => {
		const event =
			method === 'hide'
				? 'wcpay_onboarding_flow_hidden'
				: 'wcpay_onboarding_flow_exited';
		const errored = Object.keys( errors ).filter(
			( field ) => touched[ field as keyof OnboardingFields ]
		);

		const urlParams = new URLSearchParams( window.location.search );

		recordEvent( event, {
			step,
			errored,
			elapsed: elapsed( startTime ),
			source:
				urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' ) ||
				'unknown',
		} );
	};

	const listener = () => {
		if ( document.visibilityState === 'hidden' ) {
			trackEvent( 'hide' );
		}
	};

	useEffect( () => {
		document.addEventListener( 'visibilitychange', listener );
		return () => {
			document.removeEventListener( 'visibilitychange', listener );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ step, errors, touched ] );

	return {
		trackAbandoned: ( method: string ) => {
			trackEvent( method );
			document.removeEventListener( 'visibilitychange', listener );
		},
		removeTrackListener: () =>
			document.removeEventListener( 'visibilitychange', listener ),
	};
};
