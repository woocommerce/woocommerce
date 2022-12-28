/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	__experimentalText as Text,
} from '@wordpress/components';
import { createElement, Component, Fragment } from '@wordpress/element';
import { find, first, isEqual, without } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import EllipsisMenu from '../ellipsis-menu';
import MenuItem from '../ellipsis-menu/menu-item';
import MenuTitle from '../ellipsis-menu/menu-title';
import Pagination from '../pagination';
import Table from './table';
import TablePlaceholder from './placeholder';
import TableSummary, { TableSummaryPlaceholder } from './summary';

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
		const showCols = this.getShowCols( props.headers );

		this.state = { showCols };
		this.onColumnToggle = this.onColumnToggle.bind( this );
		this.onPageChange = this.onPageChange.bind( this );
	}

	componentDidUpdate( { headers: prevHeaders, query: prevQuery } ) {
		const { headers, onColumnsChange, query } = this.props;
		const { showCols } = this.state;

		if ( ! isEqual( headers, prevHeaders ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				showCols: this.getShowCols( headers ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
		if (
			query.orderby !== prevQuery.orderby &&
			! showCols.includes( query.orderby )
		) {
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
		return headers
			.map( ( { key, visible } ) => {
				if ( typeof visible === 'undefined' || visible ) {
					return key;
				}
				return false;
			} )
			.filter( Boolean );
	}

	getVisibleHeaders() {
		const { headers } = this.props;
		const { showCols } = this.state;
		return headers.filter( ( { key } ) => showCols.includes( key ) );
	}

	getVisibleRows() {
		const { headers, rows } = this.props;
		const { showCols } = this.state;

		return rows.map( ( row ) => {
			return headers
				.map( ( { key }, i ) => {
					return showCols.includes( key ) && row[ i ];
				} )
				.filter( Boolean );
		} );
	}

	onColumnToggle( key ) {
		const { headers, query, onQueryChange, onColumnsChange } = this.props;

		return () => {
			this.setState( ( prevState ) => {
				const hasKey = prevState.showCols.includes( key );

				if ( hasKey ) {
					// Handle hiding a sorted column
					if ( query.orderby === key ) {
						const defaultSort =
							find( headers, { defaultSort: true } ) ||
							first( headers ) ||
							{};
						onQueryChange( 'sort' )( defaultSort.key, 'desc' );
					}

					const showCols = without( prevState.showCols, key );
					onColumnsChange( showCols, key );
					return { showCols };
				}

				const showCols = [ ...prevState.showCols, key ];
				onColumnsChange( showCols, key );
				return { showCols };
			} );
		};
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

	render() {
		const {
			actions,
			className,
			hasSearch,
			isLoading,
			onQueryChange,
			onSort,
			query,
			rowHeader,
			rowsPerPage,
			showMenu,
			summary,
			title,
			totalRows,
			rowKey,
			emptyMessage,
		} = this.props;
		const { showCols } = this.state;
		const allHeaders = this.props.headers;
		const headers = this.getVisibleHeaders();
		const rows = this.getVisibleRows();
		const classes = classnames( 'woocommerce-table', className, {
			'has-actions': !! actions,
			'has-menu': showMenu,
			'has-search': hasSearch,
		} );

		return (
			<Card className={ classes }>
				<CardHeader>
					<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
						{ title }
					</Text>
					<div className="woocommerce-table__actions">
						{ actions }
					</div>
					{ showMenu && (
						<EllipsisMenu
							label={ __(
								'Choose which values to display',
								'woocommerce'
							) }
							renderContent={ () => (
								<Fragment>
									<MenuTitle>
										{ __( 'Columns:', 'woocommerce' ) }
									</MenuTitle>
									{ allHeaders.map(
										( { key, label, required } ) => {
											if ( required ) {
												return null;
											}
											return (
												<MenuItem
													checked={ showCols.includes(
														key
													) }
													isCheckbox
													isClickable
													key={ key }
													onInvoke={ this.onColumnToggle(
														key
													) }
												>
													{ label }
												</MenuItem>
											);
										}
									) }
								</Fragment>
							) }
						/>
					) }
				</CardHeader>
				<CardBody size={ null }>
					{ isLoading ? (
						<Fragment>
							<span className="screen-reader-text">
								{ __(
									'Your requested data is loading',
									'woocommerce'
								) }
							</span>
							<TablePlaceholder
								numberOfRows={ rowsPerPage }
								headers={ headers }
								rowHeader={ rowHeader }
								caption={ title }
								query={ query }
							/>
						</Fragment>
					) : (
						<Table
							rows={ rows }
							headers={ headers }
							rowHeader={ rowHeader }
							caption={ title }
							query={ query }
							onSort={ onSort || onQueryChange( 'sort' ) }
							rowKey={ rowKey }
							emptyMessage={ emptyMessage }
						/>
					) }
				</CardBody>

				<CardFooter justify="center">
					{ isLoading ? (
						<TableSummaryPlaceholder />
					) : (
						<Fragment>
							<Pagination
								key={ parseInt( query.paged, 10 ) || 1 }
								page={ parseInt( query.paged, 10 ) || 1 }
								perPage={ rowsPerPage }
								total={ totalRows }
								onPageChange={ this.onPageChange }
								onPerPageChange={ onQueryChange( 'per_page' ) }
							/>

							{ summary && <TableSummary data={ summary } /> }
						</Fragment>
					) }
				</CardFooter>
			</Card>
		);
	}
}

TableCard.propTypes = {
	/**
	 * If a search is provided in actions and should reorder actions on mobile.
	 */
	hasSearch: PropTypes.bool,
	/**
	 * An array of column headers (see `Table` props).
	 */
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			hiddenByDefault: PropTypes.bool,
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
			required: PropTypes.bool,
		} )
	),
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
	 * A function which is called upon the user changing the sorting of the table.
	 */
	onSort: PropTypes.func,
	/**
	 *  An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.
	 */
	query: PropTypes.object,
	/**
	 * Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col
	 * is checkboxes, for example). Set to false to disable row headers.
	 */
	rowHeader: PropTypes.oneOfType( [ PropTypes.number, PropTypes.bool ] ),
	/**
	 * An array of arrays of display/value object pairs (see `Table` props).
	 */
	rows: PropTypes.arrayOf(
		PropTypes.arrayOf(
			PropTypes.shape( {
				display: PropTypes.node,
				value: PropTypes.oneOfType( [
					PropTypes.string,
					PropTypes.number,
					PropTypes.bool,
				] ),
			} )
		)
	).isRequired,
	/**
	 * The total number of rows to display per page.
	 */
	rowsPerPage: PropTypes.number.isRequired,
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
			value: PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.number,
			] ),
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
	 * The rowKey used for the key value on each row, this can be a string of the key or a function that returns the value.
	 * This uses the index if not defined.
	 */
	rowKey: PropTypes.func,
	/**
	 * Customize the message to show when there are no rows in the table.
	 */
	emptyMessage: PropTypes.string,
};

TableCard.defaultProps = {
	isLoading: false,
	onQueryChange: () => () => {},
	onColumnsChange: () => {},
	onSort: undefined,
	query: {},
	rowHeader: 0,
	rows: [],
	showMenu: true,
	emptyMessage: undefined,
};

export default TableCard;
