/**
 * External dependencies
 */
import { CheckboxControl, Button } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { Component, createRef, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { focus } from '@wordpress/dom';
import { withDispatch } from '@wordpress/data';
import { get, isEqual, noop, partial, uniq } from 'lodash';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { CompareButton, Search, TableCard } from '@woocommerce/components';
import DownloadIcon from './download-icon';
import {
	getIdsFromQuery,
	getSearchWords,
	onQueryChange,
	updateQueryString,
} from '@woocommerce/navigation';
import {
	downloadCSVFile,
	generateCSVDataFromTable,
	generateCSVFileName,
} from '@woocommerce/csv-export';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { getReportChartData, getReportTableData } from 'wc-api/reports/utils';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import { extendTableData } from './utils';
import { recordEvent } from 'lib/tracks';
import './style.scss';

const TABLE_FILTER = 'woocommerce_admin_report_table';

/**
 * Component that extends `TableCard` to facilitate its usage in reports.
 */
class ReportTable extends Component {
	constructor( props ) {
		super( props );

		const { query, compareBy } = this.props;
		const selectedRows = query.filter
			? getIdsFromQuery( query[ compareBy ] )
			: [];
		this.state = { selectedRows };

		this.onColumnsChange = this.onColumnsChange.bind( this );
		this.onPageChange = this.onPageChange.bind( this );
		this.onSort = this.onSort.bind( this );
		this.scrollPointRef = createRef();
		this.trackTableSearch = this.trackTableSearch.bind( this );
		this.onClickDownload = this.onClickDownload.bind( this );
		this.onCompare = this.onCompare.bind( this );
		this.onSearchChange = this.onSearchChange.bind( this );
		this.selectRow = this.selectRow.bind( this );
		this.selectAllRows = this.selectAllRows.bind( this );
	}

	componentDidUpdate( { query: prevQuery } ) {
		const { compareBy, query } = this.props;

		if ( query.filter || prevQuery.filter ) {
			const prevIds = prevQuery.filter
				? getIdsFromQuery( prevQuery[ compareBy ] )
				: [];
			const currentIds = query.filter
				? getIdsFromQuery( query[ compareBy ] )
				: [];
			if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
				/* eslint-disable react/no-did-update-set-state */
				this.setState( {
					selectedRows: currentIds,
				} );
				/* eslint-enable react/no-did-update-set-state */
			}
		}
	}

	onColumnsChange( shownColumns, toggledColumn ) {
		const {
			columnPrefsKey,
			endpoint,
			getHeadersContent,
			updateCurrentUserData,
		} = this.props;
		const columns = getHeadersContent().map( ( header ) => header.key );
		const hiddenColumns = columns.filter(
			( column ) => ! shownColumns.includes( column )
		);

		if ( columnPrefsKey ) {
			const userDataFields = {
				[ columnPrefsKey ]: hiddenColumns,
			};
			updateCurrentUserData( userDataFields );
		}

		if ( toggledColumn ) {
			const eventProps = {
				report: endpoint,
				column: toggledColumn,
				status: shownColumns.includes( toggledColumn ) ? 'on' : 'off',
			};

			recordEvent( 'analytics_table_header_toggle', eventProps );
		}
	}

	onPageChange( newPage, source ) {
		const { endpoint } = this.props;
		this.scrollPointRef.current.scrollIntoView();
		const tableElement = this.scrollPointRef.current.nextSibling.querySelector(
			'.woocommerce-table__table'
		);
		const focusableElements = focus.focusable.find( tableElement );

		if ( focusableElements.length ) {
			focusableElements[ 0 ].focus();
		}

		if ( source ) {
			if ( source === 'goto' ) {
				recordEvent( 'analytics_table_go_to_page', {
					report: endpoint,
					page: newPage,
				} );
			} else {
				recordEvent( 'analytics_table_page_click', {
					report: endpoint,
					direction: source,
				} );
			}
		}
	}

	trackTableSearch() {
		const { endpoint } = this.props;

		// @todo: decide if this should only fire for new tokens (not any/all changes).
		recordEvent( 'analytics_table_filter', { report: endpoint } );
	}

	onSort( key, direction ) {
		onQueryChange( 'sort' )( key, direction );

		const { endpoint } = this.props;
		const eventProps = {
			report: endpoint,
			column: key,
			direction,
		};

		recordEvent( 'analytics_table_sort', eventProps );
	}

	filterShownHeaders( headers, hiddenKeys ) {
		// If no user preferences, set visibilty based on column default.
		if ( ! hiddenKeys ) {
			return headers.map( ( header ) => ( {
				...header,
				visible: header.required || ! header.hiddenByDefault,
			} ) );
		}

		// Set visibilty based on user preferences.
		return headers.map( ( header ) => ( {
			...header,
			visible: header.required || ! hiddenKeys.includes( header.key ),
		} ) );
	}

	onClickDownload() {
		const {
			endpoint,
			getHeadersContent,
			getRowsContent,
			initiateReportExport,
			query,
			searchBy,
			tableData,
			title,
		} = this.props;
		const params = Object.assign( {}, query );
		const { items, query: reportQuery } = tableData;
		const { data, totalResults } = items;
		let downloadType = 'browser';

		// Delete unnecessary items from filename.
		delete params.extended_info;
		if ( params.search ) {
			delete params[ searchBy ];
		}

		if ( data && data.length === totalResults ) {
			downloadCSVFile(
				generateCSVFileName( title, params ),
				generateCSVDataFromTable(
					getHeadersContent(),
					getRowsContent( data )
				)
			);
		} else {
			downloadType = 'email';
			initiateReportExport( endpoint, title, reportQuery );
		}

		recordEvent( 'analytics_table_download', {
			report: endpoint,
			rows: totalResults,
			downloadType,
		} );
	}

	onCompare() {
		const { compareBy, compareParam } = this.props;
		const { selectedRows } = this.state;
		if ( compareBy ) {
			onQueryChange( 'compare' )(
				compareBy,
				compareParam,
				selectedRows.join( ',' )
			);
		}
	}

	onSearchChange( values ) {
		const { baseSearchQuery, compareParam, searchBy } = this.props;
		// A comma is used as a separator between search terms, so we want to escape
		// any comma they contain.
		const labels = values.map( ( v ) => v.label.replace( ',', '%2C' ) );
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

		this.trackTableSearch();
	}

	selectAllRows( checked ) {
		const { ids } = this.props;
		this.setState( {
			selectedRows: checked ? ids : [],
		} );
	}

	selectRow( i, checked ) {
		const { ids } = this.props;
		if ( checked ) {
			this.setState( ( { selectedRows } ) => ( {
				selectedRows: uniq( [ ids[ i ], ...selectedRows ] ),
			} ) );
		} else {
			this.setState( ( { selectedRows } ) => {
				const index = selectedRows.indexOf( ids[ i ] );
				return {
					selectedRows: [
						...selectedRows.slice( 0, index ),
						...selectedRows.slice( index + 1 ),
					],
				};
			} );
		}
	}

	getCheckbox( i ) {
		const { ids = [] } = this.props;
		const { selectedRows } = this.state;
		const isChecked = selectedRows.indexOf( ids[ i ] ) !== -1;
		return {
			display: (
				<CheckboxControl
					onChange={ partial( this.selectRow, i ) }
					checked={ isChecked }
				/>
			),
			value: false,
		};
	}

	getAllCheckbox() {
		const { ids = [] } = this.props;
		const { selectedRows } = this.state;
		const hasData = ids.length > 0;
		const isAllChecked = hasData && ids.length === selectedRows.length;
		return {
			cellClassName: 'is-checkbox-column',
			key: 'compare',
			label: (
				<CheckboxControl
					onChange={ this.selectAllRows }
					aria-label={ __( 'Select All' ) }
					checked={ isAllChecked }
					disabled={ ! hasData }
				/>
			),
			required: true,
		};
	}

	render() {
		const { selectedRows } = this.state;
		const {
			getHeadersContent,
			getRowsContent,
			getSummary,
			isRequesting,
			primaryData,
			tableData,
			endpoint,
			// These props are not used in the render function, but are destructured
			// so they are not included in the `tableProps` variable.
			// eslint-disable-next-line no-unused-vars
			itemIdField,
			// eslint-disable-next-line no-unused-vars
			tableQuery,
			userPrefColumns,
			compareBy,
			searchBy,
			labels = {},
			...tableProps
		} = this.props;

		const { items, query } = tableData;

		const isError = tableData.isError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isLoading =
			isRequesting || tableData.isRequesting || primaryData.isRequesting;
		const totals = get( primaryData, [ 'data', 'totals' ], {} );
		const totalResults = items.totalResults;
		const downloadable = totalResults > 0;
		// Search words are in the query string, not the table query.
		const searchWords = getSearchWords( this.props.query );
		const searchedLabels = searchWords.map( ( v ) => ( {
			key: v,
			label: v,
		} ) );

		/**
		 * Filter report table.
		 *
		 * Enables manipulation of data used to create a report table.
		 *
		 * @param {Object} reportTableData - data used to create the table.
		 * @param {string} reportTableData.endpoint - table api endpoint.
		 * @param {Array} reportTableData.headers - table headers data.
		 * @param {Array} reportTableData.rows - table rows data.
		 * @param {Object} reportTableData.totals - total aggregates for request.
		 * @param {Array} reportTableData.summary - summary numbers data.
		 * @param {Object} reportTableData.items - response from api requerst.
		 */
		const filteredTableProps = applyFilters( TABLE_FILTER, {
			endpoint,
			headers: getHeadersContent(),
			rows: getRowsContent( items.data ),
			totals,
			summary: getSummary ? getSummary( totals, totalResults ) : null,
			items,
		} );
		let { headers, rows } = filteredTableProps;
		const { summary } = filteredTableProps;

		// Add in selection for comparisons.
		if ( compareBy ) {
			rows = rows.map( ( row, i ) => {
				return [ this.getCheckbox( i ), ...row ];
			} );
			headers = [ this.getAllCheckbox(), ...headers ];
		}

		// Hide any headers based on user prefs, if loaded.
		const filteredHeaders = this.filterShownHeaders(
			headers,
			userPrefColumns
		);
		const className = classnames( 'woocommerce-report-table', {
			'has-compare': !! compareBy,
			'has-search': !! searchBy,
		} );

		return (
			<Fragment>
				<div
					className="woocommerce-report-table__scroll-point"
					ref={ this.scrollPointRef }
					aria-hidden
				/>
				<TableCard
					className={ className }
					actions={ [
						compareBy && (
							<CompareButton
								key="compare"
								className="woocommerce-table__compare"
								count={ selectedRows.length }
								helpText={
									labels.helpText ||
									__(
										'Check at least two items below to compare',
										'woocommerce-admin'
									)
								}
								onClick={ this.onCompare }
								disabled={ ! downloadable }
							>
								{ labels.compareButton ||
									__( 'Compare', 'woocommerce-admin' ) }
							</CompareButton>
						),
						searchBy && (
							<Search
								allowFreeTextSearch={ true }
								inlineTags
								key="search"
								onChange={ this.onSearchChange }
								placeholder={
									labels.placeholder ||
									__(
										'Search by item name',
										'woocommerce-admin'
									)
								}
								selected={ searchedLabels }
								showClearButton={ true }
								type={ searchBy }
								disabled={ ! downloadable }
							/>
						),
						downloadable && (
							<Button
								key="download"
								className="woocommerce-table__download-button"
								disabled={ isLoading }
								onClick={ this.onClickDownload }
							>
								<DownloadIcon />
								<span className="woocommerce-table__download-button__label">
									{ labels.downloadButton ||
										__( 'Download', 'woocommerce-admin' ) }
								</span>
							</Button>
						),
					] }
					headers={ filteredHeaders }
					isLoading={ isLoading }
					onQueryChange={ onQueryChange }
					onColumnsChange={ this.onColumnsChange }
					onSort={ this.onSort }
					onPageChange={ this.onPageChange }
					rows={ rows }
					rowsPerPage={
						parseInt( query.per_page, 10 ) ||
						QUERY_DEFAULTS.pageSize
					}
					summary={ summary }
					totalRows={ totalResults }
					{ ...tableProps }
				/>
			</Fragment>
		);
	}
}

ReportTable.propTypes = {
	/**
	 * Pass in query parameters to be included in the path when onSearch creates a new url.
	 */
	baseSearchQuery: PropTypes.object,
	/**
	 * The string to use as a query parameter when comparing row items.
	 */
	compareBy: PropTypes.string,
	/**
	 * Url query parameter compare function operates on
	 */
	compareParam: PropTypes.string,
	/**
	 * The key for user preferences settings for column visibility.
	 */
	columnPrefsKey: PropTypes.string,
	/**
	 * The endpoint to use in API calls to populate the table rows and summary.
	 * For example, if `taxes` is provided, data will be fetched from the report
	 * `taxes` endpoint (ie: `/wc-analytics/reports/taxes` and `/wc/v4/reports/taxes/stats`).
	 * If the provided endpoint doesn't exist, an error will be shown to the user
	 * with `ReportError`.
	 */
	endpoint: PropTypes.string,
	/**
	 * Name of the methods available via `select( 'wc-api' )` that will be used to
	 * load more data for table items. If omitted, no call will be made and only
	 * the data returned by the reports endpoint will be used.
	 */
	extendItemsMethodNames: PropTypes.shape( {
		getError: PropTypes.string,
		isRequesting: PropTypes.string,
		load: PropTypes.string,
	} ),
	/**
	 * A function that returns the headers object to build the table.
	 */
	getHeadersContent: PropTypes.func.isRequired,
	/**
	 * A function that returns the rows array to build the table.
	 */
	getRowsContent: PropTypes.func.isRequired,
	/**
	 * A function that returns the summary object to build the table.
	 */
	getSummary: PropTypes.func,
	/**
	 * The name of the property in the item object which contains the id.
	 */
	itemIdField: PropTypes.string,
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
	 * Primary data of that report. If it's not provided, it will be automatically
	 * loaded via the provided `endpoint`.
	 */
	primaryData: PropTypes.object,
	/**
	 * The string to use as a query parameter when searching row items.
	 */
	searchBy: PropTypes.string,
	/**
	 * List of fields used for summary numbers. (Reduces queries)
	 */
	summaryFields: PropTypes.arrayOf( PropTypes.string ),
	/**
	 * Table data of that report. If it's not provided, it will be automatically
	 * loaded via the provided `endpoint`.
	 */
	tableData: PropTypes.object.isRequired,
	/**
	 * Properties to be added to the query sent to the report table endpoint.
	 */
	tableQuery: PropTypes.object,
	/**
	 * String to display as the title of the table.
	 */
	title: PropTypes.string.isRequired,
};

ReportTable.defaultProps = {
	primaryData: {},
	tableData: {
		items: {
			data: [],
			totalResults: 0,
		},
		query: {},
	},
	tableQuery: {},
	compareParam: 'filter',
	downloadable: false,
	onSearch: noop,
	baseSearchQuery: {},
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			endpoint,
			getSummary,
			isRequesting,
			itemIdField,
			query,
			tableData,
			tableQuery,
			columnPrefsKey,
			filters,
			advancedFilters,
			summaryFields,
		} = props;

		let userPrefColumns = [];
		if ( columnPrefsKey ) {
			const { getCurrentUserData } = select( 'wc-api' );
			const userData = getCurrentUserData();

			userPrefColumns =
				userData && userData[ columnPrefsKey ]
					? userData[ columnPrefsKey ]
					: userPrefColumns;
		}

		if (
			isRequesting ||
			( query.search &&
				! ( query[ endpoint ] && query[ endpoint ].length ) )
		) {
			return {
				userPrefColumns,
			};
		}
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		// Variations and Category charts are powered by the /reports/products/stats endpoint.
		const chartEndpoint = [ 'variations', 'categories' ].includes(
			endpoint
		)
			? 'products'
			: endpoint;
		const primaryData = getSummary
			? getReportChartData( {
					endpoint: chartEndpoint,
					dataType: 'primary',
					query,
					select,
					filters,
					advancedFilters,
					tableQuery,
					defaultDateRange,
					fields: summaryFields,
			  } )
			: {};
		const queriedTableData =
			tableData ||
			getReportTableData( {
				endpoint,
				query,
				select,
				tableQuery,
				filters,
				advancedFilters,
				defaultDateRange,
			} );
		const extendedTableData = extendTableData(
			select,
			props,
			queriedTableData
		);

		return {
			primaryData,
			ids: itemIdField
				? extendedTableData.items.data.map(
						( item ) => item[ itemIdField ]
				  )
				: [],
			tableData: extendedTableData,
			query: { ...tableQuery, ...query },
			userPrefColumns,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { initiateReportExport, updateCurrentUserData } = dispatch(
			'wc-api'
		);

		return {
			initiateReportExport,
			updateCurrentUserData,
		};
	} )
)( ReportTable );
