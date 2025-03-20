/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { RecommendedPaymentMethod } from '@woocommerce/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../data/onboarding-context';
import { PaymentMethodListItem } from './payment-method-list-item';
import './payment-methods-selection.scss';

export default function PaymentMethodsSelection() {
	const { currentStep } = useOnboardingContext();
	const [ isExpanded, setIsExpanded ] = useState( false );
	const [ paymentMethodsState, setPaymentMethodsState ] = useState< {
		[ key: string ]: boolean;
	} >( {} );

	const recommendedPaymentMethods =
		currentStep?.data?.recommended_payment_methods ?? [];

	return (
		<>
			<div className="woocommerce-layout__header woocommerce-recommended-payment-methods">
				<div className="woocommerce-layout__header-wrapper">
					<h1 className="components-truncate components-text woocommerce-layout__header-heading woocommerce-layout__header-left-align">
						<span className="woocommerce-settings-payments-header__title">
							{ __(
								'Choose your payment methods',
								'woocommerce'
							) }
						</span>
					</h1>
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
										paymentMethodsState={
											paymentMethodsState
										}
										setPaymentMethodsState={
											setPaymentMethodsState
										}
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
										( pm: RecommendedPaymentMethod ) =>
											pm.enabled === false
									).length ?? 0
								) }
							</Button>
						) }
					</div>
				</div>
				<div className="woocommerce-recommended-payment-methods__list_footer">
					<Button
						className="components-button is-primary"
						onClick={ () => {} }
						isBusy={ false }
						disabled={ false }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</>
	);
}
