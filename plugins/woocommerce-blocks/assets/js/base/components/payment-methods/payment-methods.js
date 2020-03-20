/**
 * External dependencies
 */
import {
	usePaymentMethods,
	usePaymentMethodInterface,
} from '@woocommerce/base-hooks';
import {
	useCallback,
	cloneElement,
	useRef,
	useEffect,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import Tabs from '../tabs';
import NoPaymentMethods from './no-payment-methods/index';

/**
 * Returns a payment method for the given context.
 *
 * @param {string} id The payment method slug to return.
 * @param {Object} paymentMethods The current registered payment methods
 * @param {boolean} isEditor Whether in the editor context (true) or not (false).
 *
 * @return {Object} The payment method matching the id for the given context.
 */
const getPaymentMethod = ( id, paymentMethods, isEditor ) => {
	let paymentMethod = paymentMethods[ id ] || null;
	if ( paymentMethod ) {
		paymentMethod = isEditor
			? paymentMethod.edit
			: paymentMethod.activeContent;
	}
	return paymentMethod;
};

/**
 * PaymentMethods component.
 *
 * @return {*} The rendered component.
 */
const PaymentMethods = () => {
	const { isEditor } = useCheckoutContext();
	const { isInitialized, paymentMethods } = usePaymentMethods();
	const currentPaymentMethods = useRef( paymentMethods );
	const {
		activePaymentMethod,
		setActivePaymentMethod,
		...paymentMethodInterface
	} = usePaymentMethodInterface();
	const currentPaymentMethodInterface = useRef( paymentMethodInterface );

	// update ref on change.
	useEffect( () => {
		currentPaymentMethods.current = paymentMethods;
		currentPaymentMethodInterface.current = paymentMethodInterface;
	}, [ paymentMethods, paymentMethodInterface, activePaymentMethod ] );

	const getRenderedTab = useCallback(
		() => ( selectedTab ) => {
			const paymentMethod = getPaymentMethod(
				selectedTab,
				currentPaymentMethods.current,
				isEditor
			);
			return paymentMethod
				? cloneElement( paymentMethod, {
						activePaymentMethod: paymentMethod.id,
						...currentPaymentMethodInterface.current,
				  } )
				: null;
		},
		[ isEditor, activePaymentMethod ]
	);

	if ( ! isInitialized || Object.keys( paymentMethods ).length === 0 ) {
		return <NoPaymentMethods />;
	}

	return (
		<Tabs
			className="wc-block-components-checkout-payment-methods"
			onSelect={ ( tabName ) => setActivePaymentMethod( tabName ) }
			tabs={ Object.keys( paymentMethods ).map( ( id ) => {
				const { label, ariaLabel } = paymentMethods[ id ];
				return {
					name: id,
					title: () => label,
					ariaLabel,
				};
			} ) }
			initialTabName={ activePaymentMethod }
			ariaLabel={ __(
				'Payment Methods',
				'woo-gutenberg-products-block'
			) }
		>
			{ getRenderedTab() }
		</Tabs>
	);
};

export default PaymentMethods;
