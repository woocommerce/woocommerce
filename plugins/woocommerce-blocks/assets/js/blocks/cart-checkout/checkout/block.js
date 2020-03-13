/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import AddressForm from '@woocommerce/base-components/address-form';
import defaultAddressFields from '@woocommerce/base-components/address-form/default-address-fields';
import {
	FormStep,
	CheckoutForm,
	NoShipping,
	Policies,
} from '@woocommerce/base-components/checkout';
import TextInput from '@woocommerce/base-components/text-input';
import ShippingRatesControl from '@woocommerce/base-components/shipping-rates-control';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { CheckoutProvider } from '@woocommerce/base-context';
import {
	ExpressCheckoutFormControl,
	PaymentMethods,
} from '@woocommerce/base-components/payment-methods';
import { SHIPPING_ENABLED } from '@woocommerce/block-settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useShippingRates } from '@woocommerce/base-hooks';
import {
	Sidebar,
	SidebarLayout,
	Main,
} from '@woocommerce/base-components/sidebar-layout';

/**
 * Internal dependencies
 */
import CheckoutSidebar from './sidebar.js';
import './style.scss';
import '../../../payment-methods-demo';

const Block = ( {
	attributes,
	cartCoupons = [],
	cartItems = [],
	cartTotals = {},
	isEditor = false,
	shippingRates = [],
} ) => {
	const [ selectedShippingRate, setSelectedShippingRate ] = useState( {} );
	const [ contactFields, setContactFields ] = useState( {} );
	const [ shouldSavePayment, setShouldSavePayment ] = useState( true );
	const [ billingFields, setBillingFields ] = useState( {} );
	const [ useShippingAsBilling, setUseShippingAsBilling ] = useState(
		attributes.useShippingAsBilling
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

	const useShippingAddressAsBilling = isEditor
		? attributes.useShippingAsBilling
		: useShippingAsBilling;
	const showBillingFields =
		! SHIPPING_ENABLED || ! useShippingAddressAsBilling;

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

	const {
		shippingRatesLoading,
		shippingAddress: shippingFields,
		setShippingAddress: setShippingFields,
	} = useShippingRates();

	return (
		<CheckoutProvider isEditor={ isEditor }>
			<SidebarLayout className="wc-block-checkout">
				<Main>
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
							<TextInput
								type="email"
								label={ __(
									'Email address',
									'woo-gutenberg-products-block'
								) }
								value={ contactFields.email }
								autoComplete="email"
								onChange={ ( newValue ) =>
									setContactFields( {
										...contactFields,
										email: newValue,
									} )
								}
								required={ true }
							/>
							<CheckboxControl
								className="wc-block-checkout__keep-updated"
								label={ __(
									'Keep me up to date on news and exclusive offers',
									'woo-gutenberg-products-block'
								) }
								checked={ contactFields.keepUpdated }
								onChange={ () =>
									setContactFields( {
										...contactFields,
										keepUpdated: ! contactFields.keepUpdated,
									} )
								}
							/>
						</FormStep>
						{ SHIPPING_ENABLED && (
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
									onChange={ setShippingFields }
									values={ shippingFields }
									fields={ Object.keys( addressFields ) }
									fieldConfig={ addressFields }
								/>
								{ attributes.showPhoneField && (
									<TextInput
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
										value={ shippingFields.phone }
										autoComplete="tel"
										onChange={ ( newValue ) =>
											setShippingFields( {
												...shippingFields,
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
									checked={ useShippingAddressAsBilling }
									onChange={ ( isChecked ) =>
										setUseShippingAsBilling( isChecked )
									}
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
									onChange={ setBillingFields }
									type="billing"
									values={ billingFields }
									fields={ Object.keys( addressFields ) }
									fieldConfig={ addressFields }
								/>
							</FormStep>
						) }
						{ SHIPPING_ENABLED &&
							( shippingRates.length === 0 && isEditor ? (
								<NoShipping />
							) : (
								<FormStep
									id="shipping-option"
									className="wc-block-checkout__shipping-option"
									title={ __(
										'Shipping options',
										'woo-gutenberg-products-block'
									) }
									description={ __(
										'Select your shipping method below.',
										'woo-gutenberg-products-block'
									) }
								>
									<ShippingRatesControl
										address={
											shippingFields.country
												? {
														address_1:
															shippingFields.address_1,
														address_2:
															shippingFields.apartment,
														city:
															shippingFields.city,
														state:
															shippingFields.state,
														postcode:
															shippingFields.postcode,
														country:
															shippingFields.country,
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

									<CheckboxControl
										className="wc-block-checkout__add-note"
										label="Add order notes?"
										checked={
											selectedShippingRate.orderNote
										}
										onChange={ () =>
											setSelectedShippingRate( {
												...selectedShippingRate,
												orderNote: ! selectedShippingRate.orderNote,
											} )
										}
									/>
								</FormStep>
							) ) }
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
							{ /*@todo this should be something the payment method controls*/ }
							<CheckboxControl
								className="wc-block-checkout__save-card-info"
								label={ __(
									'Save payment information to my account for future purchases.',
									'woo-gutenberg-products-block'
								) }
								checked={ shouldSavePayment }
								onChange={ () =>
									setShouldSavePayment( ! shouldSavePayment )
								}
							/>
						</FormStep>
						{ attributes.showPolicyLinks && <Policies /> }
					</CheckoutForm>
				</Main>
				<Sidebar className="wc-block-checkout__sidebar">
					<CheckoutSidebar
						cartCoupons={ cartCoupons }
						cartItems={ cartItems }
						cartTotals={ cartTotals }
						shippingRates={ shippingRates }
					/>
				</Sidebar>
			</SidebarLayout>
		</CheckoutProvider>
	);
};

export default Block;
