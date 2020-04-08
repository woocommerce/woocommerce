/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { useShippingDataContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from './address';
import './style.scss';

const ShippingCalculator = ( {
	onUpdate = () => {},
	addressFields = [ 'country', 'state', 'city', 'postcode' ],
} ) => {
	const { shippingAddress, setShippingAddress } = useShippingDataContext();
	return (
		<div className="wc-block-cart__shipping-calculator">
			<ShippingCalculatorAddress
				address={ shippingAddress }
				addressFields={ addressFields }
				onUpdate={ ( newAddress ) => {
					setShippingAddress( newAddress );
					onUpdate( newAddress );
				} }
			/>
		</div>
	);
};

ShippingCalculator.propTypes = {
	onUpdate: PropTypes.func,
	addressFields: PropTypes.array,
};

export default ShippingCalculator;
