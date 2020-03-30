/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from './address';
import './style.scss';

const ShippingCalculator = ( { onUpdate, address, addressFields } ) => {
	return (
		<div className="wc-block-cart__shipping-calculator">
			<ShippingCalculatorAddress
				address={ address }
				addressFields={ addressFields }
				onUpdate={ ( newAddress ) => {
					onUpdate( newAddress );
				} }
			/>
		</div>
	);
};

ShippingCalculator.propTypes = {
	onUpdate: PropTypes.func.isRequired,
	address: PropTypes.object.isRequired,
	addressFields: PropTypes.array.isRequired,
};

export default ShippingCalculator;
