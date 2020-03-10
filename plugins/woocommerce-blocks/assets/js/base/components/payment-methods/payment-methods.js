/**
 * External dependencies
 */
import {
	useCheckoutData,
	usePaymentEvents,
	useActivePaymentMethod,
	usePaymentMethods,
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

const noPaymentMethodTab = () => {
	const label = __( 'Not Existing', 'woo-gutenberg-products-block' );
	return {
		name: label,
		label,
		title: () => label,
	};
};

const createTabs = ( paymentMethods ) => {
	const paymentMethodsKeys = Object.keys( paymentMethods );
	return paymentMethodsKeys.length > 0
		? paymentMethodsKeys.map( ( key ) => {
				const { label, ariaLabel } = paymentMethods[ key ];
				return {
					name: key,
					title: () => label,
					ariaLabel,
				};
		  } )
		: [ noPaymentMethodTab() ];
};

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

const PaymentMethods = () => {
	const [ checkoutData ] = useCheckoutData();
	const { isEditor } = useCheckoutContext();
	const { dispatch, select } = usePaymentEvents();
	const { isInitialized, paymentMethods } = usePaymentMethods();
	const currentPaymentMethods = useRef( paymentMethods );

	// update ref on changes
	useEffect( () => {
		currentPaymentMethods.current = paymentMethods;
	}, [ paymentMethods ] );

	const {
		activePaymentMethod,
		setActivePaymentMethod,
	} = useActivePaymentMethod();
	const getRenderedTab = useCallback(
		() => ( selectedTab ) => {
			const paymentMethod = getPaymentMethod(
				selectedTab,
				currentPaymentMethods.current,
				isEditor
			);
			const paymentEvents = { dispatch, select };
			return paymentMethod
				? cloneElement( paymentMethod, {
						isActive: true,
						checkoutData,
						paymentEvents,
				  } )
				: null;
		},
		[ checkoutData, dispatch, select, isEditor ]
	);
	if (
		! isInitialized ||
		( Object.keys( paymentMethods ).length === 0 && isInitialized )
	) {
		// @todo this can be a placeholder informing the user there are no
		// payment methods setup?
		return <div>No Payment Methods Initialized</div>;
	}
	return (
		<Tabs
			className="wc-component__payment-method-options"
			onSelect={ ( tabName ) => setActivePaymentMethod( tabName ) }
			tabs={ createTabs( paymentMethods ) }
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
