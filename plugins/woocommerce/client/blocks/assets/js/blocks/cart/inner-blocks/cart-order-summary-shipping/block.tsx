/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { useStoreCart } from '@woocommerce/base-context';
import { TotalsWrapper } from '@woocommerce/blocks-checkout';
import { hasSelectedShippingRate } from '@woocommerce/base-utils';

const Block = ( { className }: { className: string } ) => {
	const { cartNeedsShipping, shippingRates } = useStoreCart();

	if ( ! cartNeedsShipping ) {
		return null;
	}

	const hasSelectedRates = hasSelectedShippingRate( shippingRates );

	if ( ! hasSelectedRates ) {
		return null;
	}

	return (
		<TotalsWrapper className={ className }>
			<TotalsShipping
				label={ __( 'Shipping', 'woocommerce' ) }
				placeholder={
					<span className="wc-block-components-shipping-placeholder__value">
						{ __( 'Calculated at checkout', 'woocommerce' ) }
					</span>
				}
			/>
		</TotalsWrapper>
	);
};

export default Block;
