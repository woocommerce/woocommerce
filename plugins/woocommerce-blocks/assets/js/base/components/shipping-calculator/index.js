/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from './address';
import './style.scss';

const ShippingCalculator = ( { address, setAddress } ) => {
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] = useState(
		false
	);

	return (
		<span className="wc-block-cart__change-address">
			(
			<button
				className="wc-block-cart__change-address-button"
				onClick={ () => {
					setIsShippingCalculatorOpen( ! isShippingCalculatorOpen );
				} }
			>
				{ __( 'change address', 'woo-gutenberg-products-block' ) }
			</button>
			)
			{ isShippingCalculatorOpen && (
				<ShippingCalculatorAddress
					address={ address }
					onUpdate={ ( newAddress ) => {
						setAddress( newAddress );
						setIsShippingCalculatorOpen( false );
					} }
				/>
			) }
		</span>
	);
};

ShippingCalculator.propTypes = {
	address: PropTypes.shape( {
		city: PropTypes.string,
		state: PropTypes.string,
		postcode: PropTypes.string,
		country: PropTypes.string,
	} ),
	setAddress: PropTypes.func.isRequired,
};

export default ShippingCalculator;
