/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { TotalsWrapper } from '@woocommerce/blocks-checkout';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import {
	filterShippingRatesByPrefersCollection,
	isAddressComplete,
	isPackageRateCollectable,
	hasShippingRate,
} from '@woocommerce/base-utils';

const Block = ( {
	className = '',
}: {
	className?: string;
} ): JSX.Element | null => {
	const {
		cartTotals,
		cartNeedsShipping,
		shippingRates: cartShippingRates,
		shippingAddress,
		cartHasCalculatedShipping,
	} = useStoreCart();
	const prefersCollection = useSelect( ( select ) => {
		return select( CHECKOUT_STORE_KEY ).prefersCollection();
	} );

	if ( ! cartNeedsShipping ) {
		return null;
	}

	const hasCompleteAddress = isAddressComplete( shippingAddress, [
		'state',
		'country',
		'postcode',
		'city',
	] );

	const shippingRates = filterShippingRatesByPrefersCollection(
		cartShippingRates,
		prefersCollection ?? false
	);
	const hasRates =
		cartHasCalculatedShipping && hasShippingRate( shippingRates );
	const isCollectionOnly = hasRates
		? shippingRates.every( ( shippingPackage ) => {
				return shippingPackage.shipping_rates.every(
					( rate ) =>
						! rate.selected || isPackageRateCollectable( rate )
				);
		  } )
		: false;

	return (
		<TotalsWrapper className={ className }>
			<TotalsShipping
				label={
					isCollectionOnly
						? __( 'Collection', 'woocommerce' )
						: __( 'Delivery', 'woocommerce' )
				}
				hasRates={ hasRates }
				shippingAddress={ shippingAddress }
				shippingRates={ shippingRates }
				values={ cartTotals }
				placeholder={
					<span className="wc-block-components-shipping-placeholder__value">
						{ hasCompleteAddress
							? __(
									'No available delivery option',
									'woocommerce'
							  )
							: __(
									'Enter address to calculate',
									'woocommerce'
							  ) }
					</span>
				}
			/>
		</TotalsWrapper>
	);
};

export default Block;
