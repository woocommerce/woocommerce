/**
 * External dependencies
 */
import {
	useExpressPaymentMethods,
	usePaymentMethodInterface,
} from '@woocommerce/base-hooks';
import { cloneElement, isValidElement } from '@wordpress/element';
import { useCheckoutContext } from '@woocommerce/base-context';

const ExpressPaymentMethods = () => {
	const { isEditor } = useCheckoutContext();
	const paymentMethodInterface = usePaymentMethodInterface();
	// not implementing isInitialized here because it's utilized further
	// up in the tree for express payment methods. We won't even get here if
	// there's no payment methods after initialization.
	const { paymentMethods } = useExpressPaymentMethods();
	const paymentMethodIds = Object.keys( paymentMethods );
	const content =
		paymentMethodIds.length > 0 ? (
			paymentMethodIds.map( ( id ) => {
				const expressPaymentMethod = isEditor
					? paymentMethods[ id ].edit
					: paymentMethods[ id ].content;
				return isValidElement( expressPaymentMethod ) ? (
					<li key={ id } id={ `express-payment-method-${ id }` }>
						{ cloneElement( expressPaymentMethod, {
							...paymentMethodInterface,
						} ) }
					</li>
				) : null;
			} )
		) : (
			<li key="noneRegistered">No registered Payment Methods</li>
		);
	return (
		<ul className="wc-block-component-express-checkout-payment-event-buttons">
			{ content }
		</ul>
	);
};

export default ExpressPaymentMethods;
