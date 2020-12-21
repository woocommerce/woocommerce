/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TotalsItem from '../item';

const TotalsFees = ( { currency, values } ) => {
	const { total_fees: totalFees, total_fees_tax: totalFeesTax } = values;
	const feesValue = parseInt( totalFees, 10 );

	if ( ! feesValue ) {
		return null;
	}

	const feesTaxValue = parseInt( totalFeesTax, 10 );

	return (
		<TotalsItem
			className="wc-block-components-totals-fees"
			currency={ currency }
			label={ __( 'Fees', 'woo-gutenberg-products-block' ) }
			value={
				DISPLAY_CART_PRICES_INCLUDING_TAX
					? feesValue + feesTaxValue
					: feesValue
			}
		/>
	);
};

TotalsFees.propTypes = {
	currency: PropTypes.object.isRequired,
	values: PropTypes.shape( {
		total_fees: PropTypes.string,
		total_fees_tax: PropTypes.string,
	} ).isRequired,
};

export default TotalsFees;
