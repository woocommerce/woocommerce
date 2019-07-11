/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { find, first, isEqual, noop, partial, uniq, without } from 'lodash';
import { IconButton } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import {
	downloadCSVFile,
	generateCSVDataFromTable,
	generateCSVFileName,
} from '@woocommerce/csv-export';
import { getIdsFromQuery, getSearchWords, updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Card from '../card';
import CompareButton from '../filters/compare/button';
import DownloadIcon from './download-icon';
import EllipsisMenu from '../ellipsis-menu';
import MenuItem from '../ellipsis-menu/menu-item';
import MenuTitle from '../ellipsis-menu/menu-title';
import Pagination from '../pagination';
import Search from '../search';
import Table from './table';
import TablePlaceholder from './placeholder';
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
		const { query, compareBy } = this.props;
		const showCols = this.getShowCols( props.headers );
		const selectedRows = query.filter ? getIdsFromQuery( query[ compareBy ] ) : [];

		this.state = { showCols, selectedRows };
		this.onColumnToggle = this.onColumnToggle.bind( this );
		this.onClickDownload = this.onClickDownload.bind( this );
		this.onCompare = this.onCompare.bind( this );
		this.onPageChange = this.onPageChange.bind( this );
		this.onSearch = this.onSearch.bind( this );
		this.selectRow = this.selectRow.bind( this );
		this.selectAllRows = this.selectAllRows.bind( this );
	}

	componentDidUpdate( { headers: prevHeaders, query: prevQuery } ) {
		const { compareBy, headers, onColumnsChange, query } = this.props;
		const { showCols } = this.state;

		if ( query.filter || prevQuery.filter ) {
			const prevIds = prevQuery.filter ? getIdsFromQuery( prevQuery[ compareBy ] ) : [];
			const currentIds = query.filter ? getIdsFromQuery( query[ compareBy ] ) : [];
			if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
				/* eslint-disable react/no-did-update-set-state */
				this.setState( {
					selectedRows: currentIds,
				} );
				/* eslint-enable react/no-did-update-set-state */
			}
		}
		if ( ! isEqual( headers, prevHeaders ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				showCols: this.getShowCols( headers ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
		if ( query.orderby !== prevQuery.orderby && ! showCols.includes( query.orderby ) ) {
			const newShowCols = showCols.concat( query.orderby );
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				showCols: newShowCols,
			} );
			/* eslint-enable react/no-did-update-set-state */
			onColumnsChange( newShowCols );
		}
	}

	getShowCols( headers ) {
		return headers.map( ( { key, hiddenByDefault } ) => ! hiddenByDefault && key ).filter( Boolean );
	}

	getVisibleHeaders() {
		const { headers } = this.props;
		const { showCols } = this.state;
		return headers.filter( ( { key } ) => showCols.includes( key ) );
	}

	getVisibleRows() {
		const { headers, rows } = this.props;
		const { showCols } = this.state;

		return rows.map( row => {
			return headers.map( ( { key }, i ) => {
				return showCols.includes( key ) && row[ i ];
			} ).filter( Boolean );
		} );
	}

	onColumnToggle( key ) {
		const { headers, query, onQueryChange, onColumnsChange } = this.props;

		return () => {
			this.setState( prevState => {
				const hasKey = prevState.showCols.includes( key );

				if ( hasKey ) {
					// Handle hiding a sorted column
					if ( query.orderby === key ) {
						const defaultSort = find( headers, { defaultSort: true } ) || first( headers ) || {};
						onQueryChange( 'sort' )( defaultSort.key, 'desc' );
					}

					const showCols = without( prevState.showCols, key );
					onColumnsChange( showCols );
					return { showCols };
				}

				const showCols = [ ...prevState.showCols, key ];
				onColumnsChange( showCols );
				return { showCols };
			} );
		};
	}

	onClickDownload() {
		const { query, onClickDownload, searchBy, title } = this.props;
		const params = Object.assign( {}, query );

		// Delete unnecessary items from filename.
		delete params.extended_info;
		if ( params.search ) {
			delete params[ searchBy ];
		}

		// @todo The current implementation only downloads the contents displayed in the table.
		// Another solution is required when the data set is larger (see #311).
		downloadCSVFile(
			generateCSVFileName( title, params ),
			generateCSVDataFromTable( this.getVisibleHeaders(), this.getVisibleRows() )
		);

		if ( onClickDownload ) {
			onClickDownload();
		}
	}

	onCompare() {
		const { compareBy, compareParam, onQueryChange } = this.props;
		const { selectedRows } = this.state;
		if ( compareBy ) {
			onQueryChange( 'compare' )( compareBy, compareParam, selectedRows.join( ',' ) );
		}
	}

	onPageChange( ...params ) {
		const { onPageChange, onQueryChange } = this.props;
		if ( onPageChange ) {
			onPageChange( ...params );
		}
		if ( onQueryChange ) {
			onQueryChange( 'paged' )( ...params );
		}
	}

	onSearch( values ) {
		const { compareParam, searchBy, baseSearchQuery } = this.props;
		// A comma is used as a separator between search terms, so we want to escape
		// any comma they contain.
		const labels = values.map( v => v.label.replace( ',', '%2C' ) );
		if ( labels.length ) {
			updateQueryString( {
				filter: undefined,
				[ compareParam ]: undefined,
				[ searchBy ]: undefined,
				...baseSearchQuery,
				search: uniq( labels ).join( ',' ),
			} );
		} else {
			updateQueryString( {
				search: undefined,
			} );
		}
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
		const isAllChecked = ids.length > 0 && ids.length === selectedRows.length;
		return {
			cellClassName: 'is-checkbox-column',
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
			downloadable,
			labels = {},
			isLoading,
			onClickDownload,
			onQueryChange,
			query,
			rowHeader,
			rowsPerPage,
			searchBy,
			showMenu,
			summary,
			title,
			totalRows,
		} = this.props;
		const { selectedRows, showCols } = this.state;
		const searchWords = getSearchWords( query );
		const searchedLabels = searchWords.map( v => ( { id: v, label: v } ) );
		const allHeaders = this.props.headers;
		let headers = this.getVisibleHeaders();
		let rows = this.getVisibleRows();
		if ( compareBy ) {
			rows = rows.map( ( row, i ) => {
				return [ this.getCheckbox( i ), ...row ];
			} );
			headers = [ this.getAllCheckbox(), ...headers ];
		}

		const className = classnames( 'woocommerce-analytics__card', {
			'woocommerce-table': true,
			'has-compare': !! compareBy,
			'has-search': !! searchBy,
		} );

		return (
			<Card
				className={ className }
				title={ title }
				action={ [
					compareBy && (
						<CompareButton
							key="compare"
							className="woocommerce-table__compare"
							count={ selectedRows.length }
							helpText={
								labels.helpText || __( 'Check at least two items below to compare', 'woocommerce-admin' )
							}
							onClick={ this.onCompare }
						>
							{ labels.compareButton || __( 'Compare', 'woocommerce-admin' ) }
						</CompareButton>
					),
					searchBy && (
						<Search
							allowFreeTextSearch={ true }
							inlineTags
							key="search"
							onChange={ this.onSearch }
							placeholder={ labels.placeholder || __( 'Search by item name', 'woocommerce-admin' ) }
							selected={ searchedLabels }
							showClearButton={ true }
							type={ searchBy }
						/>
					),
					( downloadable || onClickDownload ) && (
						<IconButton
							key="download"
							className="woocommerce-table__download-button"
							disabled={ isLoading }
							onClick={ this.onClickDownload }
							isLink
						>
							<DownloadIcon />
							<span className="woocommerce-table__download-button__label">
								{ labels.downloadButton || __( 'Download', 'woocommerce-admin' ) }
							</span>
						</IconButton>
					),
				] }
				menu={
					showMenu && <EllipsisMenu label={ __( 'Choose which values to display', 'woocommerce-admin' ) }
						renderContent={ () => (
							<Fragment>
								<MenuTitle>{ __( 'Columns:', 'woocommerce-admin' ) }</MenuTitle>
								{ allHeaders.map( ( { key, label, required } ) => {
									if ( required ) {
										return null;
									}
									return (
										<MenuItem
											checked={ showCols.includes( key ) }
											isCheckbox
											isClickable
											key={ key }
											onInvoke={ this.onColumnToggle( key ) }
										>
											{ label }
										</MenuItem>
									);
								} ) }
							</Fragment>
						) }
					/>
				}
			>
				{ isLoading ? (
					<Fragment>
						<span className="screen-reader-text">
							{ __( 'Your requested data is loading', 'woocommerce-admin' ) }
						</span>
						<TablePlaceholder
							numberOfRows={ rowsPerPage }
							headers={ headers }
							rowHeader={ rowHeader }
							caption={ title }
							query={ query }
							onSort={ onQueryChange( 'sort' ) }
						/>
					</Fragment>
				) : (
					<Table
						rows={ rows }
						headers={ headers }
						rowHeader={ rowHeader }
						caption={ title }
						query={ query }
						onSort={ onQueryChange( 'sort' ) }
					/>
				) }

				<Pagination
					key={ parseInt( query.paged ) || 1 }
					page={ parseInt( query.paged ) || 1 }
					perPage={ rowsPerPage }
					total={ totalRows }
					onPageChange={ this.onPageChange }
					onPerPageChange={ onQueryChange( 'per_page' ) }
				/>

				{ summary && <TableSummary data={ summary } /> }
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
	 * Url query parameter compare function operates on
	 */
	compareParam: PropTypes.string,
	/**
	 * An array of column headers (see `Table` props).
	 */
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			hiddenByDefault: PropTypes.bool,
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
	 * Defines if the table contents are loading.
	 * It will display `TablePlaceholder` component instead of `Table` if that's the case.
	 */
	isLoading: PropTypes.bool,
	/**
	 * A function which returns a callback function to update the query string for a given `param`.
	 */
	onQueryChange: PropTypes.func,
	/**
	 * A function which returns a callback function which is called upon the user changing the visiblity of columns.
	 */
	onColumnsChange: PropTypes.func,
	/**
	 * Whether the table must be downloadable. If true, the download button will appear.
	 */
	downloadable: PropTypes.bool,
	/**
	 * A callback function called when the "download" button is pressed. Optional, if used, the download button will appear.
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
	 * The string to use as a query parameter when searching row items.
	 */
	searchBy: PropTypes.string,
	/**
	 * Boolean to determine whether or not ellipsis menu is shown.
	 */
	showMenu: PropTypes.bool,
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
	/**
	 * Pass in query parameters to be included in the path when onSearch creates a new url.
	 */
	baseSearchQuery: PropTypes.object,
};

TableCard.defaultProps = {
	compareParam: 'filter',
	downloadable: false,
	isLoading: false,
	onQueryChange: noop,
	onColumnsChange: noop,
	query: {},
	rowHeader: 0,
	rows: [],
	showMenu: true,
	baseSearchQuery: {},
};

export default TableCard;
