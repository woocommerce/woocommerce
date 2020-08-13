/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import {
	TAXES_ENABLED,
	DISPLAY_ITEMIZED_TAXES,
} from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import TotalsItem from '../totals-item';

const TotalsTaxesItem = ( { currency, values } ) => {
	const { total_tax: totalTax, tax_lines: taxLines } = values;

	if ( ! TAXES_ENABLED ) {
		return null;
	}

	if ( ! DISPLAY_ITEMIZED_TAXES ) {
		return (
			<TotalsItem
				currency={ currency }
				label={ __( 'Taxes', 'woocommerce' ) }
				value={ parseInt( totalTax, 10 ) }
			/>
		);
	}

	return (
		<>
			{ taxLines.map( ( { name, price }, i ) => (
				<TotalsItem
					key={ `tax-line-${ i }` }
					currency={ currency }
					label={ name }
					value={ parseInt( price, 10 ) }
				/>
			) ) }{ ' ' }
		</>
	);
};

TotalsTaxesItem.propTypes = {
	currency: PropTypes.object.isRequired,
	values: PropTypes.shape( {
		total_tax: PropTypes.string,
	} ).isRequired,
};

export default TotalsTaxesItem;
