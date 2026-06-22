/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { useStoreCart } from '@woocommerce/base-context';
import { getSelectedShippingRateNames } from '@woocommerce/base-utils';
import type { CartShippingRate } from '@woocommerce/types';

export const ShippingVia = ( {
	showRateNames = true,
	shippingRates: shippingRatesProp,
}: {
	showRateNames?: boolean;
	shippingRates?: CartShippingRate[];
} ): JSX.Element | null => {
	const { shippingRates: cartShippingRates } = useStoreCart();
	const shippingRates = shippingRatesProp ?? cartShippingRates;
	const rateNames = getSelectedShippingRateNames( shippingRates );
	const discountLabels = Array.from(
		new Set(
			shippingRates.flatMap( ( shippingPackage ) => {
				return shippingPackage.shipping_rates
					.filter( ( rate ) => rate.selected && rate.discount_label )
					.map( ( rate ) => rate.discount_label || '' );
			} )
		)
	).filter( Boolean );

	if ( ( ! showRateNames || rateNames.length === 0 ) && discountLabels.length === 0 ) {
		return null;
	}

	return (
		<>
			{ showRateNames && rateNames.length > 0 && (
				<div className="wc-block-components-totals-shipping__via">
					{ decodeEntities(
						rateNames
							.filter(
								( item, index ) =>
									rateNames.indexOf( item ) === index
							)
							.join( ', ' )
					) }
				</div>
			) }
			{ discountLabels.length > 0 && (
				<div className="wc-block-components-totals-shipping__discount">
					{ decodeEntities( discountLabels.join( ', ' ) ) }
				</div>
			) }
		</>
	);
};

export default ShippingVia;
