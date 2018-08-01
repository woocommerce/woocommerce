/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { IconButton, ToggleControl } from '@wordpress/components';
import { noop } from 'lodash';
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

// @todo Handle toggling columns

const TableCard = ( {
	headers,
	onClickDownload,
	onQueryChange,
	query,
	rows,
	rowHeader,
	summary,
	title,
} ) => {
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
					{ headers.map( ( label, i ) => (
						<MenuItem key={ i } onInvoke={ noop }>
							<ToggleControl label={ label } checked={ true } onChange={ noop } />
						</MenuItem>
					) ) }
				</EllipsisMenu>
			}
		>
			{ /* @todo Switch a placeholder view if we don't have rows */ }
			<Table rows={ rows } headers={ headers } rowHeader={ rowHeader } caption={ title } />

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
};

TableCard.propTypes = {
	headers: PropTypes.arrayOf( PropTypes.node ),
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
