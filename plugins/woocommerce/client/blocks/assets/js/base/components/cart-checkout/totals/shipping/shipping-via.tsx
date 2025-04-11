/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { useStoreCart } from '@woocommerce/base-context';
import { getSelectedShippingRateNames } from '@woocommerce/base-utils';

export const ShippingVia = (): JSX.Element | null => {
	const { shippingRates } = useStoreCart();
	const rateNames = getSelectedShippingRateNames( shippingRates );
	return rateNames ? (
		<div className="wc-block-components-totals-shipping__via">
			{ decodeEntities(
				rateNames
					.filter(
						( item, index ) => rateNames.indexOf( item ) === index
					)
					.join( ', ' )
			) }
		</div>
	) : null;
};

export default ShippingVia;
