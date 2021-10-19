/**
 * External dependencies
 */
import { PaymentMethodIcons } from '@woocommerce/base-components/cart-checkout';
import { usePaymentMethods } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import type PaymentMethodConfig from '../../../../../blocks-registry/payment-methods/payment-method-config';

const getIconsFromPaymentMethods = (
	paymentMethods: PaymentMethodConfig[]
) => {
	return Object.values( paymentMethods ).reduce( ( acc, paymentMethod ) => {
		if ( paymentMethod.icons !== null ) {
			acc = acc.concat( paymentMethod.icons );
		}
		return acc;
	}, [] );
};

const Block = (): JSX.Element => {
	const { paymentMethods } = usePaymentMethods();

	return (
		<PaymentMethodIcons
			icons={ getIconsFromPaymentMethods( paymentMethods ) }
		/>
	);
};

export default Block;
