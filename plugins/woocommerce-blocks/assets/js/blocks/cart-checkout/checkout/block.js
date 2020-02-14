/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import FormStep from '@woocommerce/base-components/checkout/form-step';
import CheckoutForm from '@woocommerce/base-components/checkout/form';
import NoShipping from '@woocommerce/base-components/checkout/no-shipping';
import TextInput from '@woocommerce/base-components/text-input';
import { ShippingCountryInput } from '@woocommerce/base-components/country-input';
import { ShippingCountyInput } from '@woocommerce/base-components/county-input';
import ShippingRatesControl from '@woocommerce/base-components/shipping-rates-control';
import InputRow from '@woocommerce/base-components/input-row';
import { CheckboxControl } from '@wordpress/components';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import CheckoutProvider from '@woocommerce/base-context/checkout-context';
import {
	ExpressCheckoutFormControl,
	PaymentMethods,
} from '@woocommerce/base-components/payment-methods';

/**
 * Internal dependencies
 */
import './style.scss';
import '../../../payment-methods-demo';

/**
 * Component displaying an attribute filter.
 */
const Block = ( { shippingMethods = [], isEditor = false } ) => {
	const [ shippingMethod, setShippingMethod ] = useState( {} );
	const [ contactFields, setContactFields ] = useState( {} );
	const [ shouldSavePayment, setShouldSavePayment ] = useState( true );
	const [ shippingFields, setShippingFields ] = useState( {} );
	return (
		<CheckoutProvider>
			<ExpressCheckoutFormControl />
			<CheckoutForm>
				<FormStep
					id="billing-fields"
					className="wc-block-checkout__billing-fields"
					title={ __(
						'Contact information',
						'woo-gutenberg-products-block'
					) }
					description={ __(
						"We'll use this email to send you details and updates about your order.",
						'woo-gutenberg-products-block'
					) }
					stepNumber={ 1 }
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
						onChange={ ( newValue ) =>
							setContactFields( {
								...contactFields,
								email: newValue,
							} )
						}
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
					stepNumber={ 2 }
				>
					<InputRow>
						<TextInput
							label={ __(
								'First name',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.firstName }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									firstName: newValue,
								} )
							}
						/>
						<TextInput
							label={ __(
								'Surname',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.lastName }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									lastName: newValue,
								} )
							}
						/>
					</InputRow>
					<TextInput
						label={ __(
							'Street address',
							'woo-gutenberg-products-block'
						) }
						value={ shippingFields.streetAddress }
						onChange={ ( newValue ) =>
							setShippingFields( {
								...shippingFields,
								streetAddress: newValue,
							} )
						}
					/>
					<TextInput
						label={ __(
							'Apartment, suite, etc.',
							'woo-gutenberg-products-block'
						) }
						value={ shippingFields.apartment }
						onChange={ ( newValue ) =>
							setShippingFields( {
								...shippingFields,
								apartment: newValue,
							} )
						}
					/>
					<InputRow>
						<ShippingCountryInput
							label={ __(
								'Country / Region',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.country }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									country: newValue,
									county: '',
								} )
							}
						/>
						<TextInput
							label={ __(
								'City',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.city }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									city: newValue,
								} )
							}
						/>
					</InputRow>
					<InputRow>
						<ShippingCountyInput
							country={ shippingFields.country }
							label={ __(
								'County',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.county }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									county: newValue,
								} )
							}
						/>
						<TextInput
							label={ __(
								'Postal code',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.postalCode }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									postalCode: newValue,
								} )
							}
						/>
					</InputRow>
					<TextInput
						type="tel"
						label={ __( 'Phone', 'woo-gutenberg-products-block' ) }
						value={ shippingFields.phone }
						onChange={ ( newValue ) =>
							setShippingFields( {
								...shippingFields,
								phone: newValue,
							} )
						}
					/>
					<CheckboxControl
						className="wc-block-checkout__use-address-for-billing"
						label={ __(
							'Use same address for billing',
							'woo-gutenberg-products-block'
						) }
						checked={ shippingFields.useSameForBilling }
						onChange={ () =>
							setShippingFields( {
								...shippingFields,
								useSameForBilling: ! shippingFields.useSameForBilling,
							} )
						}
					/>
				</FormStep>
				{ shippingMethods.length === 0 && isEditor ? (
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
						stepNumber={ 3 }
					>
						<ShippingRatesControl
							address={
								shippingFields.country
									? {
											address_1:
												shippingFields.streetAddress,
											address_2: shippingFields.apartment,
											city: shippingFields.city,
											state: shippingFields.county,
											postcode: shippingFields.postalCode,
											country: shippingFields.country,
									  }
									: null
							}
							noResultsMessage={ __(
								'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.',
								'woo-gutenberg-products-block'
							) }
							onChange={ ( newMethods ) =>
								setShippingMethod( {
									...shippingMethod,
									methods: newMethods,
								} )
							}
							renderOption={ ( option ) => ( {
								label: option.name,
								value: option.rate_id,
								description: option.description,
								secondaryLabel: (
									<FormattedMonetaryAmount
										currency={ getCurrencyFromPriceResponse(
											option
										) }
										value={ option.price }
									/>
								),
								secondaryDescription: option.delivery_time,
							} ) }
							selected={ shippingMethod.methods }
						/>
						<CheckboxControl
							className="wc-block-checkout__add-note"
							label="Add order notes?"
							checked={ shippingMethod.orderNote }
							onChange={ () =>
								setShippingMethod( {
									...shippingMethod,
									orderNote: ! shippingMethod.orderNote,
								} )
							}
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
					stepNumber={ 4 }
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
			</CheckoutForm>
		</CheckoutProvider>
	);
};

export default Block;
