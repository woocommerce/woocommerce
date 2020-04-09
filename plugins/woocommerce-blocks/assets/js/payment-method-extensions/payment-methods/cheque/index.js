/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';

const settings = getSetting( 'cheque_data', {} );

const EditPlaceHolder = () => <div>TODO: Edit preview soon...</div>;

/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 */

/**
 * Cheque content component
 *
 * @param {RegisteredPaymentMethodProps|Object} props Incoming props
 */
const Content = ( { activePaymentMethod, eventRegistration } ) => {
	// hook into payment processing event.
	useEffect( () => {
		const unsubscribeProcessing = eventRegistration.onPaymentProcessing(
			() => true
		);
		return () => {
			unsubscribeProcessing();
		};
	}, [ eventRegistration.onPaymentProcessing ] );
	return activePaymentMethod === PAYMENT_METHOD_NAME ? (
		<div>{ decodeEntities( settings.description || '' ) }</div>
	) : null;
};

const offlineChequePaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: (
		<strong>
			{ decodeEntities(
				settings.title ||
					__( 'Check Payment', 'woo-gutenberg-products-block' )
			) }
		</strong>
	),
	content: <Content />,
	edit: <EditPlaceHolder />,
	canMakePayment: () => true,
	ariaLabel: decodeEntities(
		settings.title || __( 'Check Payment', 'woo-gutenberg-products-block' )
	),
};

registerPaymentMethod( ( Config ) => new Config( offlineChequePaymentMethod ) );
