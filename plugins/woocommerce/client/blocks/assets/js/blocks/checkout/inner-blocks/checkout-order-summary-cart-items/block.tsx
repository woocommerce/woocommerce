/**
 * External dependencies
 */
import { OrderSummary } from '@woocommerce/base-components/cart-checkout';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { TotalsWrapper } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './edit';

const Block = ( {
	className = '',
	disableProductDescriptions = false,
}: BlockAttributes ): JSX.Element => {
	const { cartItems } = useStoreCart();

	return (
		<TotalsWrapper className={ className }>
			<OrderSummary
				cartItems={ cartItems }
				disableProductDescriptions={ disableProductDescriptions }
			/>
		</TotalsWrapper>
	);
};

export default Block;
