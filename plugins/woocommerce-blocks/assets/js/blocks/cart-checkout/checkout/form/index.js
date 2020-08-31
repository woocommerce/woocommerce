/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	AddressForm,
	FormStep,
	ShippingRatesControl,
} from '@woocommerce/base-components/cart-checkout';
import Form from '@woocommerce/base-components/form';
import { ValidatedTextInput } from '@woocommerce/base-components/text-input';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import {
	getCurrencyFromPriceResponse,
	getShippingRatesPackageCount,
	getShippingRatesRateCount,
} from '@woocommerce/base-utils';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import {
	useCheckoutContext,
	useEditorContext,
	useShippingDataContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';
import {
	usePaymentMethods,
	useCheckoutAddress,
	useStoreCart,
} from '@woocommerce/base-hooks';
import { PaymentMethods } from '@woocommerce/base-components/payment-methods';
import { decodeEntities } from '@wordpress/html-entities';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import CheckoutOrderNotes from './order-notes';
import LoginPrompt from './login-prompt';
import NoShippingPlaceholder from './no-shipping-placeholder';
import './style.scss';

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

const CheckoutForm = ( {
	requireCompanyField,
	requirePhoneField,
	showApartmentField,
	showCompanyField,
	showOrderNotes,
	showPhoneField,
} ) => {
	const { isEditor } = useEditorContext();
	const { cartNeedsPayment } = useStoreCart();
	const {
		isProcessing: checkoutIsProcessing,
		onSubmit,
		orderNotes,
		dispatchActions,
	} = useCheckoutContext();
	const { setOrderNotes } = dispatchActions;
	const {
		shippingRates,
		shippingRatesLoading,
		needsShipping,
	} = useShippingDataContext();
	const { paymentMethods } = usePaymentMethods();
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
				hidden: ! showCompanyField,
				required: requireCompanyField,
			},
			address_2: {
				...defaultAddressFields.address_2,
				hidden: ! showApartmentField,
			},
		};
	}, [
		defaultAddressFields,
		showCompanyField,
		requireCompanyField,
		showApartmentField,
	] );

	return (
		<Form className="wc-block-checkout__form" onSubmit={ onSubmit }>
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
				stepHeadingContent={ () => <LoginPrompt /> }
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
						fields={ Object.keys( defaultAddressFields ) }
						fieldConfig={ addressFieldsConfig }
					/>
					{ showPhoneField && (
						<ValidatedTextInput
							id="phone"
							type="tel"
							label={
								requirePhoneField
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
							required={ requirePhoneField }
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
						fields={ Object.keys( defaultAddressFields ) }
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
						getShippingRatesRateCount( shippingRates ) > 1
							? __(
									'Select shipping options below.',
									'woo-gutenberg-products-block'
							  )
							: ''
					}
				>
					{ isEditor &&
					! getShippingRatesPackageCount( shippingRates ) ? (
						<NoShippingPlaceholder />
					) : (
						<ShippingRatesControl
							noResultsMessage={ __(
								'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.',
								'woo-gutenberg-products-block'
							) }
							renderOption={ renderShippingRatesControlOption }
							shippingRates={ shippingRates }
							shippingRatesLoading={ shippingRatesLoading }
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
			{ showOrderNotes && (
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
		</Form>
	);
};

CheckoutForm.propTypes = {
	requireCompanyField: PropTypes.bool.isRequired,
	requirePhoneField: PropTypes.bool.isRequired,
	showApartmentField: PropTypes.bool.isRequired,
	showCompanyField: PropTypes.bool.isRequired,
	showOrderNotes: PropTypes.bool.isRequired,
	showPhoneField: PropTypes.bool.isRequired,
};

export default CheckoutForm;
