/**
 * @typedef {import('./type-defs').StripePaymentItem} StripePaymentItem
 * @typedef {import('./type-defs').StripeShippingOption} StripeShippingOption
 * @typedef {import('./type-defs').StripeShippingAddress} StripeShippingAddress
 * @typedef {import('./type-defs').StripePaymentResponse} StripePaymentResponse
 * @typedef {import('@woocommerce/type-defs/payment-method-interface').PreparedCartTotalItem} CartTotalItem
 * @typedef {import('@woocommerce/type-defs/cart').CartShippingOption} CartShippingOption
 * @typedef {import('@woocommerce/type-defs/shipping').ShippingAddress} CartShippingAddress
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} CartBillingAddress
 */

/**
 * Normalizes incoming cart total items for use as a displayItems with the
 * Stripe api.
 *
 * @param {CartTotalItem[]} cartTotalItems CartTotalItems to normalize
 * @param {boolean}         pending        Whether to mark items as pending or
 *                                         not
 *
 * @return {StripePaymentItem[]} An array of PaymentItems
 */
const normalizeLineItems = ( cartTotalItems, pending = false ) => {
	return cartTotalItems
		.map( ( cartTotalItem ) => {
			return cartTotalItem.value
				? {
						amount: cartTotalItem.value,
						label: cartTotalItem.label,
						pending,
				  }
				: false;
		} )
		.filter( Boolean );
};

/**
 * Normalizes incoming cart shipping option items for use as shipping options
 * with the Stripe api.
 *
 * @param {CartShippingOption[]}  shippingOptions An array of CartShippingOption items.
 *
 * @return {StripeShippingOption[]}  An array of Stripe shipping option items.
 */
const normalizeShippingOptions = ( shippingOptions ) => {
	const rates = shippingOptions[ 0 ].shipping_rates;
	return rates.map( ( rate ) => {
		return {
			id: rate.rate_id,
			label: rate.name,
			detail: rate.description,
			amount: parseInt( rate.price, 10 ),
		};
	} );
};

/**
 * Normalize shipping address information from stripe's address object to
 * the cart shipping address object shape.
 *
 * @param {StripeShippingAddress} shippingAddress Stripe's shipping address item
 *
 * @return {CartShippingAddress} The shipping address in the shape expected by
 * the cart.
 */
const normalizeShippingAddressForCheckout = ( shippingAddress ) => {
	const address = {
		first_name: shippingAddress.recipient
			.split( ' ' )
			.slice( 0, 1 )
			.join( ' ' ),
		last_name: shippingAddress.recipient
			.split( ' ' )
			.slice( 1 )
			.join( ' ' ),
		company: '',
		address_1:
			typeof shippingAddress.addressLine[ 0 ] === 'undefined'
				? ''
				: shippingAddress.addressLine[ 0 ],
		address_2:
			typeof shippingAddress.addressLine[ 1 ] === 'undefined'
				? ''
				: shippingAddress.addressLine[ 1 ],
		city: shippingAddress.city,
		state: shippingAddress.region,
		country: shippingAddress.country,
		postcode: shippingAddress.postalCode.replace( ' ', '' ),
	};
	return address;
};

/**
 * Normalizes shipping option shape selection from Stripe's shipping option
 * object to the expected shape for cart shipping option selections.
 *
 * @param {StripeShippingOption} shippingOption The customer's selected shipping
 *                                              option.
 *
 * @return {string[]}  An array of ids (in this case will just be one)
 */
const normalizeShippingOptionSelectionsForCheckout = ( shippingOption ) => {
	return shippingOption.id;
};

/**
 * Returns the billing data extracted from the stripe payment response to the
 * CartBillingData shape.
 *
 * @param {StripePaymentResponse} paymentResponse Stripe's payment response
 *                                                object.
 *
 * @return {CartBillingAddress} The cart billing data
 */
const getBillingData = ( paymentResponse ) => {
	const source = paymentResponse.source;
	const name = source && source.owner.name;
	const billing = source && source.owner.address;
	const payerEmail = paymentResponse.payerEmail || '';
	const payerPhone = paymentResponse.payerPhone || '';
	return {
		first_name: name ? name.split( ' ' ).slice( 0, 1 ).join( ' ' ) : '',
		last_name: name ? name.split( ' ' ).slice( 1 ).join( ' ' ) : '',
		email: ( source && source.owner.email ) || payerEmail,
		phone:
			( source && source.owner.phone ) ||
			payerPhone.replace( '/[() -]/g', '' ),
		country: ( billing && billing.country ) || '',
		address_1: ( billing && billing.line1 ) || '',
		address_2: ( billing && billing.line2 ) || '',
		city: ( billing && billing.city ) || '',
		state: ( billing && billing.state ) || '',
		postcode: ( billing && billing.postal_code ) || '',
		company: '',
	};
};

/**
 * This returns extra payment method data to add to the payment method update
 * request made by the checkout processor.
 *
 * @param {StripePaymentResponse} paymentResponse    A stripe payment response
 *                                                   object.
 * @param {string}                paymentRequestType The payment request type
 *                                                   used for payment.
 *
 * @return {Object} An object with the extra payment data.
 */
const getPaymentMethodData = ( paymentResponse, paymentRequestType ) => {
	return {
		payment_method: 'stripe',
		stripe_source: paymentResponse.source
			? paymentResponse.source.id
			: null,
		payment_request_type: paymentRequestType,
	};
};

const getShippingData = ( paymentResponse ) => {
	return paymentResponse.shippingAddress
		? {
				address: normalizeShippingAddressForCheckout(
					paymentResponse.shippingAddress
				),
		  }
		: null;
};

export {
	normalizeLineItems,
	normalizeShippingOptions,
	normalizeShippingAddressForCheckout,
	normalizeShippingOptionSelectionsForCheckout,
	getBillingData,
	getPaymentMethodData,
	getShippingData,
};
