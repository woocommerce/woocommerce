/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';
import { TotalsItem } from '@woocommerce/blocks-checkout';

const TotalsFees = ( { currency, cartFees } ) => {
	return (
		<>
			{ cartFees.map( ( { id, name, totals } ) => {
				const feesValue = parseInt( totals.total, 10 );

				if ( ! feesValue ) {
					return null;
				}

				const feesTaxValue = parseInt( totals.total_tax, 10 );

				return (
					<TotalsItem
						key={ id }
						className="wc-block-components-totals-fees"
						currency={ currency }
						label={
							name || __( 'Fee', 'woo-gutenberg-products-block' )
						}
						value={
							DISPLAY_CART_PRICES_INCLUDING_TAX
								? feesValue + feesTaxValue
								: feesValue
						}
					/>
				);
			} ) }
		</>
	);
};

TotalsFees.propTypes = {
	currency: PropTypes.object.isRequired,
	cartFees: PropTypes.array.isRequired,
};

export default TotalsFees;
