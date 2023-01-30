/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { createElement, Fragment, useState } from '@wordpress/element';
import { find, first, without } from 'lodash';
import React from 'react';
import {
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	// @ts-expect-error: Suppressing Module '"@wordpress/components"' has no exported member '__experimentalText'
	__experimentalText as Text,
} from '@wordpress/components';

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
import { TableCardProps } from './types';

const defaultOnQueryChange =
	( param: string ) => ( path?: string, direction?: string ) => {};

const defaultOnColumnsChange = (
	showCols: Array< string >,
	key?: string
) => {};
/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
const TableCard: React.VFC< TableCardProps > = ( {
	actions,
	className,
	hasSearch,
	headers = [],
	ids,
	isLoading = false,
	onQueryChange = defaultOnQueryChange,
	onColumnsChange = defaultOnColumnsChange,
	onSort,
	query = {},
	rowHeader = 0,
	rows = [],
	rowsPerPage,
	showMenu = true,
	summary,
	title,
	totalRows,
	rowKey,
	emptyMessage = undefined,
	...props
} ) => {
	// eslint-disable-next-line no-console
	const getShowCols = ( _headers: TableCardProps[ 'headers' ] = [] ) => {
		return _headers
			.map( ( { key, visible } ) => {
				if ( typeof visible === 'undefined' || visible ) {
					return key;
				}
				return false;
			} )
			.filter( Boolean ) as string[];
	};

	const [ showCols, setShowCols ] = useState( getShowCols( headers ) );

	const onColumnToggle = ( key: string ) => {
		return () => {
			const hasKey = showCols.includes( key );

			if ( hasKey ) {
				// Handle hiding a sorted column
				if ( query.orderby === key ) {
					const defaultSort = find( headers, {
						defaultSort: true,
					} ) ||
						first( headers ) || { key: undefined };
					onQueryChange( 'sort' )( defaultSort.key, 'desc' );
				}

				const newShowCols = without( showCols, key );
				onColumnsChange( newShowCols, key );
				setShowCols( newShowCols );
			} else {
				const newShowCols = [ ...showCols, key ] as string[];
				onColumnsChange( newShowCols, key );
				setShowCols( newShowCols );
			}
		};
	};

	const onPageChange = (
		newPage: string,
		direction?: 'previous' | 'next'
	) => {
		if ( props.onPageChange ) {
			props.onPageChange( parseInt( newPage, 10 ), direction );
		}
		if ( onQueryChange ) {
			onQueryChange( 'paged' )( newPage, direction );
		}
	};

	const allHeaders = headers;
	const visibleHeaders = headers.filter( ( { key } ) =>
		showCols.includes( key )
	);
	const visibleRows = rows.map( ( row ) => {
		return headers
			.map( ( { key }, i ) => {
				return showCols.includes( key ) && row[ i ];
			} )
			.filter( Boolean );
	} );
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
				<div className="woocommerce-table__actions">{ actions }</div>
				{ showMenu && (
					<EllipsisMenu
						label={ __(
							'Choose which values to display',
							'woocommerce'
						) }
						renderContent={ () => (
							<Fragment>
								{ /* @ts-expect-error: Ignoring the error until we migrate ellipsis-menu to TS*/ }
								<MenuTitle>
									{ /* @ts-expect-error: Allow string */ }
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
												onInvoke={
													key !== undefined
														? onColumnToggle( key )
														: undefined
												}
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
			{ /* Ignoring the error to make it backward compatible for now. */ }
			{ /* @ts-expect-error: size must be one of small, medium, largel, xSmall, extraSmall. */ }
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
							headers={ visibleHeaders }
							rowHeader={ rowHeader }
							caption={ title }
							query={ query }
						/>
					</Fragment>
				) : (
					<Table
						rows={ visibleRows as TableCardProps[ 'rows' ] }
						headers={
							visibleHeaders as TableCardProps[ 'headers' ]
						}
						rowHeader={ rowHeader }
						caption={ title }
						query={ query }
						onSort={
							onSort ||
							( onQueryChange( 'sort' ) as (
								key: string,
								direction: string
							) => void )
						}
						rowKey={ rowKey }
						emptyMessage={ emptyMessage }
					/>
				) }
			</CardBody>

			{ /* @ts-expect-error: justify is missing from the latest @types/wordpress__components */ }
			<CardFooter justify="center">
				{ isLoading ? (
					<TableSummaryPlaceholder />
				) : (
					<Fragment>
						<Pagination
							key={ parseInt( query.paged as string, 10 ) || 1 }
							page={ parseInt( query.paged as string, 10 ) || 1 }
							perPage={ rowsPerPage }
							total={ totalRows }
							onPageChange={ onPageChange }
							onPerPageChange={ onQueryChange( 'per_page' ) }
						/>

						{ summary && <TableSummary data={ summary } /> }
					</Fragment>
				) }
			</CardFooter>
		</Card>
	);
};

export default TableCard;
