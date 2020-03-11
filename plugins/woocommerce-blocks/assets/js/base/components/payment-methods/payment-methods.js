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

const noPaymentMethodTab = () => {
	const label = __( 'Not Existing', 'woo-gutenberg-products-block' );
	return {
		name: label,
		label,
		title: () => label,
	};
};

const createTabs = ( paymentMethods ) => {
	const paymentMethodIds = Object.keys( paymentMethods );
	return paymentMethodIds.length > 0
		? paymentMethodIds.map( ( id ) => {
				const { label, ariaLabel } = paymentMethods[ id ];
				return {
					name: id,
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
	const { isEditor } = useCheckoutContext();
	const { isInitialized, paymentMethods } = usePaymentMethods();
	const currentPaymentMethods = useRef( paymentMethods );
	const {
		activePaymentMethod,
		setActivePaymentMethod,
		...paymentMethodInterface
	} = usePaymentMethodInterface();
	const currentPaymentMethodInterface = useRef( paymentMethodInterface );

	// update ref on changes
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
