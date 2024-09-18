/**
 * External dependencies
 */
import { getValidBlockAttributes } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block';
import { ExpressPaymentContext } from '../../../cart-checkout-shared/payment-methods/express-payment/express-payment-context';
import metadata from './block.json';
import { ExpressCheckoutAttributes } from '../../../cart-checkout-shared/types';

const FrontendBlock = ( attributes: ExpressCheckoutAttributes ) => {
	const validAttributes = getValidBlockAttributes(
		metadata.attributes,
		attributes
	);

	const { showButtonStyles, buttonHeight, buttonBorderRadius } =
		validAttributes;

	return (
		<ExpressPaymentContext.Provider
			value={ { showButtonStyles, buttonHeight, buttonBorderRadius } }
		>
			<Block />
		</ExpressPaymentContext.Provider>
	);
};

export default FrontendBlock;
