/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { getValidBlockAttributes } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { CheckoutExpressPayment } from '../../../cart-checkout-shared/payment-methods';
import metadata from './block.json';
import { ExpressCheckoutContext } from './context';

const Block = ( { className }: { className?: string } ): JSX.Element | null => {
	const { cartNeedsPayment } = useStoreCart();
	const { showButtonStyles, buttonHeight, buttonBorderRadius } =
		getValidBlockAttributes( metadata.attributes, [] );

	if ( ! cartNeedsPayment ) {
		return null;
	}

	return (
		<ExpressCheckoutContext.Provider
			value={ { showButtonStyles, buttonHeight, buttonBorderRadius } }
		>
			<div className={ className }>
				<CheckoutExpressPayment />
			</div>
		</ExpressCheckoutContext.Provider>
	);
};

export default Block;
