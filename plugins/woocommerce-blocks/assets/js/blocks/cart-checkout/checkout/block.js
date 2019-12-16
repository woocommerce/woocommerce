/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import FormStep from '@woocommerce/base-components/checkout/form-step';
import CheckoutForm from '@woocommerce/base-components/checkout/form';
import NoShipping from '@woocommerce/base-components/checkout/no-shipping';
import TextInput from '@woocommerce/base-components/text-input';
import RadioControl from '@woocommerce/base-components/radio-control';
import InputRow from '@woocommerce/base-components/input-row';
import { CheckboxControl, Placeholder } from '@wordpress/components';
/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component displaying an attribute filter.
 */
const Block = ( { shippingMethods = [], isEditor = false } ) => {
	const [ shippingMethod, setShippingMethod ] = useState( {} );
	const [ contactFields, setContactFields ] = useState( {} );
	const [ shouldSavePayment, setShouldSavePayment ] = useState( true );
	const [ shippingFields, setShippingFields ] = useState( {} );
	return (
		<CheckoutForm>
			<FormStep
				id="billing-fields"
				className="wc-blocks-checkout__billing-fields"
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
							{ __( 'Log in.', 'woo-gutenberg-products-block' ) }
						</a>
					</Fragment>
				) }
			>
				<TextInput
					id="email-field"
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
					className="wc-blocks-checkout__keep-updated"
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
			{ shippingMethods.length === 0 && (
				<FormStep
					id="shipping-fields"
					className="wc-blocks-checkout__shipping-fields"
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
					{ isEditor && <NoShipping /> }
				</FormStep>
			) }
			{ shippingMethods.length > 0 && (
				<Fragment>
					<FormStep
						id="shipping-fields"
						className="wc-blocks-checkout__shipping-fields"
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
								id="shipping-first-name"
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
								id="shipping-last-name"
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
							id="shipping-street-address"
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
							id="shipping-apartment"
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
							<TextInput
								id="shipping-country"
								label={ __(
									'Country',
									'woo-gutenberg-products-block'
								) }
								value={ shippingFields.country }
								onChange={ ( newValue ) =>
									setShippingFields( {
										...shippingFields,
										country: newValue,
									} )
								}
							/>
							<TextInput
								id="shipping-city"
								label={ __(
									'City',
									'woo-gutenberg-products-block'
								) }
								value={ shippingFields.country }
								onChange={ ( newValue ) =>
									setShippingFields( {
										...shippingFields,
										country: newValue,
									} )
								}
							/>
						</InputRow>
						<InputRow>
							<TextInput
								id="shipping-county"
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
								id="shipping-postal-code"
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
							id="shipping-phone"
							label={ __(
								'Phone',
								'woo-gutenberg-products-block'
							) }
							value={ shippingFields.phone }
							onChange={ ( newValue ) =>
								setShippingFields( {
									...shippingFields,
									phone: newValue,
								} )
							}
						/>
						<CheckboxControl
							className="wc-blocks-checkout__use-address-for-billing"
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
					<FormStep
						id="shipping-option"
						className="wc-blocks-checkout__shipping-option"
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
						<RadioControl
							selected={ shippingMethod.method || 'collect' }
							id="shipping-method"
							onChange={ ( option ) =>
								setShippingMethod( {
									...shippingMethod,
									method: option,
								} )
							}
							options={ [
								{
									label: 'Click & Collect',
									value: 'collect',
									description:
										'Pickup between 12:00 - 16:00 (Mon-Fri)',
									secondaryLabel: 'FREE',
								},
								{
									label: 'Regular shipping',
									value: 'usps-normal',
									description: 'Dispatched via USPS',
									secondaryLabel: '€10.00',
									secondaryDescription: '5 business days',
								},
								{
									label: 'Express shipping',
									value: 'ups-express',
									description: 'Dispatched via USPS',
									secondaryLabel: '€50.00',
									secondaryDescription: '2 business days',
								},
							] }
						/>
						<CheckboxControl
							className="wc-blocks-checkout__add-note"
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
				</Fragment>
			) }
			<FormStep
				id="payment-method"
				className="wc-blocks-checkout__payment-method"
				title={ __( 'Payment method', 'woo-gutenberg-products-block' ) }
				description={ __(
					'Select a payment method below.',
					'woo-gutenberg-products-block'
				) }
				stepNumber={ 4 }
			>
				<Placeholder>Payment methods, coming soon</Placeholder>
				<CheckboxControl
					className="wc-blocks-checkout__save-card-info"
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
	);
};

export default Block;
