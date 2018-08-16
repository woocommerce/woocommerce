/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, createRef, Fragment } from '@wordpress/element';
import classnames from 'classnames';
import { IconButton } from '@wordpress/components';
import { find, get, noop, uniqueId } from 'lodash';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';

const ASC = 'asc';
const DESC = 'desc';

const getDisplay = cell => cell.display || null;

class Table extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			tabIndex: null,
		};
		this.container = createRef();
		this.sortBy = this.sortBy.bind( this );
		this.headersID = uniqueId( 'header-' );
		this.captionID = uniqueId( 'caption-' );
	}

	componentDidMount() {
		const { scrollWidth, clientWidth } = this.container.current;
		const scrollable = scrollWidth > clientWidth;
		/* eslint-disable react/no-did-mount-set-state */
		this.setState( {
			tabIndex: scrollable ? '0' : null,
		} );
		/* eslint-enable react/no-did-mount-set-state */
	}

	sortBy( key ) {
		const { headers, query } = this.props;
		return () => {
			const currentKey =
				query.orderby || get( find( headers, { defaultSort: true } ), 'key', false );
			const currentDir = query.order || DESC;
			let dir = DESC;
			if ( key === currentKey ) {
				dir = DESC === currentDir ? ASC : DESC;
			}
			this.props.onSort( key, dir );
		};
	}

	render() {
		const { caption, classNames, headers, query, rowHeader, rows } = this.props;
		const { tabIndex } = this.state;
		const classes = classnames( 'woocommerce-table__table', classNames );
		const sortedBy = query.orderby || get( find( headers, { defaultSort: true } ), 'key', false );
		const sortDir = query.order || DESC;

		return (
			<div
				className={ classes }
				ref={ this.container }
				tabIndex={ tabIndex }
				aria-labelledby={ this.captionID }
				role="group"
			>
				<table>
					<caption id={ this.captionID } className="woocommerce-table__caption screen-reader-text">
						{ caption }
						{ tabIndex === '0' && <small>{ __( '(scroll to see more)', 'wc-admin' ) }</small> }
					</caption>
					<tbody>
						<tr>
							{ headers.map( ( header, i ) => {
								const { isSortable, isNumeric, key, label } = header;
								const thProps = {
									className: classnames( 'woocommerce-table__header', {
										'is-sortable': isSortable,
										'is-sorted': sortedBy === key,
										'is-numeric': isNumeric,
									} ),
								};
								if ( isSortable ) {
									thProps[ 'aria-sort' ] = 'none';
									if ( sortedBy === key ) {
										thProps[ 'aria-sort' ] = sortDir === ASC ? 'ascending' : 'descending';
									}
								}
								// We only sort by ascending if the col is already sorted descending
								const iconLabel =
									sortedBy === key && sortDir !== ASC
										? sprintf( __( 'Sort by %s in ascending order', 'wc-admin' ), label )
										: sprintf( __( 'Sort by %s in descending order', 'wc-admin' ), label );

								return (
									<th role="columnheader" scope="col" key={ i } { ...thProps }>
										{ isSortable ? (
											<Fragment>
												<IconButton
													icon={
														sortedBy === key && sortDir === ASC ? (
															<Gridicon size={ 18 } icon="chevron-up" />
														) : (
															<Gridicon size={ 18 } icon="chevron-down" />
														)
													}
													aria-describedby={ `${ this.headersID }-${ i }` }
													onClick={ this.sortBy( key ) }
													isDefault
												>
													{ label }
												</IconButton>
												<span className="screen-reader-text" id={ `${ this.headersID }-${ i }` }>
													{ iconLabel }
												</span>
											</Fragment>
										) : (
											label
										) }
									</th>
								);
							} ) }
						</tr>
						{ rows.map( ( row, i ) => (
							<tr key={ i }>
								{ row.map( ( cell, j ) => {
									const { isNumeric } = headers[ j ];
									const isHeader = rowHeader === j;
									const Cell = isHeader ? 'th' : 'td';
									const cellClasses = classnames( 'woocommerce-table__item', {
										'is-numeric': isNumeric,
									} );
									return (
										<Cell scope={ isHeader ? 'row' : null } key={ j } className={ cellClasses }>
											{ getDisplay( cell ) }
										</Cell>
									);
								} ) }
							</tr>
						) ) }
					</tbody>
				</table>
			</div>
		);
	}
}

Table.propTypes = {
	caption: PropTypes.string.isRequired,
	className: PropTypes.string,
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			defaultSort: PropTypes.bool,
			isSortable: PropTypes.bool,
			key: PropTypes.string,
			label: PropTypes.string,
			required: PropTypes.bool,
		} )
	),
	onSort: PropTypes.func,
	query: PropTypes.object,
	rows: PropTypes.arrayOf(
		PropTypes.arrayOf(
			PropTypes.shape( {
				display: PropTypes.node,
				value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number, PropTypes.bool ] ),
			} )
		)
	).isRequired,
	rowHeader: PropTypes.oneOfType( [ PropTypes.number, PropTypes.bool ] ),
};

Table.defaultProps = {
	headers: [],
	onSort: noop,
	query: {},
	rowHeader: 0,
};

export default Table;
