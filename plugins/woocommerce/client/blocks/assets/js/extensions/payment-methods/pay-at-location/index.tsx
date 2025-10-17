/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getPaymentMethodData } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { RawHTML } from '@wordpress/element';
import { CanMakePaymentArgument } from '@woocommerce/type-defs/payments';
import { selectedRatesAreCollectable } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';

const settings = getPaymentMethodData( 'pay-at-location', {} );
const defaultLabel = __( 'Pay at location', 'woocommerce' );
const label = decodeEntities( settings?.title || '' ) || defaultLabel;

/**
 * Content component
 */
const Content = () => {
	return <RawHTML>{ sanitizeHTML( settings.description || '' ) }</RawHTML>;
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Determine whether Pay at Location is available for this cart/order.
 *
 * @return {boolean}  True if Pay at Location payment method should be displayed as a payment option.
 */
const canMakePayment = ( {
	cartNeedsShipping,
	cart,
}: CanMakePaymentArgument ) => {
	if ( settings.enableForVirtual && ! cartNeedsShipping ) {
		// Store allows Pay at Location for virtual orders.
		return true;
	}
	return selectedRatesAreCollectable( cart.shippingRates );
};

/**
 * Pay at location payment method config object.
 */
const payAtLocationPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment,
	ariaLabel: label,
	supports: {
		features: settings?.supports ?? [],
	},
};

registerPaymentMethod( payAtLocationPaymentMethod );
