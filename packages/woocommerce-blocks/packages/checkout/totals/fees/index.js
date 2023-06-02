/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TotalsItem from '../item';

const TotalsFees = ( { currency, cartFees, className } ) => {
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
						className={ classnames(
							'wc-block-components-totals-fees',
							className
						) }
						currency={ currency }
						label={
							name || __( 'Fee', 'woocommerce' )
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
	className: PropTypes.string,
};

export default TotalsFees;
