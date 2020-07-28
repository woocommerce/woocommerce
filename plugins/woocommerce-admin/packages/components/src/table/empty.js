/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * `EmptyTable` displays a blank space with an optional message passed as a children node
 * with the purpose of replacing a table with no rows.
 * It mimics the same height a table would have according to the `numberOfRows` prop.
 *
 * @param root0
 * @param root0.children
 * @param root0.numberOfRows
 * @return {Object} -
 */
const EmptyTable = ( { children, numberOfRows } ) => {
	return (
		<div
			className="woocommerce-table is-empty"
			style={ { '--number-of-rows': numberOfRows } }
		>
			{ children }
		</div>
	);
};

EmptyTable.propTypes = {
	/**
	 * An integer with the number of rows the box should occupy.
	 */
	numberOfRows: PropTypes.number,
};

EmptyTable.defaultProps = {
	numberOfRows: 5,
};

export default EmptyTable;
