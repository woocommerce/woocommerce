/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { range } from 'lodash';

/**
 * Internal dependencies
 */
import Table from './table';
import { QueryProps, TableHeader } from './types';

type TablePlaceholderProps = {
	// An object of the query parameters passed to the page
	query?: QueryProps;
	// A label for the content in this table.
	caption: string;
	// An integer with the number of rows to display.
	numberOfRows?: number;

	rowHeader?: number | false;

	// An array of column headers (see `Table` props).
	headers: Array< TableHeader >;
};

/**
 * `TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.
 */
const TablePlaceholder: React.VFC< TablePlaceholderProps > = ( {
	query,
	caption,
	headers,
	numberOfRows = 5,
	...props
} ) => {
	const rows = range( numberOfRows ).map( () =>
		headers.map( () => ( {
			display: <span className="is-placeholder" />,
		} ) )
	);
	const tableProps = { query, caption, headers, numberOfRows, ...props };
	return (
		<Table
			ariaHidden={ true }
			className="is-loading"
			rows={ rows }
			{ ...tableProps }
		/>
	);
};

export default TablePlaceholder;
