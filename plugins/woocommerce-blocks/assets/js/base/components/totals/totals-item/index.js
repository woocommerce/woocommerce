/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
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
			<FormattedMonetaryAmount
				className="wc-block-totals-table-item__value"
				currency={ currency }
				displayType="text"
				value={ value }
			/>
			<span className="wc-block-totals-table-item__description">
				{ description }
			</span>
		</div>
	);
};

TotalsItem.propTypes = {
	currency: PropTypes.object.isRequired,
	label: PropTypes.string.isRequired,
	value: PropTypes.number.isRequired,
	className: PropTypes.string,
	description: PropTypes.node,
};

export default TotalsItem;
