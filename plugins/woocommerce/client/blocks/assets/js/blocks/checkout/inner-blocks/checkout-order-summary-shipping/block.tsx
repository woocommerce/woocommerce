/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { useStoreCart } from '@woocommerce/base-context';
import { TotalsWrapper } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import {
	filterShippingRatesByPrefersCollection,
	hasAllFieldsForShippingRates,
} from '@woocommerce/base-utils';
import { Skeleton } from '@woocommerce/base-components/skeleton';

const Block = ( {
	className = '',
}: {
	className?: string;
} ): JSX.Element | null => {
	const { cartNeedsShipping, shippingRates, shippingAddress } =
		useStoreCart();
	const { prefersCollection, isShippingRatesStale } = useSelect(
		( select ) => ( {
			prefersCollection: select( checkoutStore ).prefersCollection(),
			isShippingRatesStale: select( cartStore ).isShippingRatesStale(),
		} ),
		[]
	);

	if ( ! cartNeedsShipping ) {
		return null;
	}

	const filteredRates = filterShippingRatesByPrefersCollection(
		shippingRates,
		prefersCollection ?? false
	);

	const hasCompleteAddress = hasAllFieldsForShippingRates( shippingAddress );

	// Rates for a completed address are still on their way, so expect some rather than reporting there are none.
	const placeholder =
		hasCompleteAddress && isShippingRatesStale ? (
			<Skeleton
				width="45px"
				height="1em"
				ariaMessage={ __( 'Loading price…', 'woocommerce' ) }
			/>
		) : (
			<span className="wc-block-components-shipping-placeholder__value">
				{ hasCompleteAddress
					? __( 'No available delivery option', 'woocommerce' )
					: __( 'Enter address to calculate', 'woocommerce' ) }
			</span>
		);

	return (
		<TotalsWrapper className={ className }>
			<TotalsShipping
				shippingRates={ filteredRates }
				label={
					prefersCollection
						? __( 'Pickup', 'woocommerce' )
						: __( 'Delivery', 'woocommerce' )
				}
				placeholder={ placeholder }
			/>
		</TotalsWrapper>
	);
};

export default Block;
