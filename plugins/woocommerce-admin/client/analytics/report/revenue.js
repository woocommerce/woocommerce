/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { map } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import DatePicker from 'components/date-picker';
import { getAdminLink } from 'lib/nav-utils';
import { getCurrencyFormatString } from 'lib/currency';
import Header from 'components/header';
import { SummaryList, SummaryNumber } from 'components/summary';
import Table from 'components/table';

// Mock data until we fetch from an API
import rawData from './mock-data';

class RevenueReport extends Component {
	getRowsContent( data ) {
		return map( data, ( row, label ) => {
			// @TODO How to create this per-report? Can use `w`, `year`, `m` to build time-specific order links
			// we need to know which kind of report this is, and parse the `label` to get this row's date
			const orderLink = (
				<a href={ getAdminLink( '/edit.php?post_type=shop_order&w=4&year=2018' ) }>
					{ row.order_count }
				</a>
			);
			return [
				label,
				orderLink,
				getCurrencyFormatString( row.gross_revenue ),
				getCurrencyFormatString( row.refunds ),
				getCurrencyFormatString( row.coupons ),
				getCurrencyFormatString( row.taxes ),
				getCurrencyFormatString( row.shipping ),
				getCurrencyFormatString( row.net_revenue ),
			];
		} );
	}

	render() {
		const { path, query } = this.props;
		const rows = this.getRowsContent( rawData.intervals[ 0 ].week[ 0 ] );
		const headers = [
			__( 'Date', 'woo-dash' ),
			__( 'Orders', 'woo-dash' ),
			__( 'Gross Revenue', 'woo-dash' ),
			__( 'Refunds', 'woo-dash' ),
			__( 'Coupons', 'woo-dash' ),
			__( 'Taxes', 'woo-dash' ),
			__( 'Shipping', 'woo-dash' ),
			__( 'Net Revenue', 'woo-dash' ),
		];

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'woo-dash' ) ],
						__( 'Revenue', 'woo-dash' ),
					] }
				/>
				<DatePicker query={ query } path={ path } />

				<SummaryList>
					<SummaryNumber
						value={ '$829.40' }
						label={ __( 'Gross Revenue', 'woo-dash' ) }
						delta={ 29 }
					/>
					<SummaryNumber
						value={ '$24.00' }
						label={ __( 'Refunds', 'woo-dash' ) }
						delta={ -10 }
						selected
					/>
					<SummaryNumber value={ '$49.90' } label={ __( 'Coupons', 'woo-dash' ) } delta={ 15 } />
					<SummaryNumber value={ '$66.39' } label={ __( 'Taxes', 'woo-dash' ) } />
				</SummaryList>
				<Card title={ __( 'Gross Revenue' ) }>
					<p>Graph here</p>
					<hr />
					<Table rows={ rows } headers={ headers } caption={ __( 'Revenue Last Week' ) } />
				</Card>
			</Fragment>
		);
	}
}

RevenueReport.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default RevenueReport;
