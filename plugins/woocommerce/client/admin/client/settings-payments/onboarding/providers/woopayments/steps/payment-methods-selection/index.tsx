/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Icon, Notice } from '@wordpress/components';
import { RecommendedPaymentMethod } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useState, useEffect, useMemo, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { close } from '@wordpress/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../data/onboarding-context';
import { PaymentMethodListItem } from './list-item';
import {
	combinePaymentMethodsState,
	combineRequestMethods,
	recordPaymentsOnboardingEvent,
	shouldRenderPaymentMethodInMainList,
} from '~/settings-payments/utils';
import './style.scss';

export default function PaymentMethodsSelection() {
	const { currentStep, navigateToNextStep, closeModal, sessionEntryPoint } =
		useOnboardingContext();
	const [ isExpanded, setIsExpanded ] = useState( false );
	const [ paymentMethodsState, setPaymentMethodsState ] = useState< {
		[ key: string ]: boolean;
	} >( {} );
	// Store the calculated initial visibility status in state to trigger re-render
	const [ initialVisibilityMap, setInitialVisibilityMap ] = useState< Record<
		string,
		boolean
	> | null >( null );
	const [ isContinueButtonLoading, setIsContinueButtonLoading ] =
		useState( false );
	const [ loadingPaymentMethods, setLoadingPaymentMethods ] = useState< {
		[ key: string ]: boolean;
	} >( {} );

	const contextPaymentMethodsState = currentStep?.context?.pms_state;
	const contextPaymentMethods = currentStep?.context?.recommended_pms;

	// Memoize the combined recommended payment methods
	const recommendedPaymentMethods = useMemo( () => {
		return contextPaymentMethods
			? combineRequestMethods( contextPaymentMethods )
			: [];
	}, [ contextPaymentMethods ] );

	const scrollRef = useRef< HTMLDivElement | null >( null );
	const [ hasOverflow, setHasOverflow ] = useState( false );

	// Update the local payment methods state when the context changes
	useEffect( () => {
		if ( contextPaymentMethodsState ) {
			setPaymentMethodsState( contextPaymentMethodsState );
		}
	}, [ contextPaymentMethodsState ] );

	// Combine state to match combined methods list
	const combinedState = useMemo(
		() => combinePaymentMethodsState( paymentMethodsState ),
		[ paymentMethodsState ]
	);

	// Calculate and store initial visibility *once* when data is ready
	useEffect( () => {
		// Only proceed if the map has been populated.
		if ( initialVisibilityMap !== null ) {
			return;
		}

		// Ensure both methods and state are sufficiently loaded
		if (
			recommendedPaymentMethods.length > 0 &&
			Object.keys( combinedState ).length > 0 // Use combinedState length
		) {
			// Check if all necessary state keys are present for the current methods in the *combined* state
			const allKeysPresent = recommendedPaymentMethods.every( ( m ) => {
				// Check in combinedState
				return combinedState[ m.id ] !== undefined;
			} );

			if ( allKeysPresent ) {
				const calculatedMap: Record< string, boolean > = {};
				recommendedPaymentMethods.forEach( ( method ) => {
					calculatedMap[ method.id ] =
						shouldRenderPaymentMethodInMainList(
							method,
							combinedState[ method.id ] // Use combinedState value
						);
				} );
				// Set the state with the calculated initial visibility map
				setInitialVisibilityMap( calculatedMap );
			}
		}
		// Depend on methods and the *combined* state
	}, [ recommendedPaymentMethods, combinedState, initialVisibilityMap ] );

	// Calculate hidden count based on the stored initial visibility (Memoized)
	const hiddenCount = useMemo( () => {
		// Use the state map now
		if ( ! initialVisibilityMap || isExpanded ) {
			return 0;
		}

		// Filter based on the stored initial visibility status from state
		return recommendedPaymentMethods.filter(
			// Count if initial visibility was false
			( method ) => ! ( initialVisibilityMap[ method.id ] ?? false )
		).length;
		// Depend on the state map now
	}, [ recommendedPaymentMethods, isExpanded, initialVisibilityMap ] );

	const savePaymentMethodsState = (
		state: Record< string, boolean >,
		changedMethodId?: string
	): Promise< void > => {
		const saveUrl = currentStep?.actions?.save?.href;

		// Store the previous state for potential rollback
		const previousState = { ...paymentMethodsState };

		// Set loading state for the specific method if provided
		if ( changedMethodId ) {
			setLoadingPaymentMethods( ( prev ) => ( {
				...prev,
				[ changedMethodId ]: true,
			} ) );
		}

		// Optimistically update the local state first
		setPaymentMethodsState( state );

		if ( saveUrl ) {
			// Send the updated state to the backend.
			return apiFetch( {
				url: saveUrl,
				method: 'POST',
				data: {
					payment_methods: state,
					source: sessionEntryPoint,
				},
			} )
				.then( () => {} )
				.catch( () => {
					// If the request fails, revert to the previous state
					setPaymentMethodsState( previousState );
				} )
				.finally( () => {
					// Clear loading state immediately if no API call is made
					if ( changedMethodId ) {
						setLoadingPaymentMethods( ( prev ) => ( {
							...prev,
							[ changedMethodId ]: false,
						} ) );
					}
				} );
		}

		// Clear loading state immediately if no API call is made
		if ( changedMethodId ) {
			setLoadingPaymentMethods( ( prev ) => ( {
				...prev,
				[ changedMethodId ]: false,
			} ) );
		}

		// Return a resolved promise since no API call was made.
		return Promise.resolve();
	};

	// Check if overflow exists for Payment Methods list container.
	const checkHasOverflow = () => {
		// Delay the check slightly to ensure DOM is ready.
		return setTimeout( () => {
			const pmsContainer = scrollRef.current;
			if ( pmsContainer ) {
				// Compare scrollHeight and clientHeight to determine overflow
				const hasScrollOverflow =
					pmsContainer.scrollHeight > pmsContainer.clientHeight;
				setHasOverflow( hasScrollOverflow );
			}
		}, 10 );
	};

	// Check for overflow on initial render and on window resize.
	useEffect( () => {
		let timeoutId = checkHasOverflow();

		// Check for overflow on window resize.
		const handleResize = () => {
			// Clear any existing timeout before creating a new one.
			clearTimeout( timeoutId );
			timeoutId = checkHasOverflow();
		};
		window.addEventListener( 'resize', handleResize );

		return () => {
			// Cleanup the timeout and event listener on unmount.
			clearTimeout( timeoutId );
			window.removeEventListener( 'resize', handleResize );
		};
	}, [ isExpanded, initialVisibilityMap ] );

	return (
		<div className="settings-payments-onboarding-modal__step--content">
			<div className="woocommerce-recommended-payment-methods">
				<div className="woocommerce-recommended-payment-methods__header">
					<div className="woocommerce-recommended-payment-methods__header--title">
						<h1 className="components-truncate components-text">
							{ __(
								'Choose your payment methods',
								'woocommerce'
							) }
						</h1>
						<Button
							className="settings-payments-onboarding-modal__header--close"
							onClick={ closeModal }
						>
							<Icon icon={ close } />
						</Button>
					</div>
					<div className="woocommerce-recommended-payment-methods__header--description">
						{ __(
							"Select which payment methods you'd like to offer to your shoppers. You can update these at any time.",
							'woocommerce'
						) }
					</div>
				</div>
				{ currentStep?.errors && currentStep.errors.length > 0 && (
					<Notice
						status="error"
						isDismissible={ false }
						className="woocommerce-recommended-payment-methods__error"
					>
						<p>{ currentStep.errors[ 0 ].message }</p>
					</Notice>
				) }
				<div className="woocommerce-recommended-payment-methods__list">
					<div
						className="settings-payments-methods__container"
						ref={ scrollRef }
					>
						<div className="woocommerce-list">
							{ recommendedPaymentMethods?.map(
								( method: RecommendedPaymentMethod ) => (
									<PaymentMethodListItem
										method={ method }
										paymentMethodsState={ combinePaymentMethodsState(
											paymentMethodsState
										) }
										setPaymentMethodsState={ ( state ) => {
											savePaymentMethodsState(
												state,
												method.id
											);
										} }
										// Pass down the calculated initial visibility for this specific method from state
										initialVisibilityStatus={
											initialVisibilityMap
												? initialVisibilityMap[
														method.id
												  ] ?? null
												: null
										}
										isExpanded={ isExpanded }
										isLoading={
											loadingPaymentMethods[
												method.id
											] ?? false
										}
										key={ method.id }
									/>
								)
							) }
						</div>
						{ /* Show button only if not expanded and there are initially hidden items */ }
						{ ! isExpanded && hiddenCount > 0 && (
							<div className="settings-payments-methods__show-more--wrapper">
								<Button
									className="settings-payments-methods__show-more"
									onClick={ () => {
										recordPaymentsOnboardingEvent(
											'woopayments_onboarding_modal_click',
											{
												step:
													currentStep?.id ||
													'unknown',
												action: 'show_more',
												hidden_count: hiddenCount,
												source: sessionEntryPoint,
											}
										);

										setIsExpanded( ! isExpanded );
									} }
									tabIndex={ 0 }
									aria-expanded={ isExpanded }
								>
									{ sprintf(
										/* translators: %s: number of hidden payment methods */
										__( 'Show more (%s)', 'woocommerce' ),
										hiddenCount
									) }
								</Button>
							</div>
						) }
					</div>
				</div>
				<div
					className={ clsx(
						'woocommerce-recommended-payment-methods__list_footer',
						{
							'has-border': hasOverflow,
						}
					) }
				>
					<Button
						className="components-button is-primary"
						onClick={ () => {
							const finishUrl =
								currentStep?.actions?.finish?.href;
							if ( ! finishUrl ) {
								return;
							}

							// Persist the final state on the backend, just in case the user didn't change anything.
							setIsContinueButtonLoading( true );
							// First save the payment methods state.
							savePaymentMethodsState( paymentMethodsState )
								.then( () => {
									// Then mark the step as completed.
									return apiFetch( {
										url: finishUrl,
										method: 'POST',
										data: {
											source: sessionEntryPoint,
										},
									} );
								} )
								.then( () => {
									const displayedPaymentMethodsIds =
										Object.keys(
											initialVisibilityMap || {}
										);
									const paymentMethodsIds =
										Object.keys( paymentMethodsState );

									const eventProps = {
										// This is the entire list of payment methods that are available to the user,
										// regardless of whether they are enabled or not, shown by default or hidden behind a Show more section.
										displayed_payment_methods:
											displayedPaymentMethodsIds.join(
												', '
											),
										// This is the list of payment methods that were initially displayed to the user
										// when the step became visible, regardless of whether they were enabled or not.
										default_displayed_pms:
											displayedPaymentMethodsIds
												.filter(
													( paymentMethod ) =>
														initialVisibilityMap?.[
															paymentMethod
														] !== false
												)
												.join( ', ' ),
										// This is the list of payment methods that were enabled by default
										// when the step became visible, regardless of whether they ended up selected or not.
										default_selected_pms:
											recommendedPaymentMethods
												.filter(
													( paymentMethod ) =>
														paymentMethod.enabled
												)
												.map( ( method ) => method.id )
												.join( ', ' ),
										// This is the list of payment methods that ended up enabled (either by user selection or default).
										selected_payment_methods:
											paymentMethodsIds
												.filter(
													( paymentMethod ) =>
														paymentMethodsState[
															paymentMethod
														]
												)
												.join( ', ' ),
										// This is the list of payment methods that ended up disabled (either by user selection or default).
										deselected_payment_methods:
											paymentMethodsIds
												.filter(
													( paymentMethod ) =>
														! paymentMethodsState[
															paymentMethod
														]
												)
												.join( ', ' ),
										business_country:
											window.wcSettings?.admin
												?.woocommerce_payments_nox_profile
												?.business_country_code ??
											'unknown',
										source: sessionEntryPoint,
									};

									recordPaymentsOnboardingEvent(
										'woopayments_onboarding_modal_click',
										{
											step: currentStep?.id || 'unknown',
											action: 'continue',
											...eventProps,
										}
									);

									// Legacy event
									recordEvent(
										'wcpay_settings_payment_methods_continue',
										eventProps
									);

									setIsContinueButtonLoading( false );
									navigateToNextStep();
								} )
								.catch( () => {
									setIsContinueButtonLoading( false );
								} );
						} }
						isBusy={ isContinueButtonLoading }
						disabled={ isContinueButtonLoading }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
