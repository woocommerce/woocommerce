/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { ReportFilters, SummaryListPlaceholder, ChartPlaceholder } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { charts, filters } from './config';
import getSelectedChart from 'lib/get-selected-chart';
import ProductsReportTable from './table';
import ReportChart from 'analytics/components/report-chart';
import ReportError from 'analytics/components/report-error';
import ReportSummary from 'analytics/components/report-summary';
import VariationsReportTable from './table-variations';
import withSelect from 'wc-api/with-select';

class ProductsReport extends Component {
	getChartMeta() {
		const { query, isSingleProductView, isSingleProductVariable } = this.props;

		const isProductDetailsView =
			'top_items' === query.filter ||
			'top_sales' === query.filter ||
			'compare-products' === query.filter;

		const mode =
			isProductDetailsView || ( isSingleProductView && isSingleProductVariable )
				? 'item-comparison'
				: 'time-comparison';
		const compareObject =
			isSingleProductView && isSingleProductVariable ? 'variations' : 'products';
		const label =
			isSingleProductView && isSingleProductVariable
				? __( '%s variations', 'wc-admin' )
				: __( '%s products', 'wc-admin' );

		return {
			compareObject,
			itemsLabel: label,
			mode,
		};
	}

	render() {
		const { compareObject, itemsLabel, mode } = this.getChartMeta();
		const {
			path,
			query,
			isProductsError,
			isProductsRequesting,
			isSingleProductVariable,
		} = this.props;

		if ( isProductsError ) {
			return <ReportError isError />;
		}

		if ( isProductsRequesting ) {
			return (
				<Fragment>
					<ReportFilters query={ query } path={ path } filters={ filters } />
					<SummaryListPlaceholder numberOfItems={ charts.length } />
					<span className="screen-reader-text">
						{ __( 'Your requested data is loading', 'wc-admin' ) }
					</span>
					<div className="woocommerce-chart">
						<div className="woocommerce-chart__body">
							<ChartPlaceholder height={ 220 } />
						</div>
					</div>
					<ProductsReportTable query={ query } />
				</Fragment>
			);
		}

		const chartQuery = {
			...query,
		};

		if ( 'item-comparison' === mode ) {
			chartQuery.segmentby = 'products' === compareObject ? 'product' : 'variation';
		}

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					mode={ mode }
					charts={ charts }
					endpoint="products"
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					mode={ mode }
					filters={ filters }
					charts={ charts }
					endpoint="products"
					itemsLabel={ itemsLabel }
					path={ path }
					query={ chartQuery }
					selectedChart={ getSelectedChart( chartQuery.chart, charts ) }
				/>
				{ isSingleProductVariable ? (
					<VariationsReportTable query={ query } />
				) : (
					<ProductsReportTable query={ query } />
				) }
			</Fragment>
		);
	}
}

ProductsReport.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const { getProducts, isGetProductsRequesting, getProductsError } = select( 'wc-api' );
		const isSingleProductView = query.products && 1 === query.products.split( ',' ).length;
		if ( isSingleProductView ) {
			const includeArgs = { include: query.products };
			// TODO Look at similar usage to populate tags in the Search component.
			const products = getProducts( includeArgs );
			const isVariable = products[ 0 ] && 'variable' === products[ 0 ].type;
			const isProductsRequesting = isGetProductsRequesting( includeArgs );
			const isProductsError = getProductsError( includeArgs );
			return {
				query: {
					...query,
					'is-variable': isVariable,
				},
				isSingleProductView,
				isSingleProductVariable: isVariable,
				isProductsRequesting,
				isProductsError,
			};
		}

		return {
			query,
			isSingleProductView,
		};
	} )
)( ProductsReport );
