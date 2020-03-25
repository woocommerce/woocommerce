/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Card, EmptyTable, TableCard } from '@woocommerce/components';
import { getPersistedQuery } from '@woocommerce/navigation';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getLeaderboard } from 'wc-api/items/utils';
import ReportError from 'analytics/components/report-error';
import sanitizeHTML from 'lib/sanitize-html';
import withSelect from 'wc-api/with-select';
import './style.scss';

export class Leaderboard extends Component {
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
			return row.map( ( column ) => {
				return {
					display: (
						<div
							dangerouslySetInnerHTML={ sanitizeHTML(
								column.display
							) }
						/>
					),
					value: column.value,
				};
			} );
		} );
	}

	render() {
		const { isRequesting, isError, totalRows, title } = this.props;
		const classes = 'woocommerce-leaderboard';

		if ( isError ) {
			return <ReportError className={ classes } isError />;
		}

		const rows = this.getFormattedRows();

		if ( ! isRequesting && rows.length === 0 ) {
			return (
				<Card title={ title } className={ classes }>
					<EmptyTable>
						{ __(
							'No data recorded for the selected time period.',
							'woocommerce-admin'
						) }
					</EmptyTable>
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
	 * Which column should be the row header, defaults to the first item (`0`) (see `Table` props).
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

export default compose(
	withSelect( ( select, props ) => {
		const { id, query, totalRows } = props;
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		const leaderboardQuery = {
			id,
			per_page: totalRows,
			persisted_query: getPersistedQuery( query ),
			query,
			select,
			defaultDateRange,
		};
		const leaderboardData = getLeaderboard( leaderboardQuery );

		return leaderboardData;
	} )
)( Leaderboard );
