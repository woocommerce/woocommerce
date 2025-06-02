/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { paymentStore } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { CheckoutExpressPaymentsSkeleton } from '@woocommerce/base-components/skeleton/patterns/checkout-express-payments';

/**
 * Internal dependencies
 */
import { CheckoutExpressPayment } from '../../../cart-checkout-shared/payment-methods';

const Block = ( { className }: { className?: string } ): JSX.Element | null => {
	const { cartNeedsPayment, cartIsLoading } = useStoreCart();
	const { paymentMethodsInitialized } = useSelect( ( select ) => {
		const store = select( paymentStore );
		return {
			paymentMethodsInitialized: store.paymentMethodsInitialized(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
			savedPaymentMethods: store.getSavedPaymentMethods(),
		};
	} );

	if ( ! paymentMethodsInitialized ) {
		return <CheckoutExpressPaymentsSkeleton />;
	}

	if ( ! cartNeedsPayment && ! cartIsLoading ) {
		return null;
	}

	return (
		<div className={ className }>
			<CheckoutExpressPayment />
		</div>
	);
};

export default Block;
