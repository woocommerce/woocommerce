/**
 * External dependencies
 */
import { Fragment, useState, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import defaultAddressFields from '@woocommerce/base-components/cart-checkout/address-form/default-address-fields';
import {
	AddressForm,
	FormStep,
	CheckoutForm,
	PlaceOrderButton,
	Policies,
	ReturnToCartButton,
	ShippingRatesControl,
} from '@woocommerce/base-components/cart-checkout';
import { ValidatedTextInput } from '@woocommerce/base-components/text-input';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import {
	getCurrencyFromPriceResponse,
	getShippingRatesPackageCount,
	getShippingRatesRateCount,
} from '@woocommerce/base-utils';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import {
	CheckoutProvider,
	useCheckoutContext,
	useEditorContext,
	useShippingDataContext,
	useBillingDataContext,
	useValidationContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';
import {
	useStoreCart,
	usePaymentMethods,
	useStoreNotices,
} from '@woocommerce/base-hooks';
import {
	ExpressCheckoutFormControl,
	PaymentMethods,
} from '@woocommerce/base-components/payment-methods';
import { decodeEntities } from '@wordpress/html-entities';
import {
	Sidebar,
	SidebarLayout,
	Main,
} from '@woocommerce/base-components/sidebar-layout';
import { getSetting } from '@woocommerce/settings';
import withScrollToTop from '@woocommerce/base-hocs/with-scroll-to-top';

/**
 * Internal dependencies
 */
import CheckoutSidebar from './sidebar';
import CheckoutOrderError from './checkout-order-error';
import NoShippingPlaceholder from './no-shipping-placeholder';
import './style.scss';

const Block = ( props ) => {
	return (
		<CheckoutProvider>
			<Checkout { ...props } />
		</CheckoutProvider>
	);
};

const Checkout = ( { attributes, scrollToTop } ) => {
	const { isEditor } = useEditorContext();
	const {
		cartItems,
		cartTotals,
		cartCoupons,
		cartNeedsPayment,
	} = useStoreCart();
	const {
		hasOrder,
		hasError: checkoutHasError,
		isIdle: checkoutIsIdle,
		isProcessing: checkoutIsProcessing,
	} = useCheckoutContext();
	const {
		hasValidationErrors,
		showAllValidationErrors,
	} = useValidationContext();
	const {
		shippingRates,
		shippingRatesLoading,
		shippingAddress,
		setShippingAddress,
		needsShipping,
	} = useShippingDataContext();
	const { billingData, setBillingData } = useBillingDataContext();
	const { paymentMethods } = usePaymentMethods();
	const { hasNoticesOfType } = useStoreNotices();

	const [ shippingAsBilling, setShippingAsBilling ] = useState(
		needsShipping
	);

	const renderShippingRatesControlOption = ( option ) => ( {
		label: decodeEntities( option.name ),
		value: option.rate_id,
		description: decodeEntities( option.description ),
		secondaryLabel: (
			<FormattedMonetaryAmount
				currency={ getCurrencyFromPriceResponse( option ) }
				value={ option.price }
			/>
		),
		secondaryDescription: decodeEntities( option.delivery_time ),
	} );

	const showBillingFields = ! needsShipping || ! shippingAsBilling;
	const addressFields = {
		...defaultAddressFields,
		company: {
			...defaultAddressFields.company,
			hidden: ! attributes.showCompanyField,
			required: attributes.requireCompanyField,
		},
		address_2: {
			...defaultAddressFields.address_2,
			hidden: ! attributes.showApartmentField,
		},
	};

	const setShippingFields = useCallback(
		( address ) => {
			if ( shippingAsBilling ) {
				setShippingAddress( address );
				setBillingData( address );
			} else {
				setShippingAddress( address );
			}
		},
		[ setShippingAddress, setBillingData, shippingAsBilling ]
	);
	useEffect( () => {
		if ( shippingAsBilling ) {
			setBillingData( { ...shippingAddress, shippingAsBilling } );
		} else {
			setBillingData( { shippingAsBilling } );
		}
	}, [ shippingAsBilling, setBillingData ] );

	const hasErrorsToDisplay =
		checkoutIsIdle &&
		checkoutHasError &&
		( hasValidationErrors || hasNoticesOfType( 'default' ) );
	useEffect( () => {
		if ( hasErrorsToDisplay ) {
			showAllValidationErrors();
			scrollToTop( { focusableSelector: 'input:invalid' } );
		}
	}, [ hasErrorsToDisplay ] );

	if ( ! isEditor && ! hasOrder ) {
		return <CheckoutOrderError />;
	}

	return (
		<>
			<SidebarLayout className="wc-block-checkout">
				<Main className="wc-block-checkout__main">
					{ cartNeedsPayment && <ExpressCheckoutFormControl /> }
					<CheckoutForm>
						<FormStep
							id="contact-fields"
							disabled={ checkoutIsProcessing }
							className="wc-block-checkout__contact-fields"
							title={ __(
								'Contact information',
								'woo-gutenberg-products-block'
							) }
							description={ __(
								"We'll use this email to send you details and updates about your order.",
								'woo-gutenberg-products-block'
							) }
							stepHeadingContent={ () => (
								<Fragment>
									{ __(
										'Already have an account? ',
										'woo-gutenberg-products-block'
									) }
									<a href="/wp-login.php">
										{ __(
											'Log in.',
											'woo-gutenberg-products-block'
										) }
									</a>
								</Fragment>
							) }
						>
							<ValidatedTextInput
								id="email"
								type="email"
								label={ __(
									'Email address',
									'woo-gutenberg-products-block'
								) }
								value={ billingData.email }
								autoComplete="email"
								onChange={ ( newValue ) =>
									setBillingData( { email: newValue } )
								}
								required={ true }
							/>
						</FormStep>
						{ needsShipping && (
							<FormStep
								id="shipping-fields"
								disabled={ checkoutIsProcessing }
								className="wc-block-checkout__shipping-fields"
								title={ __(
									'Shipping address',
									'woo-gutenberg-products-block'
								) }
								description={ __(
									'Enter the physical address where you want us to deliver your order.',
									'woo-gutenberg-products-block'
								) }
							>
								<AddressForm
									id="shipping"
									onChange={ setShippingFields }
									values={ shippingAddress }
									fields={ Object.keys( addressFields ) }
									fieldConfig={ addressFields }
								/>
								{ attributes.showPhoneField && (
									<ValidatedTextInput
										id="phone"
										type="tel"
										label={
											attributes.requirePhoneField
												? __(
														'Phone',
														'woo-gutenberg-products-block'
												  )
												: __(
														'Phone (optional)',
														'woo-gutenberg-products-block'
												  )
										}
										value={ billingData.phone }
										autoComplete="tel"
										onChange={ ( newValue ) =>
											setBillingData( {
												phone: newValue,
											} )
										}
										required={
											attributes.requirePhoneField
										}
									/>
								) }
								<CheckboxControl
									className="wc-block-checkout__use-address-for-billing"
									label={ __(
										'Use same address for billing',
										'woo-gutenberg-products-block'
									) }
									checked={ shippingAsBilling }
									onChange={ ( isChecked ) =>
										setShippingAsBilling( isChecked )
									}
								/>
							</FormStep>
						) }
						{ showBillingFields && (
							<FormStep
								id="billing-fields"
								disabled={ checkoutIsProcessing }
								className="wc-block-checkout__billing-fields"
								title={ __(
									'Billing address',
									'woo-gutenberg-products-block'
								) }
								description={ __(
									'Enter the address that matches your card or payment method.',
									'woo-gutenberg-products-block'
								) }
							>
								<AddressForm
									id="billing"
									onChange={ setBillingData }
									type="billing"
									values={ billingData }
									fields={ Object.keys( addressFields ) }
									fieldConfig={ addressFields }
								/>
							</FormStep>
						) }
						{ needsShipping && (
							<FormStep
								id="shipping-option"
								disabled={ checkoutIsProcessing }
								className="wc-block-checkout__shipping-option"
								title={ __(
									'Shipping options',
									'woo-gutenberg-products-block'
								) }
								description={
									getShippingRatesRateCount( shippingRates ) >
									1
										? __(
												'Select shipping options below.',
												'woo-gutenberg-products-block'
										  )
										: ''
								}
							>
								{ getShippingRatesPackageCount(
									shippingRates
								) === 0 && isEditor ? (
									<NoShippingPlaceholder />
								) : (
									<ShippingRatesControl
										address={
											shippingAddress.country
												? {
														address_1:
															shippingAddress.address_1,
														address_2:
															shippingAddress.address_2,
														city:
															shippingAddress.city,
														state:
															shippingAddress.state,
														postcode:
															shippingAddress.postcode,
														country:
															shippingAddress.country,
												  }
												: null
										}
										noResultsMessage={ __(
											'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.',
											'woo-gutenberg-products-block'
										) }
										renderOption={
											renderShippingRatesControlOption
										}
										shippingRates={ shippingRates }
										shippingRatesLoading={
											shippingRatesLoading
										}
									/>
								) }
								{ /*@todo This is not implemented*/ }
								<CheckboxControl
									className="wc-block-checkout__add-note"
									label="Add order notes?"
									checked={ false }
									onChange={ () => null }
								/>
							</FormStep>
						) }
						{ cartNeedsPayment && (
							<FormStep
								id="payment-method"
								disabled={ checkoutIsProcessing }
								className="wc-block-checkout__payment-method"
								title={ __(
									'Payment method',
									'woo-gutenberg-products-block'
								) }
								description={
									Object.keys( paymentMethods ).length > 1
										? __(
												'Select a payment method below.',
												'woo-gutenberg-products-block'
										  )
										: ''
								}
							>
								<StoreNoticesProvider context="wc/payment-area">
									<PaymentMethods />
								</StoreNoticesProvider>
							</FormStep>
						) }
					</CheckoutForm>
				</Main>
				<Sidebar className="wc-block-checkout__sidebar">
					<CheckoutSidebar
						cartCoupons={ cartCoupons }
						cartItems={ cartItems }
						cartTotals={ cartTotals }
					/>
				</Sidebar>
				<Main className="wc-block-checkout__main-totals">
					<div className="wc-block-checkout__actions">
						{ attributes.showReturnToCart && (
							<ReturnToCartButton
								link={ getSetting(
									'page-' + attributes?.cartPageId,
									false
								) }
							/>
						) }
						<PlaceOrderButton />
					</div>
					{ attributes.showPolicyLinks && <Policies /> }
				</Main>
			</SidebarLayout>
		</>
	);
};

export default withScrollToTop( Block );
