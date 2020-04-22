/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { TAXES_ENABLED } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import TotalsItem from '../totals-item';

const TotalsTaxesItem = ( { currency, values } ) => {
	const { total_tax: totalTax } = values;

	if ( ! TAXES_ENABLED ) {
		return null;
	}

	return (
		<TotalsItem
			currency={ currency }
			label={ __( 'Taxes', 'woo-gutenberg-products-block' ) }
			value={ parseInt( totalTax, 10 ) }
		/>
	);
};

TotalsTaxesItem.propTypes = {
	currency: PropTypes.object.isRequired,
	values: PropTypes.shape( {
		total_tax: PropTypes.string,
	} ).isRequired,
};

export default TotalsTaxesItem;
