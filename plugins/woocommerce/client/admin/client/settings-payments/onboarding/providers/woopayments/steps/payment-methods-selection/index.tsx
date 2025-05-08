/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { RecommendedPaymentMethod } from '@woocommerce/data';
import { useState, useEffect, useMemo, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { close } from '@wordpress/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../data/onboarding-context';
import { PaymentMethodListItem } from '~/settings-payments/components/payment-method-list-item';
import {
	combineRequestMethods,
	combinePaymentMethodsState,
	decouplePaymentMethodsState,
	shouldRenderPaymentMethodInMainList,
} from '~/settings-payments/utils';
import './style.scss';

export default function PaymentMethodsSelection() {
	const { currentStep, navigateToNextStep, closeModal } =
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
		// Only proceed if the map hasn't been populated yet
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
				const isPresent = combinedState[ m.id ] !== undefined; // Check in combinedState
				return isPresent;
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
	}, [ recommendedPaymentMethods, combinedState ] );

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

	const savePaymentMethodsState = ( state: Record< string, boolean > ) => {
		// Update the local state
		setPaymentMethodsState( state );

		// Send the updated state to the server
		const href = currentStep?.actions?.save?.href;
		if ( href ) {
			apiFetch( {
				url: href,
				method: 'POST',
				data: {
					payment_methods: decouplePaymentMethodsState( state ),
				},
			} );
		}
	};

	// Check if overflow exists for Payment Methods list container.
	const checkHasOverflow = () => {
		const pmsContainer = scrollRef.current;

		if ( pmsContainer ) {
			// Compare scrollHeight and clientHeight to determine overflow.
			setHasOverflow(
				pmsContainer.scrollHeight > pmsContainer.clientHeight
			);
		}
	};

	// Check for overflow on initial render and on window resize.
	useEffect( () => {
		// Use setTimeout to ensure the DOM is updated before checking for overflow.
		const timeout = setTimeout( () => {
			checkHasOverflow();
		}, 0 ); // Runs after paint

		// Check for overflow on window resize.
		window.addEventListener( 'resize', checkHasOverflow );

		return () => {
			// Cleanup the timeout and event listener on unmount.
			clearTimeout( timeout );
			window.removeEventListener( 'resize', checkHasOverflow );
		};
	}, [] );

	return (
		<div className="settings-payments-onboarding-modal__step--content">
			<div className="woocommerce-layout__header woocommerce-recommended-payment-methods">
				<div className="woocommerce-layout__header-wrapper">
					<div className="woocommerce-layout__header-title-and-close">
						<h1 className="components-truncate components-text woocommerce-layout__header-heading woocommerce-layout__header-left-align woocommerce-settings-payments-header__title">
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

					<div className="woocommerce-settings-payments-header__description">
						{ __(
							"Select which payment methods you'd like to offer to your shoppers. You can update these at any time.",
							'woocommerce'
						) }
					</div>
				</div>
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
											// Update the local state
											setPaymentMethodsState( state );

											// Persist the state on the backend.
											savePaymentMethodsState( state );
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
										setIsExpanded( ! isExpanded );

										// Check for overflow after expanding hidden payment methods.
										// Use setTimeout to ensure the DOM is updated before checking for overflow.
										setTimeout( () => {
											checkHasOverflow();
										}, 0 );
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
							const href = currentStep?.actions?.finish?.href;
							if ( ! href ) {
								return;
							}

							// Persist the final state on the backend, just in case the user didn't change anything.
							savePaymentMethodsState( paymentMethodsState );
							setIsContinueButtonLoading( true );

							// Mark the step as completed.
							apiFetch( {
								url: href,
								method: 'POST',
							} ).then( () => {
								setIsContinueButtonLoading( false );
								navigateToNextStep();
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
