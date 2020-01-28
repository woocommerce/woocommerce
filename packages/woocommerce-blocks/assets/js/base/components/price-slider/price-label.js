/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

const PriceLabel = ( { minPrice, maxPrice } ) => {
	if ( ! minPrice && ! maxPrice ) {
		return null;
	}
	return (
		<div className="wc-block-price-filter__range-text">
			{ sprintf(
				// translators: %s: low price, %s: high price.
				__( 'Price: %s — %s', 'woocommerce' ),
				minPrice,
				maxPrice
			) }
		</div>
	);
};

PriceLabel.propTypes = {
	/**
	 * Min price to display.
	 */
	minPrice: PropTypes.string.isRequired,
	/**
	 * Max price to display.
	 */
	maxPrice: PropTypes.string.isRequired,
};

export default PriceLabel;
