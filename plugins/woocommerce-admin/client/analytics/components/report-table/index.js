/**
 * External dependencies
 */
import { CheckboxControl, Button } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { Fragment, useRef, useState } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { focus } from '@wordpress/dom';
import { withDispatch, withSelect } from '@wordpress/data';
import { get, partial, uniq } from 'lodash';
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import {
	CompareButton,
	AnalyticsError,
	Search,
	TableCard,
} from '@woocommerce/components';
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
import {
	getReportChartData,
	getReportTableData,
	EXPORT_STORE_NAME,
	SETTINGS_STORE_NAME,
	REPORTS_STORE_NAME,
	useUserPreferences,
	QUERY_DEFAULTS,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import DownloadIcon from './download-icon';
import { extendTableData } from './utils';
import './style.scss';

const TABLE_FILTER = 'woocommerce_admin_report_table';

const ReportTable = ( props ) => {
	const {
		getHeadersContent,
		getRowsContent,
		getSummary,
		isRequesting,
		primaryData = {},
		tableData = {
			items: {
				data: [],
				totalResults: 0,
			},
			query: {},
		},
		endpoint,
		// These props are not used in the render function, but are destructured
		// so they are not included in the `tableProps` variable.
		// eslint-disable-next-line no-unused-vars
		itemIdField,
		// eslint-disable-next-line no-unused-vars, @typescript-eslint/no-unused-vars
		tableQuery = {},
		compareBy,
		compareParam = 'filter',
		searchBy,
		labels = {},
		...tableProps
	} = props;

	// Pull these props out separately because they need to be included in tableProps.
	const { query, columnPrefsKey } = props;

	const { items, query: reportQuery } = tableData;

	const initialSelectedRows = query[ compareParam ]
		? getIdsFromQuery( query[ compareBy ] )
		: [];
	const [ selectedRows, setSelectedRows ] = useState( initialSelectedRows );
	const scrollPointRef = useRef( null );

	const { updateUserPreferences, ...userData } = useUserPreferences();

	// Bail early if we've encountered an error.
	const isError = tableData.isError || primaryData.isError;

	if ( isError ) {
		return <AnalyticsError />;
	}

	let userPrefColumns = [];
	if ( columnPrefsKey ) {
		userPrefColumns =
			userData && userData[ columnPrefsKey ]
				? userData[ columnPrefsKey ]
				: userPrefColumns;
	}

	const onPageChange = ( newPage, source ) => {
		scrollPointRef.current.scrollIntoView();
		const tableElement = scrollPointRef.current.nextSibling.querySelector(
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
	};

	const trackTableSearch = () => {
		// @todo: decide if this should only fire for new tokens (not any/all changes).
		recordEvent( 'analytics_table_filter', { report: endpoint } );
	};

	const onSort = ( key, direction ) => {
		onQueryChange( 'sort' )( key, direction );

		const eventProps = {
			report: endpoint,
			column: key,
			direction,
		};

		recordEvent( 'analytics_table_sort', eventProps );
	};

	const filterShownHeaders = ( headers, hiddenKeys ) => {
		// If no user preferences, set visibility based on column default.
		if ( ! hiddenKeys ) {
			return headers.map( ( header ) => ( {
				...header,
				visible: header.required || ! header.hiddenByDefault,
			} ) );
		}

		// Set visibility based on user preferences.
		return headers.map( ( header ) => ( {
			...header,
			visible: header.required || ! hiddenKeys.includes( header.key ),
		} ) );
	};

	const applyTableFilters = ( data, totals, totalResults ) => {
		const summary = getSummary ? getSummary( totals, totalResults ) : null;

		/**
		 * Filter report table for the CSV download.
		 *
		 * Enables manipulation of data used to create the report CSV.
		 *
		 * @filter woocommerce_admin_report_table
		 * @param {Object} reportTableData          - data used to create the table.
		 * @param {string} reportTableData.endpoint - table api endpoint.
		 * @param {Array}  reportTableData.headers  - table headers data.
		 * @param {Array}  reportTableData.rows     - table rows data.
		 * @param {Object} reportTableData.totals   - total aggregates for request.
		 * @param {Array}  reportTableData.summary  - summary numbers data.
		 * @param {Object} reportTableData.items    - response from api requerst.
		 */
		return applyFilters( TABLE_FILTER, {
			endpoint,
			headers: getHeadersContent(),
			rows: getRowsContent( data ),
			totals,
			summary,
			items,
		} );
	};

	const onClickDownload = () => {
		const { createNotice, startExport, title } = props;
		const params = Object.assign( {}, query );
		const { data, totalResults } = items;
		let downloadType = 'browser';

		// Delete unnecessary items from filename.
		delete params.extended_info;
		if ( params.search ) {
			delete params[ searchBy ];
		}

		if ( data && data.length === totalResults ) {
			const { headers, rows } = applyTableFilters( data, totalResults );

			downloadCSVFile(
				generateCSVFileName( title, params ),
				generateCSVDataFromTable( headers, rows )
			);
		} else {
			downloadType = 'email';
			startExport( endpoint, reportQuery )
				.then( () =>
					createNotice(
						'success',
						sprintf(
							/* translators: %s = type of report */
							__(
								'Your %s Report will be emailed to you.',
								'woocommerce'
							),
							title
						)
					)
				)
				.catch( ( error ) =>
					createNotice(
						'error',
						error.message ||
							sprintf(
								/* translators: %s = type of report */
								__(
									'There was a problem exporting your %s Report. Please try again.',
									'woocommerce'
								),
								title
							)
					)
				);
		}

		recordEvent( 'analytics_table_download', {
			report: endpoint,
			rows: totalResults,
			download_type: downloadType,
		} );
	};

	const onCompare = () => {
		if ( compareBy ) {
			onQueryChange( 'compare' )(
				compareBy,
				compareParam,
				selectedRows.join( ',' )
			);
		}
	};

	const onSearchChange = ( values ) => {
		const { baseSearchQuery = {}, addCesSurveyForCustomerSearch } = props;
		// A comma is used as a separator between search terms, so we want to escape
		// any comma they contain.
		const searchTerms = values.map( ( v ) =>
			v.label.replace( ',', '%2C' )
		);
		if ( searchTerms.length ) {
			updateQueryString( {
				filter: undefined,
				[ compareParam ]: undefined,
				[ searchBy ]: undefined,
				...baseSearchQuery,
				search: uniq( searchTerms ).join( ',' ),
			} );

			// Prompt survey if user is searching for something.
			addCesSurveyForCustomerSearch();
		} else {
			updateQueryString( {
				search: undefined,
			} );
		}

		trackTableSearch();
	};

	const selectAllRows = ( checked ) => {
		const { ids } = props;
		setSelectedRows( checked ? ids : [] );
	};

	const selectRow = ( i, checked ) => {
		const { ids } = props;
		if ( checked ) {
			setSelectedRows( uniq( [ ids[ i ], ...selectedRows ] ) );
		} else {
			const index = selectedRows.indexOf( ids[ i ] );
			setSelectedRows( [
				...selectedRows.slice( 0, index ),
				...selectedRows.slice( index + 1 ),
			] );
		}
	};

	const getCheckbox = ( i ) => {
		const { ids = [] } = props;
		const isChecked = selectedRows.indexOf( ids[ i ] ) !== -1;
		return {
			display: (
				<CheckboxControl
					onChange={ partial( selectRow, i ) }
					checked={ isChecked }
				/>
			),
			value: false,
		};
	};

	const getAllCheckbox = () => {
		const { ids = [] } = props;
		const hasData = ids.length > 0;
		const isAllChecked = hasData && ids.length === selectedRows.length;
		return {
			cellClassName: 'is-checkbox-column',
			key: 'compare',
			label: (
				<CheckboxControl
					onChange={ selectAllRows }
					aria-label={ __( 'Select All', 'woocommerce' ) }
					checked={ isAllChecked }
					disabled={ ! hasData }
				/>
			),
			required: true,
		};
	};

	const isLoading =
		isRequesting || tableData.isRequesting || primaryData.isRequesting;
	const totals = get( primaryData, [ 'data', 'totals' ], {} );
	const totalResults = items.totalResults || 0;
	const downloadable = totalResults > 0;
	// Search words are in the query string, not the table query.
	const searchWords = getSearchWords( query );
	const searchedLabels = searchWords.map( ( v ) => ( {
		key: v,
		label: v,
	} ) );

	const { data } = items;
	const applyTableFiltersResult = applyTableFilters(
		data,
		totals,
		totalResults
	);
	let { headers, rows } = applyTableFiltersResult;
	const { summary } = applyTableFiltersResult;

	const onColumnsChange = ( shownColumns, toggledColumn ) => {
		const columns = headers.map( ( header ) => header.key );
		const hiddenColumns = columns.filter(
			( column ) => ! shownColumns.includes( column )
		);
		if ( columnPrefsKey ) {
			const userDataFields = {
				[ columnPrefsKey ]: hiddenColumns,
			};
			updateUserPreferences( userDataFields );
		}

		if ( toggledColumn ) {
			const eventProps = {
				report: endpoint,
				column: toggledColumn,
				status: shownColumns.includes( toggledColumn ) ? 'on' : 'off',
			};

			recordEvent( 'analytics_table_header_toggle', eventProps );
		}
	};

	// Add in selection for comparisons.
	if ( compareBy ) {
		rows = rows.map( ( row, i ) => {
			return [ getCheckbox( i ), ...row ];
		} );
		headers = [ getAllCheckbox(), ...headers ];
	}

	// Hide any headers based on user prefs, if loaded.
	const filteredHeaders = filterShownHeaders( headers, userPrefColumns );

	return (
		<Fragment>
			<div
				className="woocommerce-report-table__scroll-point"
				ref={ scrollPointRef }
				aria-hidden
			/>
			<TableCard
				className={ 'woocommerce-report-table' }
				hasSearch={ !! searchBy }
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
									'woocommerce'
								)
							}
							onClick={ onCompare }
							disabled={ ! downloadable }
						>
							{ labels.compareButton ||
								__( 'Compare', 'woocommerce' ) }
						</CompareButton>
					),
					searchBy && (
						<Search
							allowFreeTextSearch={ true }
							inlineTags
							key="search"
							onChange={ onSearchChange }
							placeholder={
								labels.placeholder ||
								__( 'Search by item name', 'woocommerce' )
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
							onClick={ onClickDownload }
						>
							<DownloadIcon />
							<span className="woocommerce-table__download-button__label">
								{ labels.downloadButton ||
									__( 'Download', 'woocommerce' ) }
							</span>
						</Button>
					),
				] }
				headers={ filteredHeaders }
				isLoading={ isLoading }
				onQueryChange={ onQueryChange }
				onColumnsChange={ onColumnsChange }
				onSort={ onSort }
				onPageChange={ onPageChange }
				rows={ rows }
				rowsPerPage={
					parseInt( reportQuery.per_page, 10 ) ||
					QUERY_DEFAULTS.pageSize
				}
				summary={ summary }
				totalRows={ totalResults }
				{ ...tableProps }
			/>
		</Fragment>
	);
};

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
	 * with `AnalyticsError`.
	 */
	endpoint: PropTypes.string,
	/**
	 * Name of the methods available via `select` that will be used to
	 * load more data for table items. If omitted, no call will be made and only
	 * the data returned by the reports endpoint will be used.
	 */
	extendItemsMethodNames: PropTypes.shape( {
		getError: PropTypes.string,
		isRequesting: PropTypes.string,
		load: PropTypes.string,
	} ),
	/**
	 * Name of store on which extendItemsMethodNames can be found.
	 */
	extendedItemsStoreName: PropTypes.string,
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
	tableData: PropTypes.object,
	/**
	 * Properties to be added to the query sent to the report table endpoint.
	 */
	tableQuery: PropTypes.object,
	/**
	 * String to display as the title of the table.
	 */
	title: PropTypes.string.isRequired,
};

const EMPTY_ARRAY = [];
const EMPTY_OBJECT = {};

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
			filters,
			advancedFilters,
			summaryFields,
			extendedItemsStoreName,
		} = props;

		/* eslint @wordpress/no-unused-vars-before-return: "off" */
		const reportStoreSelector = select( REPORTS_STORE_NAME );

		const extendedStoreSelector = extendedItemsStoreName
			? select( extendedItemsStoreName )
			: null;

		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		const noSearchResultsFound =
			query.search && ! ( query[ endpoint ] && query[ endpoint ].length );
		if ( isRequesting || noSearchResultsFound ) {
			return EMPTY_OBJECT;
		}

		// Category charts are powered by the /reports/products/stats endpoint.
		const chartEndpoint = endpoint === 'categories' ? 'products' : endpoint;
		const primaryData = getSummary
			? getReportChartData( {
					endpoint: chartEndpoint,
					selector: reportStoreSelector,
					dataType: 'primary',
					query,
					filters,
					advancedFilters,
					defaultDateRange,
					fields: summaryFields,
			  } )
			: EMPTY_OBJECT;
		const queriedTableData =
			tableData ||
			getReportTableData( {
				endpoint,
				query,
				selector: reportStoreSelector,
				tableQuery,
				filters,
				advancedFilters,
				defaultDateRange,
			} );

		const extendedTableData = extendedStoreSelector
			? extendTableData( extendedStoreSelector, props, queriedTableData )
			: queriedTableData;

		return {
			primaryData,
			ids:
				itemIdField && extendedTableData.items.data
					? extendedTableData.items.data.map(
							( item ) => item[ itemIdField ]
					  )
					: EMPTY_ARRAY,
			tableData: extendedTableData,
			query,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { startExport } = dispatch( EXPORT_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );
		const { addCesSurveyForCustomerSearch } = dispatch( CES_STORE_KEY );

		return {
			createNotice,
			startExport,
			addCesSurveyForCustomerSearch,
		};
	} )
)( ReportTable );
