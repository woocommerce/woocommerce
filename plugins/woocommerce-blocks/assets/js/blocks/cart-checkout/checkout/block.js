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
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import {
	CheckoutProvider,
	useCheckoutContext,
	useEditorContext,
	useShippingDataContext,
	useBillingDataContext,
	useValidationContext,
} from '@woocommerce/base-context';
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

const Block = ( props ) => (
	<CheckoutProvider>
		<Checkout { ...props } />
	</CheckoutProvider>
);

const Checkout = ( {
	attributes,
	cartCoupons = [],
	cartItems = [],
	cartTotals = {},
	scrollToTop,
	shippingRates = [],
} ) => {
	const { isEditor } = useEditorContext();
	const {
		hasOrder,
		hasError: checkoutHasError,
		isComplete: checkoutIsComplete,
	} = useCheckoutContext();
	const { showAllValidationErrors } = useValidationContext();
	const {
		shippingRatesLoading,
		shippingAddress,
		setShippingAddress,
		needsShipping,
	} = useShippingDataContext();
	const { billingData, setBillingData } = useBillingDataContext();

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
			hidden: ! attributes.showAddress2Field,
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

	useEffect( () => {
		if ( checkoutIsComplete && checkoutHasError ) {
			showAllValidationErrors();
			scrollToTop( { focusableSelector: 'input:invalid' } );
		}
	}, [ checkoutIsComplete, checkoutHasError ] );

	if ( ! isEditor && ! hasOrder ) {
		return <CheckoutOrderError />;
	}

	return (
		<>
			<SidebarLayout className="wc-block-checkout">
				<Main className="wc-block-checkout__main">
					<ExpressCheckoutFormControl />
					<CheckoutForm>
						<FormStep
							id="contact-fields"
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
									required={ attributes.requirePhoneField }
								/>
							</FormStep>
						) }
						{ showBillingFields && (
							<FormStep
								id="billing-fields"
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
								className="wc-block-checkout__shipping-option"
								title={ __(
									'Shipping options',
									'woo-gutenberg-products-block'
								) }
								description={ __(
									'Select a shipping method below.',
									'woo-gutenberg-products-block'
								) }
							>
								{ shippingRates.length === 0 && isEditor ? (
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
						<FormStep
							id="payment-method"
							className="wc-block-checkout__payment-method"
							title={ __(
								'Payment method',
								'woo-gutenberg-products-block'
							) }
							description={ __(
								'Select a payment method below.',
								'woo-gutenberg-products-block'
							) }
						>
							<PaymentMethods />
						</FormStep>
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
