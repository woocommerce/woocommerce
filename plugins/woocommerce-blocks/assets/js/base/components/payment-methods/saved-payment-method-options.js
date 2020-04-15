/**
 * External dependencies
 */
import { useEffect, useState, useRef, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import RadioControl from '@woocommerce/base-components/radio-control';

/** @typedef {import('@woocommerce/type-defs/contexts').CustomerPaymentMethod} CustomerPaymentMethod */

/**
 * Returns the option object for a cc or echeck saved payment method token.
 *
 * @param {CustomerPaymentMethod} savedPaymentMethod
 *
 * @return {Object} An option objects to use for RadioControl.
 */
const getCcOrEcheckPaymentMethodOption = ( { method, expires, tokenId } ) => {
	return {
		value: tokenId,
		label: sprintf(
			__(
				'%1$s ending in %2$s (expires %3$s)',
				'woo-gutenberg-product-blocks'
			),
			method.brand,
			method.last4,
			expires
		),
		name: `wc-saved-payment-method-token-${ tokenId }`,
	};
};

/**
 * Returns the option object for any non specific saved payment method.
 *
 * @param {CustomerPaymentMethod} savedPaymentMethod
 *
 * @return {Object} An option objects to use for RadioControl.
 */
const getDefaultPaymentMethodOptions = ( { method, tokenId } ) => {
	return {
		value: tokenId,
		label: sprintf(
			__( 'Saved token for %s', 'woo-gutenberg-products-block' ),
			method.gateway
		),
		name: `wc-saved-payment-method-token-${ tokenId }`,
	};
};

const SavedPaymentMethodOptions = ( { onSelect } ) => {
	const { isEditor } = useEditorContext();
	const {
		setPaymentStatus,
		customerPaymentMethods,
		activePaymentMethod,
	} = usePaymentMethodDataContext();
	const [ selectedToken, setSelectedToken ] = useState( null );

	/**
	 * @type      {Object} Options
	 * @property  {Array}  current  The current options on the type.
	 */
	const currentOptions = useRef( [] );
	useEffect( () => {
		let options = [];
		const paymentMethodKeys = Object.keys( customerPaymentMethods );
		if ( paymentMethodKeys.length > 0 ) {
			paymentMethodKeys.forEach( ( type ) => {
				const paymentMethods = customerPaymentMethods[ type ];
				if ( paymentMethods.length > 0 ) {
					options = options.concat(
						paymentMethods.map( ( paymentMethod ) => {
							if (
								paymentMethod.is_default &&
								selectedToken === null
							) {
								setSelectedToken(
									parseInt( paymentMethod.tokenId, 10 )
								);
							}
							return type === 'cc' || type === 'echeck'
								? getCcOrEcheckPaymentMethodOption(
										paymentMethod
								  )
								: getDefaultPaymentMethodOptions(
										paymentMethod
								  );
						} )
					);
				}
			} );
			currentOptions.current = options;
			currentOptions.current.push( {
				value: 0,
				label: __(
					'Use a new payment method',
					'woo-gutenberg-product-blocks'
				),
				name: `wc-saved-payment-method-token-new`,
			} );
		}
	}, [ customerPaymentMethods, selectedToken ] );
	const updateToken = useCallback(
		( token ) => {
			if ( token !== '0' ) {
				// @todo this will need updated when the signature changes to
				// allow no billing data included.
				setPaymentStatus().success( null, {
					payment_method: activePaymentMethod,
					savedPaymentMethodToken: token,
				} );
			} else {
				setPaymentStatus().started();
			}
			setSelectedToken( parseInt( token, 10 ) );
			onSelect( parseInt( token, 10 ) );
		},
		[ setSelectedToken, setPaymentStatus, onSelect ]
	);
	useEffect( () => {
		if ( selectedToken && currentOptions.current.length > 0 ) {
			updateToken( selectedToken );
		}
	}, [ selectedToken, updateToken ] );

	// In the editor, show `Use a new payment method` option as selected.
	const selectedOption = isEditor ? 0 : selectedToken;
	return currentOptions.current.length > 0 ? (
		<RadioControl
			id={ 'wc-payment-method-stripe-saved-tokens' }
			selected={ selectedOption }
			onChange={ updateToken }
			options={ currentOptions.current }
		/>
	) : null;
};

export default SavedPaymentMethodOptions;
