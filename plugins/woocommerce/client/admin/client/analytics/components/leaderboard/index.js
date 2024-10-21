/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { EmptyTable, AnalyticsError, TableCard } from '@woocommerce/components';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { getPersistedQuery } from '@woocommerce/navigation';
import {
	getFilterQuery,
	getLeaderboard,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { CurrencyContext } from '@woocommerce/currency';
import { formatValue } from '@woocommerce/number';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import sanitizeHTML from '../../../lib/sanitize-html';
import './style.scss';

const formattable = new Set( [ 'currency', 'number' ] );

export class Leaderboard extends Component {
	getFormattedColumn = ( column ) => {
		const { format } = column;

		/*
		 * The format property is used for numeric columns and is optional.
		 * The `value` property type is specified as string in the API schema
		 * and it's extensible from other extensions. Therefore, even if the
		 * actual type of numeric columns returned by WooCoomerce's own API is
		 * number, there is no guarantee the value will be a number.
		 */
		if ( formattable.has( column.format ) && isFinite( column.value ) ) {
			const value = parseFloat( column.value );

			if ( ! Number.isNaN( value ) ) {
				const { formatAmount, getCurrencyConfig } = this.context;
				const display =
					format === 'currency'
						? formatAmount( value )
						: formatValue( getCurrencyConfig(), format, value );

				return {
					display,
					value,
				};
			}
		}

		return {
			display: (
				<div
					dangerouslySetInnerHTML={ sanitizeHTML( column.display ) }
				/>
			),
			value: column.value,
		};
	};

	getFormattedHeaders() {
		return this.props.headers.map( ( header, i ) => {
			return {
				isLeftAligned: i === 0,
				hiddenByDefault: false,
				isSortable: false,
				key: header.label,
				label: header.label,
			};
		} );
	}

	getFormattedRows() {
		return this.props.rows.map( ( row ) => {
			return row.map( this.getFormattedColumn );
		} );
	}

	render() {
		const { isRequesting, isError, totalRows, title } = this.props;
		const classes = 'woocommerce-leaderboard';

		if ( isError ) {
			return <AnalyticsError className={ classes } />;
		}

		const rows = this.getFormattedRows();

		if ( ! isRequesting && rows.length === 0 ) {
			return (
				<Card className={ classes }>
					<CardHeader>
						<Text
							size={ 16 }
							weight={ 600 }
							as="h3"
							color="#23282d"
						>
							{ title }
						</Text>
					</CardHeader>
					<CardBody size={ null }>
						<EmptyTable>
							{ __(
								'No data recorded for the selected time period.',
								'woocommerce'
							) }
						</EmptyTable>
					</CardBody>
				</Card>
			);
		}

		return (
			<TableCard
				className={ classes }
				headers={ this.getFormattedHeaders() }
				isLoading={ isRequesting }
				rows={ rows }
				rowsPerPage={ totalRows }
				showMenu={ false }
				title={ title }
				totalRows={ totalRows }
			/>
		);
	}
}

Leaderboard.propTypes = {
	/**
	 * An array of column headers.
	 */
	headers: PropTypes.arrayOf(
		PropTypes.shape( {
			label: PropTypes.string,
		} )
	),
	/**
	 * String of leaderboard ID to display.
	 */
	id: PropTypes.string.isRequired,
	/**
	 * Query args added to the report table endpoint request.
	 */
	query: PropTypes.object,
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
	 * String to display as the title of the table.
	 */
	title: PropTypes.string.isRequired,
	/**
	 * Number of table rows.
	 */
	totalRows: PropTypes.number.isRequired,
};

Leaderboard.defaultProps = {
	rows: [],
	isError: false,
	isRequesting: false,
};

Leaderboard.contextType = CurrencyContext;

export default compose(
	withSelect( ( select, props ) => {
		const { id, query, totalRows, filters } = props;
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );
		const filterQuery = getFilterQuery( { filters, query } );

		const leaderboardQuery = {
			id,
			per_page: totalRows,
			persisted_query: getPersistedQuery( query ),
			query,
			select,
			defaultDateRange,
			filterQuery,
		};
		const leaderboardData = getLeaderboard( leaderboardQuery );

		return leaderboardData;
	} )
)( Leaderboard );
