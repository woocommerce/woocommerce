/**
 * External dependencies
 */
import React, { useState, useRef, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Notice, Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './style.scss';

interface StepCheckResponse {
	status: string;
	success: boolean;
}

const TestDriveLoader: React.FunctionComponent< {
	progress: number;
	message?: string;
} > = ( { progress, message } ) => (
	<Loader className="woocommerce-payments-test-account-step__preloader">
		<Loader.Layout className="woocommerce-payments-test-account-step__preloader-layout">
			<Loader.Illustration>
				<img
					src={ `${ WC_ASSET_URL }images/onboarding/test-account-setup.svg` }
					alt="setup"
					style={ { maxWidth: '223px' } }
				/>
			</Loader.Illustration>

			<Loader.Title>
				{ __( 'Finishing payments setup', 'woocommerce' ) }
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

// Constants for polling intervals and phase durations
const POLLING_INTERVAL_INITIAL = 3000; // 3 seconds is the initial polling interval
const POLLING_INTERVAL_EXTENDED_1 = 5000; // 5 seconds is the extended polling interval for phase 1
const POLLING_INTERVAL_EXTENDED_2 = 7000; // 7 seconds is the extended polling interval for phase 2
const EXTENDED_POLLING_PHASE_1_DURATION = 30000; // 30 seconds is the duration of phase 1
const MAX_INITIAL_PROGRESS = 90; // Cap progress at 90% for the initial phase
const MAX_EXTENDED_PHASE_1_PROGRESS = 96; // Cap progress at 96% for the extended phase 1
const INITIAL_PHASE_INCREMENT = 5; // Increment progress by 20% for the initial phase
const EXTENDED_PHASE_1_INCREMENT = 1; // Increment progress by 1% for the extended phase 1

// Status types for the component
type Status = 'idle' | 'initializing' | 'polling' | 'success' | 'error';

const TestAccountStep = () => {
	const { currentStep, navigateToNextStep, closeModal, refreshStoreData } =
		useOnboardingContext();

	// Component State
	const [ status, setStatus ] = useState< Status >( 'idle' );
	const [ progress, setProgress ] = useState( 20 );
	const [ errorMessage, setErrorMessage ] = useState< string | undefined >();
	const [ pollingPhase, setPollingPhase ] = useState( 0 ); // 0: initial, 1: extended 1, 2: extended 2
	const [ retryCounter, setRetryCounter ] = useState( 0 );

	// Refs for timers and phase tracking
	const pollingTimeoutRef = useRef< number | null >( null );
	const phase1StartTimeRef = useRef< number | null >( null );

	// Helper to clear timers
	const clearTimers = () => {
		if ( pollingTimeoutRef.current !== null ) {
			clearTimeout( pollingTimeoutRef.current );
			pollingTimeoutRef.current = null;
		}
	};

	// Reset state function
	const resetState = useCallback( () => {
		setStatus( 'idle' );
		setProgress( 0 );
		setErrorMessage( undefined );
		setPollingPhase( 0 );
		phase1StartTimeRef.current = null;
		clearTimers();
	}, [ setStatus, setProgress, setErrorMessage, setPollingPhase ] );

	// Main effect for handling initialization and polling loop
	useEffect( () => {
		// -- Initialization Phase --
		if ( status === 'idle' ) {
			const stepHasError = currentStep?.errors?.[ 0 ];
			if ( stepHasError ) {
				setErrorMessage( stepHasError );
				setStatus( 'error' );
				return;
			}

			if ( currentStep?.status === 'completed' ) {
				setStatus( 'success' );
				setProgress( 100 ); // Show success state immediately
				return;
			}

			if ( currentStep?.status === 'not_started' ) {
				setStatus( 'initializing' );
				apiFetch< { success: boolean; message?: string } >( {
					url: currentStep?.actions?.init?.href,
					method: 'POST',
				} )
					.then( ( response ) => {
						if ( response?.success ) {
							// Start polling immediately after successful init
							setStatus( 'polling' );
						} else {
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
						setErrorMessage( error.message );
						setStatus( 'error' );
					} );
			} else {
				// If status is neither 'not_started' nor 'completed', assume we can start polling
				setStatus( 'polling' );
			}
		}

		// -- Polling Phase --
		if ( status === 'polling' ) {
			const poll = () => {
				// Clear any existing timeout before starting a new one
				clearTimers();

				apiFetch< StepCheckResponse >( {
					url: currentStep?.actions?.check?.href,
					method: 'POST',
				} )
					.then( ( response ) => {
						if ( response?.status === 'completed' ) {
							// Use timeout for smoother transition to success UI
							pollingTimeoutRef.current = window.setTimeout(
								() => {
									setStatus( 'success' );
									setProgress( 100 ); // Visually complete
								},
								1000
							);
							return; // Stop polling loop
						}

						// Still pending, update progress and determine next poll
						let nextPhase = pollingPhase;
						let nextInterval = POLLING_INTERVAL_INITIAL;
						let newProgress = 0;

						// Use functional update to ensure we always increment from the latest progress
						setProgress( ( currentProgress ) => {
							// Apply different increment logic based on phase
							if ( pollingPhase === 0 ) {
								// Phase 0: increment by INITIAL_PHASE_INCREMENT until MAX_INITIAL_PROGRESS
								newProgress = Math.min(
									currentProgress + INITIAL_PHASE_INCREMENT,
									MAX_INITIAL_PROGRESS
								);
							} else if ( pollingPhase === 1 ) {
								// Phase 1: increment by EXTENDED_PHASE_1_INCREMENT until 96%
								newProgress = Math.min(
									currentProgress +
										EXTENDED_PHASE_1_INCREMENT,
									MAX_EXTENDED_PHASE_1_PROGRESS
								);
							} else {
								// Phase 2: Do not increment progress
								newProgress = currentProgress;
							}
							return newProgress;
						} );

						// Update next phase and interval based on current phase and progress
						if (
							pollingPhase === 0 &&
							newProgress >= MAX_INITIAL_PROGRESS
						) {
							// Transition to phase 1 when first reaching MAX_INITIAL_PROGRESS while in phase 0
							nextPhase = 1;
							nextInterval = POLLING_INTERVAL_EXTENDED_1;
							phase1StartTimeRef.current = Date.now();
						} else if ( pollingPhase === 1 ) {
							// Already in phase 1, check if duration exceeded
							if (
								phase1StartTimeRef.current &&
								Date.now() - phase1StartTimeRef.current >
									EXTENDED_POLLING_PHASE_1_DURATION
							) {
								// Transition to phase 2
								nextPhase = 2;
								nextInterval = POLLING_INTERVAL_EXTENDED_2;
							} else {
								// Stay in phase 1
								nextPhase = 1;
								nextInterval = POLLING_INTERVAL_EXTENDED_1;
							}
						} else if ( pollingPhase === 2 ) {
							// Stay in phase 2
							nextPhase = 2;
							nextInterval = POLLING_INTERVAL_EXTENDED_2;
						} else {
							// Stay in phase 0
							nextPhase = 0;
							nextInterval = POLLING_INTERVAL_INITIAL;
						}

						setPollingPhase( nextPhase ); // Update phase state

						// Schedule the next poll
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

			// Start the first poll
			poll();
		}

		// Cleanup function for the effect
		return () => {
			clearTimers(); // Clear any pending timeouts
		};
	}, [ status, currentStep, retryCounter, pollingPhase ] );

	// Effect to reset state on retry
	useEffect( () => {
		if ( retryCounter > 0 ) {
			resetState();
		}
	}, [ retryCounter, resetState ] );

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

	if ( status === 'success' ) {
		// Render success state
		return (
			<>
				<WooPaymentsStepHeader onClose={ closeModal } />
				<div className="settings-payments-onboarding-modal__step--content">
					<div className="woocommerce-payments-test-account-step__success_content_container">
						<div className="woocommerce-woopayments-modal__content woocommerce-payments-test-account-step__success_content">
							<h1 className="woocommerce-payments-test-account-step__success_content_title">
								{ __(
									"You're ready to test payments!",
									'woocommerce'
								) }
							</h1>
							<div className="woocommerce-woopayments-modal__content__item">
								<div className="woocommerce-woopayments-modal__content__item__description">
									<p>
										{ interpolateComponents( {
											mixedString: __(
												"We've created a test account for you so that you can begin {{link}}testing payments on your store{{/link}}.",
												'woocommerce'
											),
											components: {
												link: (
													<Link
														href="https://woocommerce.com/document/woopayments/testing-and-troubleshooting/sandbox-mode/"
														target="_blank"
														rel="noreferrer"
														type="external"
													/>
												),
												break: <br />,
											},
										} ) }
									</p>
								</div>
							</div>
							<div className="woocommerce-payments-test-account-step__success-whats-next">
								<div className="woocommerce-woopayments-modal__content__item">
									<h2>
										{ __( "What's next:", 'woocommerce' ) }
									</h2>
								</div>
								<div className="woocommerce-woopayments-modal__content__item-flex">
									<img
										src={
											WC_ASSET_URL +
											'images/icons/store.svg'
										}
										alt="store icon"
									/>
									<div className="woocommerce-woopayments-modal__content__item-flex__description">
										<h3>
											{ __(
												'Continue your store setup',
												'woocommerce'
											) }
										</h3>
										<div>
											{ __(
												'Finish completing the tasks required to launch your store.',
												'woocommerce'
											) }
										</div>
									</div>
								</div>
								<div className="woocommerce-woopayments-modal__content__item-flex">
									<img
										src={
											WC_ASSET_URL +
											'images/icons/dollar.svg'
										}
										alt="dollar icon"
									/>
									<div className="woocommerce-woopayments-modal__content__item-flex__description">
										<h3>
											{ __(
												'Activate payments',
												'woocommerce'
											) }
										</h3>
										<div>
											<p>
												{ interpolateComponents( {
													mixedString: __(
														'Provide some additional details about your business so you can being accepting real payments. {{link}}Learn more{{/link}}',
														'woocommerce'
													),
													components: {
														link: (
															<Link
																href="https://woocommerce.com/document/woopayments/startup-guide/#sign-up-process"
																target="_blank"
																rel="noreferrer"
																type="external"
															/>
														),
													},
												} ) }
											</p>
										</div>
									</div>
								</div>
							</div>
							<Button
								variant="primary"
								onClick={ () => {
									// Navigate to wc-admin page
									navigateTo( {
										url: getNewPath( {}, '', {
											page: 'wc-admin',
										} ),
									} );
								} }
							>
								{ __( 'Continue store setup', 'woocommerce' ) }
							</Button>
							<div className="woocommerce-payments-test-account-step__success_content_or-divider">
								<hr />
								{ __( 'OR', 'woocommerce' ) }
								<hr />
							</div>

							<Button
								variant="secondary"
								onClick={ () => {
									// This will refresh the steps and move the modal to the next step
									navigateToNextStep();

									// Refresh the store data
									refreshStoreData();
								} }
							>
								{ __( 'Activate payments', 'woocommerce' ) }
							</Button>
						</div>
					</div>
				</div>
			</>
		);
	}

	// Render loading/error state
	return (
		<div className="woocommerce-payments-test-account-step">
			<WooPaymentsStepHeader onClose={ closeModal } />

			{ /* Error Notice */ }
			{ status === 'error' && errorMessage && (
				<Notice
					status="warning"
					isDismissible={ false }
					actions={ [
						{
							label: __( 'Try Again', 'woocommerce' ),
							variant: 'primary',
							onClick: () => {
								setRetryCounter( ( c ) => c + 1 );
							},
						},
						{
							label: __( 'Cancel', 'woocommerce' ),
							variant: 'secondary',
							className:
								'woocommerce-payments-test-account-step__error-cancel-button',
							onClick: closeModal,
						},
					] }
					className="woocommerce-payments-test-account-step__error"
				>
					<p className="woocommerce-payments-test-account-step__error-message">
						{ errorMessage }
					</p>
				</Notice>
			) }

			{ /* Loader - shown during initializing and polling */ }
			{ ( status === 'initializing' || status === 'polling' ) && (
				<TestDriveLoader
					progress={ progress }
					message={ getPhaseMessage( pollingPhase ) }
				/>
			) }
		</div>
	);
};

export default TestAccountStep;
