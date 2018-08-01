/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, createRef } from '@wordpress/element';
import classnames from 'classnames';
import { IconButton } from '@wordpress/components';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import { isEqual, uniqueId } from 'lodash';

const ASC = 'ascending';
const DESC = 'descending';

const getDisplay = cell => cell.display || null;
const getValue = cell => cell.value || 0;

class Table extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			tabIndex: null,
			rows: props.rows || [],
			sortedBy: null,
			sortDir: 'none',
		};
		this.container = createRef();
		this.sortBy = this.sortBy.bind( this );
		this.captionID = uniqueId( 'caption-' );
	}

	componentDidUpdate( prevProps ) {
		if ( ! isEqual( this.props.rows, prevProps.rows ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				rows: this.props.rows,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
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

	isColSortable( col ) {
		const { rows: [ first ] } = this.props;
		if ( ! first || ! first[ col ] ) {
			return false;
		}

		return false !== first[ col ].value;
	}

	sortBy( col ) {
		this.setState( prevState => {
			// Set the sort direction as inverse of current state
			const sortDir = prevState.sortDir === ASC ? DESC : ASC;
			return {
				rows: prevState.rows
					.slice( 0 )
					.sort(
						( a, b ) =>
							sortDir === ASC
								? getValue( a[ col ] ) > getValue( b[ col ] )
								: getValue( a[ col ] ) < getValue( b[ col ] )
					),
				sortedBy: col,
				sortDir,
			};
		} );
	}

	render() {
		const { caption, classNames, headers, rowHeader } = this.props;
		const { rows, sortedBy, sortDir, tabIndex } = this.state;
		const classes = classnames( 'woocommerce-table__table', classNames );

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
								const isSortable = this.isColSortable( i );
								return (
									<th
										role="columnheader"
										scope="col"
										key={ i }
										aria-sort={ sortedBy === i ? sortDir : 'none' }
										className={ classnames( 'woocommerce-table__header', {
											'is-sortable': isSortable,
											'is-sorted': sortedBy === i,
										} ) }
									>
										{ isSortable ? (
											<IconButton
												icon={
													sortDir === ASC ? (
														<Gridicon size={ 18 } icon="chevron-up" />
													) : (
														<Gridicon size={ 18 } icon="chevron-down" />
													)
												}
												label={
													sortDir !== ASC
														? sprintf( __( 'Sort by %s in ascending order', 'wc-admin' ), header )
														: sprintf( __( 'Sort by %s in descending order', 'wc-admin' ), header )
												}
												onClick={ () => this.sortBy( i ) }
												isDefault
											>
												{ header }
											</IconButton>
										) : (
											header
										) }
									</th>
								);
							} ) }
						</tr>
						{ rows.map( ( row, i ) => (
							<tr key={ i }>
								{ row.map(
									( cell, j ) =>
										rowHeader === j ? (
											<th scope="row" key={ j } className="woocommerce-table__item">
												{ getDisplay( cell ) }
											</th>
										) : (
											<td key={ j } className="woocommerce-table__item">
												{ getDisplay( cell ) }
											</td>
										)
								) }
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
	headers: PropTypes.arrayOf( PropTypes.node ),
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
	rowHeader: 0,
};

export default Table;
