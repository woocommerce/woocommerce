/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { range } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Table from './table';

class TablePlaceholder extends Component {
	render() {
		const { caption, headers, numberOfRows } = this.props;
		const rows = range( numberOfRows ).map( () =>
			headers.map( () => ( { display: <span className="is-placeholder" /> } ) )
		);

		return (
			<Table
				ariaHidden={ true }
				caption={ caption }
				classNames="is-loading"
				headers={ headers }
				rowHeader={ false }
				rows={ rows }
			/>
		);
	}
}

TablePlaceholder.propTypes = {
	caption: PropTypes.string.isRequired,
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.string,
			required: PropTypes.bool,
		} )
	),
	numberOfRows: PropTypes.number,
};

TablePlaceholder.defaultProps = {
	numberOfRows: 5,
};

export default TablePlaceholder;
