/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { fill, find, findIndex, first, isEqual, noop, partial, uniq } from 'lodash';
import { IconButton, ToggleControl } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import Card from 'components/card';
import CompareButton from 'components/filters/compare/button';
import DowloadIcon from './download-icon';
import EllipsisMenu from 'components/ellipsis-menu';
import { getIdsFromQuery } from 'lib/nav-utils';
import MenuItem from 'components/ellipsis-menu/menu-item';
import MenuTitle from 'components/ellipsis-menu/menu-title';
import Pagination from 'components/pagination';
import Search from 'components/search';
import Table from './table';
import TableSummary from './summary';

/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
class TableCard extends Component {
	constructor( props ) {
		super( props );
		const { compareBy, query } = props;
		this.state = {
			showCols: fill( Array( props.headers.length ), true ),
			selectedRows: getIdsFromQuery( query[ compareBy ] ),
		};
		this.toggleCols = this.toggleCols.bind( this );
		this.onCompare = this.onCompare.bind( this );
		this.onSearch = this.onSearch.bind( this );
		this.selectRow = this.selectRow.bind( this );
		this.selectAllRows = this.selectAllRows.bind( this );
	}

	componentDidUpdate( { query: prevQuery } ) {
		const { compareBy, query } = this.props;
		const prevIds = getIdsFromQuery( prevQuery[ compareBy ] );
		const currentIds = getIdsFromQuery( query[ compareBy ] );
		if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { selectedRows: currentIds } );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	toggleCols( selected ) {
		const { headers, query, onQueryChange } = this.props;
		return () => {
			// Handle hiding a sorted column
			if ( query.orderby ) {
				const sortBy = findIndex( headers, { key: query.orderby } );
				if ( sortBy === selected ) {
					const defaultSort = find( headers, { defaultSort: true } ) || first( headers ) || {};
					onQueryChange( 'sort' )( defaultSort.key, 'desc' );
				}
			}

			this.setState( prevState => ( {
				showCols: prevState.showCols.map(
					( toggled, i ) => ( selected === i ? ! toggled : toggled )
				),
			} ) );
		};
	}

	onCompare() {
		const { compareBy, onQueryChange } = this.props;
		const { selectedRows } = this.state;
		if ( compareBy ) {
			onQueryChange( 'compare' )( compareBy, selectedRows.join( ',' ) );
		}
	}

	onSearch( value ) {
		const { compareBy, onQueryChange } = this.props;
		const { selectedRows } = this.state;
		if ( compareBy ) {
			const ids = value.map( v => v.id );
			onQueryChange( 'compare' )( compareBy, [ ...selectedRows, ...ids ].join( ',' ) );
		}
	}

	filterCols( rows = [] ) {
		const { showCols } = this.state;
		// Header is a 1d array
		if ( ! Array.isArray( first( rows ) ) ) {
			return rows.filter( ( col, i ) => showCols[ i ] );
		}
		// Rows is a 2d array
		return rows.map( row => row.filter( ( col, i ) => showCols[ i ] ) );
	}

	selectAllRows( event ) {
		const { ids } = this.props;
		if ( event.target.checked ) {
			this.setState( {
				selectedRows: ids,
			} );
		} else {
			this.setState( {
				selectedRows: [],
			} );
		}
	}

	selectRow( i, event ) {
		const { ids } = this.props;
		if ( event.target.checked ) {
			this.setState( ( { selectedRows } ) => ( {
				selectedRows: uniq( [ ids[ i ], ...selectedRows ] ),
			} ) );
		} else {
			this.setState( ( { selectedRows } ) => {
				const index = selectedRows.indexOf( ids[ i ] );
				return {
					selectedRows: [ ...selectedRows.slice( 0, index ), ...selectedRows.slice( index + 1 ) ],
				};
			} );
		}
	}

	getCheckbox( i ) {
		const { ids = [] } = this.props;
		const { selectedRows } = this.state;
		const isChecked = -1 !== selectedRows.indexOf( ids[ i ] );
		return {
			display: (
				<input type="checkbox" onChange={ partial( this.selectRow, i ) } checked={ isChecked } />
			),
			value: false,
		};
	}

	getAllCheckbox() {
		const { ids = [] } = this.props;
		const { selectedRows } = this.state;
		const isAllChecked = ids.length === selectedRows.length;
		return {
			label: (
				<input
					type="checkbox"
					onChange={ this.selectAllRows }
					aria-label={ __( 'Select All' ) }
					checked={ isAllChecked }
				/>
			),
			required: true,
		};
	}

	render() {
		const {
			compareBy,
			labels = {},
			onClickDownload,
			onQueryChange,
			query,
			rowHeader,
			rowsPerPage,
			summary,
			title,
			totalRows,
		} = this.props;
		const { selectedRows, showCols } = this.state;
		const allHeaders = this.props.headers;
		let headers = this.filterCols( this.props.headers );
		let rows = this.filterCols( this.props.rows );
		if ( compareBy ) {
			rows = rows.map( ( row, i ) => {
				return [ this.getCheckbox( i ), ...row ];
			} );
			headers = [ this.getAllCheckbox(), ...headers ];
		}

		const className = classnames( {
			'woocommerce-table': true,
			'has-compare': !! compareBy,
		} );

		return (
			<Card
				className={ className }
				title={ title }
				action={ [
					compareBy && (
						<CompareButton
							key="compare"
							count={ selectedRows.length }
							helpText={
								labels.helpText || __( 'Select at least two items to compare', 'wc-admin' )
							}
							onClick={ this.onCompare }
						>
							{ labels.compareButton || __( 'Compare', 'wc-admin' ) }
						</CompareButton>
					),
					compareBy && (
						<Search
							key="search"
							placeholder={ labels.placeholder || __( 'Search by item name', 'wc-admin' ) }
							type={ compareBy + 's' }
							onChange={ this.onSearch }
						/>
					),
					onClickDownload && (
						<IconButton
							key="download"
							className="woocommerce-table__download-button"
							onClick={ onClickDownload }
							isLink
						>
							<DowloadIcon />
							{ labels.downloadButton || __( 'Download', 'wc-admin' ) }
						</IconButton>
					),
				] }
				menu={
					<EllipsisMenu label={ __( 'Choose which values to display', 'wc-admin' ) }>
						<MenuTitle>{ __( 'Columns:', 'wc-admin' ) }</MenuTitle>
						{ allHeaders.map( ( { label, required }, i ) => {
							if ( required ) {
								return null;
							}
							return (
								<MenuItem key={ i } onInvoke={ this.toggleCols( i ) }>
									<ToggleControl
										label={ label }
										checked={ !! showCols[ i ] }
										onChange={ this.toggleCols( i ) }
									/>
								</MenuItem>
							);
						} ) }
					</EllipsisMenu>
				}
			>
				<Table
					rows={ rows }
					headers={ headers }
					rowHeader={ rowHeader }
					caption={ title }
					query={ query }
					onSort={ onQueryChange( 'sort' ) }
				/>

				{ summary && <TableSummary data={ summary } /> }

				<Pagination
					page={ parseInt( query.page ) || 1 }
					perPage={ rowsPerPage }
					total={ totalRows }
					onPageChange={ onQueryChange( 'page' ) }
					onPerPageChange={ onQueryChange( 'per_page' ) }
				/>
			</Card>
		);
	}
}

TableCard.propTypes = {
	/**
	 * The string to use as a query parameter when comparing row items.
	 */
	compareBy: PropTypes.string,
	/**
	 * An array of column headers (see `Table` props).
	 */
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.string,
			required: PropTypes.bool,
		} )
	),
	/**
	 * Custom labels for table header actions.
	 */
	labels: PropTypes.shape( {
		compareButton: PropTypes.string,
		downloadButton: PropTypes.string,
		helpText: PropTypes.string,
		placeholder: PropTypes.string,
	} ),
	/**
	 * A list of IDs, matching to the row list so that ids[ 0 ] contains the object ID for the object displayed in row[ 0 ].
	 */
	ids: PropTypes.arrayOf( PropTypes.number ),
	/**
	 * A function which returns a callback function to update the query string for a given `param`.
	 */
	onQueryChange: PropTypes.func,
	/**
	 * A callback function which handles then "download" button press. Optional, if not used, the button won't appear.
	 */
	onClickDownload: PropTypes.func,
	/**
	 *  An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.
	 */
	query: PropTypes.object,
	/**
	 * An array of arrays of display/value object pairs (see `Table` props).
	 */
	rowHeader: PropTypes.oneOfType( [ PropTypes.number, PropTypes.bool ] ),
	/**
	 * Which column should be the row header, defaults to the first item (`0`) (see `Table` props).
	 */
	rows: PropTypes.arrayOf(
		PropTypes.arrayOf(
			PropTypes.shape( {
				display: PropTypes.node,
				value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number, PropTypes.bool ] ),
			} )
		)
	).isRequired,
	/**
	 * The total number of rows to display per page.
	 */
	rowsPerPage: PropTypes.number.isRequired,
	/**
	 * An array of objects with `label` & `value` properties, which display in a line under the table.
	 * Optional, can be left off to show no summary.
	 */
	summary: PropTypes.arrayOf(
		PropTypes.shape( {
			label: PropTypes.node,
			value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ),
		} )
	),
	/**
	 * The title used in the card header, also used as the caption for the content in this table.
	 */
	title: PropTypes.string.isRequired,
	/**
	 * The total number of rows (across all pages).
	 */
	totalRows: PropTypes.number.isRequired,
};

TableCard.defaultProps = {
	onQueryChange: noop,
	query: {},
	rowHeader: 0,
	rows: [],
};

export default TableCard;
