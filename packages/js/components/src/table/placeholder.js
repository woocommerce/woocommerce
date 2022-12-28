/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import { range } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Table from './table';

/**
 * `TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.
 */
class TablePlaceholder extends Component {
	render() {
		const { numberOfRows, ...tableProps } = this.props;
		const rows = range( numberOfRows ).map( () =>
			this.props.headers.map( () => ( {
				display: <span className="is-placeholder" />,
			} ) )
		);

		return (
			<Table
				ariaHidden={ true }
				className="is-loading"
				rows={ rows }
				{ ...tableProps }
			/>
		);
	}
}

TablePlaceholder.propTypes = {
	/**
	 *  An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.
	 */
	query: PropTypes.object,
	/**
	 * A label for the content in this table.
	 */
	caption: PropTypes.string.isRequired,
	/**
	 * An array of column headers (see `Table` props).
	 */
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			hiddenByDefault: PropTypes.bool,
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.node,
			required: PropTypes.bool,
		} )
	),
	/**
	 * An integer with the number of rows to display.
	 */
	numberOfRows: PropTypes.number,
};

TablePlaceholder.defaultProps = {
	numberOfRows: 5,
};

export default TablePlaceholder;
