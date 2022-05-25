/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * A component to display summarized table data - the list of data passed in on a single line.
 *
 * @param {Object} props
 * @param {Array}  props.data
 * @return {Object} -
 */
const TableSummary = ( { data } ) => {
	return (
		<ul className="woocommerce-table__summary" role="complementary">
			{ data.map( ( { label, value }, i ) => (
				<li className="woocommerce-table__summary-item" key={ i }>
					<span className="woocommerce-table__summary-value">
						{ value }
					</span>
					<span className="woocommerce-table__summary-label">
						{ label }
					</span>
				</li>
			) ) }
		</ul>
	);
};

TableSummary.propTypes = {
	/**
	 * An array of objects with `label` & `value` properties, which display on a single line.
	 */
	data: PropTypes.array,
};

export default TableSummary;

/**
 * A component to display a placeholder box for `TableSummary`. There is no prop for this component.
 *
 * @return {Object} -
 */
export const TableSummaryPlaceholder = () => {
	return (
		<ul
			className="woocommerce-table__summary is-loading"
			role="complementary"
		>
			<li className="woocommerce-table__summary-item">
				<span className="is-placeholder" />
			</li>
		</ul>
	);
};
