/**
 * External dependencies
 */
import classnames from 'classnames';
import { useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
	useValidationContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';
import {
	useStoreCart,
	usePaymentMethods,
	useStoreNotices,
	useCheckoutAddress,
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
import {
	CHECKOUT_SHOW_LOGIN_REMINDER,
	CHECKOUT_ALLOWS_GUEST,
	DISPLAY_CART_PRICES_INCLUDING_TAX,
} from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import CheckoutSidebar from './sidebar';
import CheckoutOrderError from './checkout-order-error';
import CheckoutOrderNotes from './checkout-order-notes';
import NoShippingPlaceholder from './no-shipping-placeholder';
import './style.scss';

/**
 * Renders the Checkout block wrapped within the CheckoutProvider.
 *
 * @param {Object} props Component props.
 * @return {*} The component.
 */
const Block = ( props ) => {
	return (
		<CheckoutProvider>
			<Checkout { ...props } />
		</CheckoutProvider>
	);
};

/**
 * Renders a shipping rate control option.
 *
 * @param {Object} option Shipping Rate.
 */
const renderShippingRatesControlOption = ( option ) => {
	const priceWithTaxes = DISPLAY_CART_PRICES_INCLUDING_TAX
		? parseInt( option.price, 10 ) + parseInt( option.taxes, 10 )
		: parseInt( option.price, 10 );
	return {
		label: decodeEntities( option.name ),
		value: option.rate_id,
		description: decodeEntities( option.description ),
		secondaryLabel: (
			<FormattedMonetaryAmount
				currency={ getCurrencyFromPriceResponse( option ) }
				value={ priceWithTaxes }
			/>
		),
		secondaryDescription: decodeEntities( option.delivery_time ),
	};
};

/**
 * Main Checkout Component.
 *
 * @param {Object} props Component props.
 * @return {*} The component.
 */
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
		customerId,
		onSubmit,
		orderNotes,
		dispatchActions,
	} = useCheckoutContext();
	const { setOrderNotes } = dispatchActions;
	const {
		hasValidationErrors,
		showAllValidationErrors,
	} = useValidationContext();
	const {
		shippingRates,
		shippingRatesLoading,
		needsShipping,
	} = useShippingDataContext();
	const { paymentMethods } = usePaymentMethods();
	const { hasNoticesOfType } = useStoreNotices();
	const {
		defaultAddressFields,
		shippingFields,
		setShippingFields,
		billingFields,
		setBillingFields,
		setEmail,
		setPhone,
		shippingAsBilling,
		setShippingAsBilling,
		showBillingFields,
	} = useCheckoutAddress();
	const addressFieldsConfig = useMemo( () => {
		return {
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
	}, [ defaultAddressFields, attributes ] );

	const hasErrorsToDisplay =
		checkoutIsIdle &&
		checkoutHasError &&
		( hasValidationErrors || hasNoticesOfType( 'default' ) );

	useEffect( () => {
		if ( hasErrorsToDisplay ) {
			showAllValidationErrors();
			scrollToTop( { focusableSelector: 'input:invalid' } );
		}
	}, [ hasErrorsToDisplay, scrollToTop, showAllValidationErrors ] );

	if ( ! isEditor && ! hasOrder ) {
		return <CheckoutOrderError />;
	}

	const loginToCheckoutUrl = `/wp-login.php?redirect_to=${ encodeURIComponent(
		window.location.href
	) }`;

	if ( ! isEditor && ! customerId && ! CHECKOUT_ALLOWS_GUEST ) {
		return (
			<>
				{ __(
					'You must be logged in to checkout. ',
					'woo-gutenberg-products-block'
				) }
				<a href={ loginToCheckoutUrl }>
					{ __(
						'Click here to log in.',
						'woo-gutenberg-products-block'
					) }
				</a>
			</>
		);
	}

	const loginPrompt = () =>
		CHECKOUT_SHOW_LOGIN_REMINDER &&
		! customerId && (
			<>
				{ __(
					'Already have an account? ',
					'woo-gutenberg-products-block'
				) }
				<a href={ loginToCheckoutUrl }>
					{ __( 'Log in.', 'woo-gutenberg-products-block' ) }
				</a>
			</>
		);

	const checkoutClassName = classnames( 'wc-block-checkout', {
		'has-dark-controls': attributes.hasDarkControls,
	} );
	return (
		<>
			<SidebarLayout className={ checkoutClassName }>
				<Main className="wc-block-checkout__main">
					{ cartNeedsPayment && <ExpressCheckoutFormControl /> }
					<CheckoutForm onSubmit={ onSubmit }>
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
							stepHeadingContent={ loginPrompt }
						>
							<ValidatedTextInput
								id="email"
								type="email"
								label={ __(
									'Email address',
									'woo-gutenberg-products-block'
								) }
								value={ billingFields.email }
								autoComplete="email"
								onChange={ setEmail }
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
									values={ shippingFields }
									fields={ Object.keys(
										defaultAddressFields
									) }
									fieldConfig={ addressFieldsConfig }
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
										value={ billingFields.phone }
										autoComplete="tel"
										onChange={ setPhone }
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
									onChange={ setBillingFields }
									type="billing"
									values={ billingFields }
									fields={ Object.keys(
										defaultAddressFields
									) }
									fieldConfig={ addressFieldsConfig }
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
								{ isEditor &&
								! getShippingRatesPackageCount(
									shippingRates
								) ? (
									<NoShippingPlaceholder />
								) : (
									<ShippingRatesControl
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
						{ attributes.showOrderNotes && (
							<FormStep id="order-notes" showStepNumber={ false }>
								<CheckoutOrderNotes
									disabled={ checkoutIsProcessing }
									onChange={ setOrderNotes }
									placeholder={
										needsShipping
											? __(
													'Notes about your order, e.g. special notes for delivery.',
													'woo-gutenberg-products-block'
											  )
											: __(
													'Notes about your order.',
													'woo-gutenberg-products-block'
											  )
									}
									value={ orderNotes }
								/>
							</FormStep>
						) }
					</CheckoutForm>
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
				<Sidebar className="wc-block-checkout__sidebar">
					<CheckoutSidebar
						cartCoupons={ cartCoupons }
						cartItems={ cartItems }
						cartTotals={ cartTotals }
					/>
				</Sidebar>
			</SidebarLayout>
		</>
	);
};

export default withScrollToTop( Block );
