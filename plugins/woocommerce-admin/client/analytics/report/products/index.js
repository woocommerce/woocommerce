/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import { filters } from './config';
import { ReportFilters } from '@woocommerce/components';
import { appendTimestamp, getCurrentDates } from 'lib/date';
import { QUERY_DEFAULTS } from 'store/constants';
import { getReportChartData } from 'store/reports/utils';
import ProductsReportChart from './chart';
import ProductsReportTable from './table';

class ProductsReport extends Component {
	render() {
		const {
			isProductsError,
			isProductsRequesting,
			path,
			primaryData,
			products,
			query,
		} = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ProductsReportChart query={ query } />
				<ProductsReportTable
					isError={ isProductsError || primaryData.isError }
					isRequesting={ isProductsRequesting || primaryData.isRequesting }
					products={ products }
					query={ query }
					totalRows={ get(
						primaryData,
						[ 'data', 'totals', 'products_count' ],
						Object.keys( products ).length
					) }
				/>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const primaryData = getReportChartData( 'products', 'primary', query, select );

		const { getProducts, isGetProductsError, isGetProductsRequesting } = select( 'wc-admin' );
		const tableQuery = {
			orderby: query.orderby || 'items_sold',
			order: query.order || 'desc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
			extended_product_info: true,
		};
		const products = getProducts( tableQuery );
		const isProductsError = isGetProductsError( tableQuery );
		const isProductsRequesting = isGetProductsRequesting( tableQuery );

		return {
			isProductsError,
			isProductsRequesting,
			primaryData,
			products,
		};
	} )
)( ProductsReport );
