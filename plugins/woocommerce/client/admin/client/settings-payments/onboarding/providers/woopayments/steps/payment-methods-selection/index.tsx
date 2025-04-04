/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { RecommendedPaymentMethod } from '@woocommerce/data';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { close } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../data/onboarding-context';
import { PaymentMethodListItem } from '~/settings-payments/components/payment-method-list-item';
import {
	combineRequestMethods,
	combinePaymentMethodsState,
	decouplePaymentMethodsState,
	shouldRenderPaymentMethod,
} from '~/settings-payments/utils';
import './style.scss';

export default function PaymentMethodsSelection() {
	const { currentStep, navigateToNextStep, closeModal } =
		useOnboardingContext();
	const [ isExpanded, setIsExpanded ] = useState( false );
	const [ paymentMethodsState, setPaymentMethodsState ] = useState< {
		[ key: string ]: boolean;
	} >( {} );

	const contextPaymentMethodsState = currentStep?.context?.pms_state;
	const contextPaymentMethods = currentStep?.context?.recommended_pms;

	useEffect( () => {
		if ( contextPaymentMethodsState ) {
			setPaymentMethodsState( contextPaymentMethodsState );
		}
	}, [ contextPaymentMethodsState ] );

	const recommendedPaymentMethods = contextPaymentMethods
		? combineRequestMethods( contextPaymentMethods )
		: [];

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
					<div className="settings-payments-methods__container">
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

											// Send the updated state to the server
											const href =
												currentStep?.actions?.save
													?.href;
											// Send POST request to the href with the payment methods state
											if ( href ) {
												apiFetch( {
													url: href,
													method: 'POST',
													data: {
														payment_methods:
															decouplePaymentMethodsState(
																state
															),
													},
												} );
											}
										} }
										isExpanded={ isExpanded }
										key={ method.id }
									/>
								)
							) }
						</div>
						{ ! isExpanded && (
							<Button
								className="settings-payments-methods__show-more"
								onClick={ () => {
									setIsExpanded( ! isExpanded );
								} }
								tabIndex={ 0 }
								aria-expanded={ isExpanded }
							>
								{ sprintf(
									/* translators: %s: number of disabled payment methods */
									__( 'Show more (%s)', 'woocommerce' ),
									recommendedPaymentMethods?.filter(
										( method ) =>
											! shouldRenderPaymentMethod(
												method,
												paymentMethodsState[ method.id ]
											)
									).length ?? 0
								) }
							</Button>
						) }
					</div>
				</div>
				<div className="woocommerce-recommended-payment-methods__list_footer">
					<Button
						className="components-button is-primary"
						onClick={ () => {
							const href = currentStep?.actions?.finish?.href;
							if ( href ) {
								apiFetch( {
									url: href,
									method: 'POST',
								} );

								navigateToNextStep();
							}
						} }
						isBusy={ false }
						disabled={ false }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
