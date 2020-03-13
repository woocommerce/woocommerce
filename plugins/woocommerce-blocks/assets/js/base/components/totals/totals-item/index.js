/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { isValidElement } from '@wordpress/element';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';

/**
 * Internal dependencies
 */
import './style.scss';

const TotalsItem = ( { className, currency, label, value, description } ) => {
	return (
		<div
			className={ classnames( 'wc-block-totals-table-item', className ) }
		>
			<span className="wc-block-totals-table-item__label">{ label }</span>
			{ isValidElement( value ) ? (
				<div className="wc-block-totals-table-item__value">
					{ value }
				</div>
			) : (
				<FormattedMonetaryAmount
					className="wc-block-totals-table-item__value"
					currency={ currency }
					displayType="text"
					value={ value }
				/>
			) }
			<div className="wc-block-totals-table-item__description">
				{ description }
			</div>
		</div>
	);
};

TotalsItem.propTypes = {
	currency: PropTypes.object,
	label: PropTypes.string.isRequired,
	value: PropTypes.oneOfType( [ PropTypes.number, PropTypes.node ] ),
	className: PropTypes.string,
	description: PropTypes.node,
};

export default TotalsItem;
