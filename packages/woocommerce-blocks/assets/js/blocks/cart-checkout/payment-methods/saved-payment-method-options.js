/**
 * External dependencies
 */
import {
	useEffect,
	useRef,
	useCallback,
	cloneElement,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { usePaymentMethodDataContext } from '@woocommerce/base-context';
import RadioControl from '@woocommerce/base-components/radio-control';
import {
	usePaymentMethodInterface,
	usePaymentMethods,
} from '@woocommerce/base-context/hooks';
import { getPaymentMethods } from '@woocommerce/blocks-registry';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').CustomerPaymentMethod} CustomerPaymentMethod
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatch} PaymentStatusDispatch
 */

/**
 * Returns the option object for a cc or echeck saved payment method token.
 *
 * @param {CustomerPaymentMethod} savedPaymentMethod
 * @param {function(string):void} setActivePaymentMethod
 * @param {PaymentStatusDispatch} setPaymentStatus
 * @return {Object} An option objects to use for RadioControl.
 */
const getCcOrEcheckPaymentMethodOption = (
	{ method, expires, tokenId },
	setActivePaymentMethod,
	setPaymentStatus
) => {
	return {
		value: tokenId + '',
		label: sprintf(
			/* translators: %1$s is referring to the payment method brand, %2$s is referring to the last 4 digits of the payment card, %3$s is referring to the expiry date.  */
			__(
				'%1$s ending in %2$s (expires %3$s)',
				'woo-gutenberg-product-blocks'
			),
			method.brand,
			method.last4,
			expires
		),
		name: `wc-saved-payment-method-token-${ tokenId }`,
		onChange: ( token ) => {
			const savedTokenKey = `wc-${ method.gateway }-payment-token`;
			setActivePaymentMethod( method.gateway );
			setPaymentStatus().started( {
				payment_method: method.gateway,
				[ savedTokenKey ]: token + '',
				isSavedToken: true,
			} );
		},
	};
};

/**
 * Returns the option object for any non specific saved payment method.
 *
 * @param {CustomerPaymentMethod} savedPaymentMethod
 * @param {function(string):void} setActivePaymentMethod
 * @param {PaymentStatusDispatch} setPaymentStatus
 *
 * @return {Object} An option objects to use for RadioControl.
 */
const getDefaultPaymentMethodOptions = (
	{ method, tokenId },
	setActivePaymentMethod,
	setPaymentStatus
) => {
	return {
		value: tokenId + '',
		label: sprintf(
			/* translators: %s is the name of the payment method gateway. */
			__( 'Saved token for %s', 'woocommerce' ),
			method.gateway
		),
		name: `wc-saved-payment-method-token-${ tokenId }`,
		onChange: ( token ) => {
			const savedTokenKey = `wc-${ method.gateway }-payment-token`;
			setActivePaymentMethod( method.gateway );
			setPaymentStatus().started( {
				payment_method: method.gateway,
				[ savedTokenKey ]: token + '',
				isSavedToken: true,
			} );
		},
	};
};

const SavedPaymentMethodOptions = () => {
	const {
		setPaymentStatus,
		customerPaymentMethods,
		activePaymentMethod,
		setActivePaymentMethod,
		activeSavedToken,
		setActiveSavedToken,
	} = usePaymentMethodDataContext();
	const standardMethods = getPaymentMethods();
	const { paymentMethods } = usePaymentMethods();
	const paymentMethodInterface = usePaymentMethodInterface();

	/**
	 * @type      {Object} Options
	 * @property  {Array}  current  The current options on the type.
	 */
	const currentOptions = useRef( [] );

	const updateToken = useCallback(
		( token ) => {
			setActiveSavedToken( token );
		},
		[ setActiveSavedToken ]
	);

	useEffect( () => {
		const types = Object.keys( customerPaymentMethods );
		const options = types
			.flatMap( ( type ) => {
				const typeMethods = customerPaymentMethods[ type ];
				return typeMethods.map( ( paymentMethod ) => {
					const option =
						type === 'cc' || type === 'echeck'
							? getCcOrEcheckPaymentMethodOption(
									paymentMethod,
									setActivePaymentMethod,
									setPaymentStatus
							  )
							: getDefaultPaymentMethodOptions(
									paymentMethod,
									setActivePaymentMethod,
									setPaymentStatus
							  );
					if (
						! activePaymentMethod &&
						paymentMethod.is_default &&
						activeSavedToken === ''
					) {
						updateToken( paymentMethod.tokenId + '' );
						option.onChange( paymentMethod.tokenId );
					}
					return option;
				} );
			} )
			.filter( Boolean );
		currentOptions.current = options;
	}, [
		customerPaymentMethods,
		updateToken,
		activeSavedToken,
		activePaymentMethod,
		setActivePaymentMethod,
		setPaymentStatus,
		standardMethods,
	] );

	const savedPaymentMethodHandler =
		!! activeSavedToken &&
		paymentMethods[ activePaymentMethod ] &&
		paymentMethods[ activePaymentMethod ]?.savedTokenComponent
			? cloneElement(
					paymentMethods[ activePaymentMethod ]?.savedTokenComponent,
					{ token: activeSavedToken, ...paymentMethodInterface }
			  )
			: null;

	return currentOptions.current.length > 0 ? (
		<>
			<RadioControl
				id={ 'wc-payment-method-saved-tokens' }
				selected={ activeSavedToken }
				onChange={ updateToken }
				options={ currentOptions.current }
			/>
			{ savedPaymentMethodHandler }
		</>
	) : null;
};

export default SavedPaymentMethodOptions;
