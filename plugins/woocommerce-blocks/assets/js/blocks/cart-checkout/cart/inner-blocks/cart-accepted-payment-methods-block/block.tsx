/**
 * External dependencies
 */
import { PaymentMethodIcons } from '@woocommerce/base-components/cart-checkout';
import { usePaymentMethods } from '@woocommerce/base-context/hooks';
import type {
	PaymentMethods,
	PaymentMethodIcons as PaymentMethodIconsType,
} from '@woocommerce/type-defs/payments';

const getIconsFromPaymentMethods = (
	paymentMethods: PaymentMethods
): PaymentMethodIconsType => {
	return Object.values( paymentMethods ).reduce( ( acc, paymentMethod ) => {
		if ( paymentMethod.icons !== null ) {
			acc = acc.concat( paymentMethod.icons );
		}
		return acc;
	}, [] as PaymentMethodIconsType );
};

const Block = ( { className }: { className: string } ): JSX.Element => {
	const { paymentMethods } = usePaymentMethods();

	return (
		<PaymentMethodIcons
			className={ className }
			icons={ getIconsFromPaymentMethods( paymentMethods ) }
		/>
	);
};

export default Block;
