/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { IconButton, ToggleControl } from '@wordpress/components';
import { fill, find, findIndex, first, isArray, noop } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import Card from 'components/card';
import { EllipsisMenu, MenuItem, MenuTitle } from 'components/ellipsis-menu';
import Pagination from 'components/pagination';
import Table from './table';
import TableSummary from './summary';

class TableCard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			showCols: fill( Array( props.headers.length ), true ),
		};
		this.toggleCols = this.toggleCols.bind( this );
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

	filterCols( rows = [] ) {
		const { showCols } = this.state;
		// Header is a 1d array
		if ( ! isArray( first( rows ) ) ) {
			return rows.filter( ( col, i ) => showCols[ i ] );
		}
		// Rows is a 2d array
		return rows.map( row => row.filter( ( col, i ) => showCols[ i ] ) );
	}

	render() {
		const { onClickDownload, onQueryChange, query, rowHeader, summary, title } = this.props;
		const { showCols } = this.state;
		const allHeaders = this.props.headers;
		const headers = this.filterCols( this.props.headers );
		const rows = this.filterCols( this.props.rows );

		return (
			<Card
				className="woocommerce-table"
				title={ title }
				action={
					onClickDownload && (
						<IconButton onClick={ onClickDownload } icon="arrow-down" size={ 18 } isDefault>
							{ __( 'Download', 'wc-admin' ) }
						</IconButton>
					)
				}
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
				{ /* @todo Switch a placeholder view if we don't have rows */ }
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
					perPage={ parseInt( query.per_page ) || 25 }
					total={ 5000 }
					onPageChange={ onQueryChange( 'page' ) }
					onPerPageChange={ onQueryChange( 'per_page' ) }
				/>
			</Card>
		);
	}
}

TableCard.propTypes = {
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.string,
			required: PropTypes.bool,
		} )
	),
	onQueryChange: PropTypes.func,
	onClickDownload: PropTypes.func,
	query: PropTypes.object,
	rowHeader: PropTypes.oneOfType( [ PropTypes.number, PropTypes.bool ] ),
	rows: PropTypes.arrayOf(
		PropTypes.arrayOf(
			PropTypes.shape( {
				display: PropTypes.node,
				value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number, PropTypes.bool ] ),
			} )
		)
	).isRequired,
	summary: PropTypes.arrayOf(
		PropTypes.shape( {
			label: PropTypes.node,
			value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ),
		} )
	),
	title: PropTypes.string.isRequired,
};

TableCard.defaultProps = {
	onQueryChange: noop,
	query: {},
	rowHeader: 0,
	rows: [],
};

export { TableCard, Table, TableSummary };
export { default as TablePlaceholder } from './placeholder';
