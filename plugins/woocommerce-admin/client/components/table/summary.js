/** @format */
/**
 * External dependencies
 */
import PropTypes from 'prop-types';

const TableSummary = ( { data } ) => {
	return (
		<ul className="woocommerce-table__summary">
			{ data.map( ( { label, value }, i ) => (
				<li className="woocommerce-table__summary-item" key={ i }>
					<span className="woocommerce-table__summary-value">{ value }</span>
					<span className="woocommerce-table__summary-label">{ label }</span>
				</li>
			) ) }
		</ul>
	);
};

TableSummary.propTypes = {
	data: PropTypes.array,
};

export default TableSummary;
