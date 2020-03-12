/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	DISPLAY_CART_PRICES_INCLUDING_TAX,
	SHIPPING_ENABLED,
} from '@woocommerce/block-settings';
import ShippingCalculator from '@woocommerce/base-components/shipping-calculator';
import ShippingLocation from '@woocommerce/base-components/shipping-location';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TotalsItem from '../totals-item';

const TotalsShippingItem = ( {
	currency,
	shippingAddress,
	updateShippingAddress,
	values,
} ) => {
	if ( ! SHIPPING_ENABLED ) {
		return null;
	}
	const {
		total_shipping: totalShipping,
		total_shipping_tax: totalShippingTax,
	} = values;
	const shippingValue = parseInt( totalShipping, 10 );
	const shippingTaxValue = parseInt( totalShippingTax, 10 );

	return (
		<TotalsItem
			currency={ currency }
			description={
				<>
					{ shippingAddress && (
						<ShippingLocation address={ shippingAddress } />
					) }
					{ updateShippingAddress && shippingAddress && (
						<ShippingCalculator
							address={ shippingAddress }
							setAddress={ updateShippingAddress }
						/>
					) }
				</>
			}
			label={ __( 'Shipping', 'woo-gutenberg-products-block' ) }
			value={
				DISPLAY_CART_PRICES_INCLUDING_TAX
					? shippingValue + shippingTaxValue
					: shippingValue
			}
		/>
	);
};

TotalsShippingItem.propTypes = {
	currency: PropTypes.object.isRequired,
	shippingAddress: PropTypes.object,
	updateShippingAddress: PropTypes.func,
	values: PropTypes.shape( {
		total_shipping: PropTypes.string,
		total_shipping_tax: PropTypes.string,
	} ).isRequired,
};

export default TotalsShippingItem;
