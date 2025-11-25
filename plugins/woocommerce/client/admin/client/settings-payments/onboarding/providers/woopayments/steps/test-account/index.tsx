/**
 * External dependencies
 */
import React, { useState, useRef, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import { WooPaymentsResetAccountModal } from '~/settings-payments/components/modals';
import './style.scss';

const TEST_ACCOUNT_ERROR_CODES = {
	ACCOUNT_ALREADY_EXISTS:
		'woocommerce_woopayments_test_account_already_exists',
};

interface StepCheckResponse {
	status: string;
	success: boolean;
}

const TestDriveLoader: React.FunctionComponent< {
	progress: number;
	title?: string;
	message?: string;
} > = ( { progress, title, message } ) => (
	<Loader className="woocommerce-payments-test-account-step__preloader">
		<Loader.Layout className="woocommerce-payments-test-account-step__preloader-layout">
			<Loader.Illustration>
				<img
					src={ `${ WC_ASSET_URL }images/onboarding/test-account-setup.svg` }
					alt={ __( 'Setup', 'woocommerce' ) }
					style={ { maxWidth: '223px' } }
				/>
			</Loader.Illustration>

			<Loader.Title>
				{ title || __( 'Finishing payments setup', 'woocommerce' ) }
			</Loader.Title>
			<Loader.ProgressBar progress={ progress ?? 0 } />
			<Loader.Sequence interval={ 0 }>
				{ message ||
					__(
						"In just a few moments, you'll be ready to test payments on your store.",
						'woocommerce'
					) }
			</Loader.Sequence>
		</Loader.Layout>
	</Loader>
);

// Constants for polling intervals and phase durations.
const POLLING_INTERVAL_INITIAL = 3000; // 3 seconds is the initial polling interval.
const POLLING_INTERVAL_EXTENDED_1 = 5000; // 5 seconds is the extended polling interval for phase 1.
const POLLING_INTERVAL_EXTENDED_2 = 7000; // 7 seconds is the extended polling interval for phase 2.
const EXTENDED_POLLING_PHASE_1_DURATION = 30000; // 30 seconds is the duration of phase 1.
const MAX_INITIAL_PROGRESS = 90; // Cap progress at 90% for the initial phase.
const MAX_EXTENDED_PHASE_1_PROGRESS = 96; // Cap progress at 96% for the extended phase 1.
const INITIAL_PHASE_INCREMENT = 5; // Increment progress by 20% for the initial phase.
const EXTENDED_PHASE_1_INCREMENT = 1; // Increment progress by 1% for the extended phase 1.
const INIT_PROGRESS_START = 10; // Start progress at 10% during init.
const INIT_PROGRESS_INCREMENT = 2; // Increment by 2% every second during init.
const INIT_PROGRESS_MAX = 30; // Cap progress at 30% during init.
const TITLE_CHANGE_INTERVAL = 5000; // The time interval for the title to change.

// Status types for the component.
type Status =
	| 'idle'
	| 'initializing'
	| 'polling'
	| 'success'
	| 'error'
	| 'blocked'
	| 'failed';

const PHASE_MESSAGES = [
	__( 'Setting up your test account', 'woocommerce' ),
	__( 'Finishing payments setup', 'woocommerce' ),
	__( 'Almost there!', 'woocommerce' ),
];

const TestAccountStep = () => {
	const {
		currentStep,
		closeModal,
		setJustCompletedStepId,
		sessionEntryPoint,
		setSnackbar,
	} = useOnboardingContext();

	// Component State.
	const [ status, setStatus ] = useState< Status >( 'idle' );
	const [ progress, setProgress ] = useState( 20 );
	const [ errorMessage, setErrorMessage ] = useState< string | undefined >();
	const [ pollingPhase, setPollingPhase ] = useState( 0 ); // 0: initial, 1: extended 1, 2: extended 2
	const [ retryCounter, setRetryCounter ] = useState( 0 );
	const [ loaderTitle, setLoaderTitle ] = useState< string | undefined >(
		PHASE_MESSAGES[ 0 ]
	);

	const [ isResetAccountModalOpen, setIsResetAccountModalOpen ] =
		useState( false );
	const [ errorCode, setErrorCode ] = useState< string | undefined >();

	// Refs for timers and phase tracking.
	const pollingTimeoutRef = useRef< number | null >( null );
	const phase1StartTimeRef = useRef< number | null >( null );
	const initializingTimeoutRef = useRef< number | null >( null );
	const titlePhaseRef = useRef< number >( 0 );
	// Update loader title based on time intervals
	useEffect( () => {
		if ( status === 'success' ) {
			// This is a pseudo-sub-step so we need to record the event manually.
			recordPaymentsOnboardingEvent(
				'woopayments_onboarding_modal_step_view',
				{
					step: currentStep?.id || 'unknown',
					sub_step_id: 'ready_to_test_payments',
					source: sessionEntryPoint,
				}
			);
		}

		if ( status !== 'polling' && status !== 'initializing' ) {
			titlePhaseRef.current = 0;
			return;
		}

		// Start with first title
		if ( titlePhaseRef.current === 0 ) {
			setLoaderTitle( PHASE_MESSAGES[ 0 ] );
		}

		// Increment title phase every TITLE_CHANGE_INTERVAL
		const timer = setTimeout( () => {
			titlePhaseRef.current += 1;
			if ( titlePhaseRef.current < PHASE_MESSAGES.length ) {
				setLoaderTitle( PHASE_MESSAGES[ titlePhaseRef.current ] );
			}
		}, TITLE_CHANGE_INTERVAL );

		return () => {
			clearTimeout( timer );
		};
	}, [ status ] );

	// Helper to clear timers.
	const clearTimers = () => {
		if ( pollingTimeoutRef.current !== null ) {
			clearTimeout( pollingTimeoutRef.current );
			pollingTimeoutRef.current = null;
		}
		if ( initializingTimeoutRef.current !== null ) {
			clearTimeout( initializingTimeoutRef.current );
			initializingTimeoutRef.current = null;
		}
	};

	const resetState = useCallback( () => {
		setStatus( 'idle' );
		setProgress( 0 );
		setErrorMessage( undefined );
		setPollingPhase( 0 );
		phase1StartTimeRef.current = null;
		clearTimers();
	}, [ setStatus, setProgress, setErrorMessage, setPollingPhase ] );

	// Main effect for handling initialization and polling loop.
	useEffect( () => {
		// -- Initialization Phase --
		if ( status === 'idle' ) {
			if ( currentStep?.status === 'completed' ) {
				setStatus( 'success' );
				setJustCompletedStepId( currentStep.id );

				setProgress( 100 ); // Show success state immediately.
				return;
			}

			if ( currentStep?.status === 'blocked' ) {
				setErrorMessage(
					currentStep?.errors?.[ 0 ]?.message ||
						__(
							'There are environment or store setup issues which are blocking progress. Please resolve them to proceed.',
							'woocommerce'
						)
				);
				setStatus( 'blocked' );
				return;
			}

			// If this step is not started or previously failed, try to initialize it.
			if (
				currentStep?.status === 'not_started' ||
				currentStep?.status === 'failed'
			) {
				setStatus( 'initializing' );
				setProgress( INIT_PROGRESS_START );

				const cleanStepIfNeeded = async () => {
					// We only need to clean the step if it has been retried or failed.
					if (
						currentStep?.actions?.clean?.href &&
						( retryCounter > 0 || currentStep?.status === 'failed' )
					) {
						await apiFetch< {
							success: boolean;
							message?: string;
						} >( {
							url: currentStep?.actions?.clean?.href,
							method: 'POST',
						} );
					}
				};

				// First clean the step if needed, then initialize.
				cleanStepIfNeeded()
					.then( () => {
						return apiFetch< {
							success: boolean;
							message?: string;
							code?: string;
						} >( {
							url: currentStep?.actions?.init?.href,
							method: 'POST',
							data: {
								source: sessionEntryPoint,
							},
						} );
					} )
					.then( ( response ) => {
						if ( response?.success ) {
							// Start polling immediately after successful init.
							setStatus( 'polling' );
						} else {
							setErrorCode( response?.code || '' );
							setErrorMessage(
								response?.message ||
									__(
										'Creating test account failed. Please try again.',
										'woocommerce'
									)
							);
							setStatus( 'error' );
						}
					} )
					.catch( ( error ) => {
						setErrorCode( error?.code || '' );
						setErrorMessage( error.message );
						setStatus( 'error' );
					} );
			} else {
				// If status is neither 'not_started' nor 'completed', assume we can start polling.
				setStatus( 'polling' );
			}
		}

		// -- Polling Phase --
		if ( status === 'polling' ) {
			const poll = () => {
				// Clear any existing timeout before starting a new one.
				clearTimers();

				apiFetch< StepCheckResponse >( {
					url: currentStep?.actions?.check?.href,
					method: 'POST',
				} )
					.then( ( response ) => {
						if ( response?.status === 'completed' ) {
							// Use timeout for smoother transition to success UI.
							pollingTimeoutRef.current = window.setTimeout(
								() => {
									setStatus( 'success' );
									setProgress( 100 ); // Visually complete.
									setJustCompletedStepId(
										currentStep?.id || ''
									);
								},
								1000
							);
							return; // Stop polling loop.
						}

						// Still pending, update progress and determine next poll.
						let nextPhase: number;
						let nextInterval: number;
						let newProgress = 0;

						// Use functional update to ensure we always increment from the latest progress.
						setProgress( ( currentProgress ) => {
							// Apply different increment logic based on phase.
							if ( pollingPhase === 0 ) {
								// Phase 0: increment by INITIAL_PHASE_INCREMENT until MAX_INITIAL_PROGRESS.
								newProgress = Math.min(
									currentProgress + INITIAL_PHASE_INCREMENT,
									MAX_INITIAL_PROGRESS
								);
							} else if ( pollingPhase === 1 ) {
								// Phase 1: increment by EXTENDED_PHASE_1_INCREMENT until 96%.
								newProgress = Math.min(
									currentProgress +
										EXTENDED_PHASE_1_INCREMENT,
									MAX_EXTENDED_PHASE_1_PROGRESS
								);
							} else {
								// Phase 2: Do not increment progress.
								newProgress = currentProgress;
							}
							return newProgress;
						} );

						// Update next phase and interval based on current phase and progress.
						if (
							pollingPhase === 0 &&
							newProgress >= MAX_INITIAL_PROGRESS
						) {
							// Transition to phase 1 when first reaching MAX_INITIAL_PROGRESS while in phase 0.
							nextPhase = 1;
							nextInterval = POLLING_INTERVAL_EXTENDED_1;
							phase1StartTimeRef.current = Date.now();
						} else if ( pollingPhase === 1 ) {
							// Already in phase 1, check if duration exceeded.
							if (
								phase1StartTimeRef.current &&
								Date.now() - phase1StartTimeRef.current >
									EXTENDED_POLLING_PHASE_1_DURATION
							) {
								// Transition to phase 2.
								nextPhase = 2;
								nextInterval = POLLING_INTERVAL_EXTENDED_2;
							} else {
								// Stay in phase 1.
								nextPhase = 1;
								nextInterval = POLLING_INTERVAL_EXTENDED_1;
							}
						} else if ( pollingPhase === 2 ) {
							// Stay in phase 2.
							nextPhase = 2;
							nextInterval = POLLING_INTERVAL_EXTENDED_2;
						} else {
							// Stay in phase 0.
							nextPhase = 0;
							nextInterval = POLLING_INTERVAL_INITIAL;
						}

						setPollingPhase( nextPhase ); // Update phase state.

						// Schedule the next poll.
						pollingTimeoutRef.current = window.setTimeout(
							poll,
							nextInterval
						);
					} )
					.catch( ( error ) => {
						setErrorMessage( error.message );
						setStatus( 'error' );
						clearTimers();
					} );
			};

			// Start the first poll.
			poll();
		}

		// -- Progress animation during Initializing Phase --
		if ( status === 'initializing' ) {
			// Start progress animation from 10% to 30%, increment by 2% every second.
			if ( initializingTimeoutRef.current === null ) {
				initializingTimeoutRef.current = window.setInterval( () => {
					setProgress( ( current ) => {
						if ( current < INIT_PROGRESS_MAX ) {
							return Math.min(
								current + INIT_PROGRESS_INCREMENT,
								INIT_PROGRESS_MAX
							);
						}
						return current;
					} );
				}, 1000 );
			}
		}
		// Clear the initializing timer if not in initializing phase.
		if (
			status !== 'initializing' &&
			initializingTimeoutRef.current !== null
		) {
			clearTimeout( initializingTimeoutRef.current );
			initializingTimeoutRef.current = null;
		}

		// Cleanup function for the effect.
		return () => {
			clearTimers(); // Clear any pending timeouts.
		};
	}, [
		status,
		currentStep,
		retryCounter,
		pollingPhase,
		setJustCompletedStepId,
	] );

	const getPhaseMessage = ( phase: number ) => {
		if ( phase === 1 ) {
			return __(
				"The test account creation is taking a bit longer than expected, but don't worry — we're on it! Please bear with us for a few seconds more as we set everything up for your store.",
				'woocommerce'
			);
		}
		if ( phase === 2 ) {
			return __(
				"Thank you for your patience! Unfortunately, the test account creation is taking a bit longer than we anticipated. But don't worry — we won't give up! Feel free to close this modal and check back later. We appreciate your understanding!",
				'woocommerce'
			);
		}
		return undefined;
	};

	useEffect( () => {
		if ( status === 'success' ) {
			navigateTo( {
				url: getNewPath( { nox: 'test_account_created' }, '', {
					page: 'wc-admin',
				} ),
			} );
		}
	}, [ status ] );

	const isAccountAlreadyExistsError =
		errorCode === TEST_ACCOUNT_ERROR_CODES.ACCOUNT_ALREADY_EXISTS;

	const actions = isAccountAlreadyExistsError
		? [
				{
					label: __( 'Reset Account', 'woocommerce' ),
					variant: 'secondary' as const,
					onClick: () => {
						setIsResetAccountModalOpen( true );
					},
				},
		  ]
		: [
				{
					label: __( 'Try Again', 'woocommerce' ),
					variant: 'primary' as const,
					onClick: () => {
						recordPaymentsOnboardingEvent(
							'woopayments_onboarding_modal_click',
							{
								step: currentStep?.id || 'unknown',
								action: 'try_again_on_error',
								retries: retryCounter + 1,
								source: sessionEntryPoint,
							}
						);

						resetState();
						setRetryCounter( ( c ) => c + 1 );
					},
				},
				{
					label: __( 'Cancel', 'woocommerce' ),
					variant: 'secondary' as const,
					className:
						'woocommerce-payments-test-account-step__error-cancel-button',
					onClick: () => {
						recordPaymentsOnboardingEvent(
							'woopayments_onboarding_modal_click',
							{
								step: currentStep?.id || 'unknown',
								action: 'cancel_on_error',
								retries: retryCounter,
								source: sessionEntryPoint,
							}
						);

						closeModal();
					},
				},
		  ];

	// Render loading/error state.
	return (
		<div className="woocommerce-payments-test-account-step">
			<WooPaymentsStepHeader onClose={ closeModal } />

			{ /* Error Notice */ }
			{ ( status === 'error' || status === 'blocked' ) && (
				<Notice
					status={ status === 'blocked' ? 'error' : 'warning' }
					isDismissible={ false }
					actions={
						// Only show actions if the step is not blocked.
						status !== 'blocked' ? actions : []
					}
					className="woocommerce-payments-test-account-step__error"
				>
					<p className="woocommerce-payments-test-account-step__error-message">
						{ errorMessage ||
							__(
								'An error occurred while creating your test account. Please try again.',
								'woocommerce'
							) }
					</p>
				</Notice>
			) }

			{ /* Loader - shown during initializing and polling */ }
			{ /* The success state is added just to keep the current loader state while we redirect to the admin page */ }
			{ ( status === 'initializing' ||
				status === 'polling' ||
				status === 'success' ) && (
				<TestDriveLoader
					progress={ progress }
					title={ loaderTitle }
					message={ getPhaseMessage( pollingPhase ) }
				/>
			) }

			<WooPaymentsResetAccountModal
				isOpen={ isResetAccountModalOpen }
				onClose={ () => {
					setIsResetAccountModalOpen( false );
					setSnackbar( {
						show: true,
						message: __(
							'Your test account was successfully reset.',
							'woocommerce'
						),
					} );
				} }
				isEmbeddedResetFlow
				resetUrl={ currentStep?.actions?.reset?.href }
			/>
		</div>
	);
};

export default TestAccountStep;
