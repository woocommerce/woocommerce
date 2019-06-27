/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

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
import ReportFilters from 'analytics/components/report-filters';

class ProductsReport extends Component {
	getChartMeta() {
		const { query, isSingleProductView, isSingleProductVariable } = this.props;
		const isCompareView =
			'compare-products' === query.filter &&
			query.products &&
			query.products.split( ',' ).length > 1;

		const mode =
			isCompareView || ( isSingleProductView && isSingleProductVariable )
				? 'item-comparison'
				: 'time-comparison';
		const compareObject =
			isSingleProductView && isSingleProductVariable ? 'variations' : 'products';
		const label =
			isSingleProductView && isSingleProductVariable
				? __( '%d variations', 'woocommerce-admin' )
				: __( '%d products', 'woocommerce-admin' );

		return {
			compareObject,
			itemsLabel: label,
			mode,
		};
	}

	render() {
		const { compareObject, itemsLabel, mode } = this.getChartMeta();
		const { path, query, isError, isRequesting, isSingleProductVariable } = this.props;

		if ( isError ) {
			return <ReportError isError />;
		}

		const chartQuery = {
			...query,
		};

		if ( 'item-comparison' === mode ) {
			chartQuery.segmentby = 'products' === compareObject ? 'product' : 'variation';
		}

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } report="products" />
				<ReportSummary
					mode={ mode }
					charts={ charts }
					endpoint="products"
					isRequesting={ isRequesting }
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
				/>
				<ReportChart
					mode={ mode }
					filters={ filters }
					endpoint="products"
					isRequesting={ isRequesting }
					itemsLabel={ itemsLabel }
					path={ path }
					query={ chartQuery }
					selectedChart={ getSelectedChart( chartQuery.chart, charts ) }
				/>
				{ isSingleProductVariable ? (
					<VariationsReportTable
						baseSearchQuery={ { filter: 'single_product' } }
						isRequesting={ isRequesting }
						query={ query }
						filters={ filters }
					/>
				) : (
					<ProductsReportTable isRequesting={ isRequesting } query={ query } filters={ filters } />
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
		const { query, isRequesting } = props;
		const isSingleProductView =
			! query.search && query.products && 1 === query.products.split( ',' ).length;
		if ( isRequesting ) {
			return {
				query: {
					...query,
				},
				isSingleProductView,
				isRequesting,
			};
		}

		const { getItems, isGetItemsRequesting, getItemsError } = select( 'wc-api' );
		if ( isSingleProductView ) {
			const productId = parseInt( query.products );
			const includeArgs = { include: productId };
			// TODO Look at similar usage to populate tags in the Search component.
			const products = getItems( 'products', includeArgs );
			const isVariable =
				products && products.get( productId ) && 'variable' === products.get( productId ).type;
			const isProductsRequesting = isGetItemsRequesting( 'products', includeArgs );
			const isProductsError = Boolean( getItemsError( 'products', includeArgs ) );
			return {
				query: {
					...query,
					'is-variable': isVariable,
				},
				isSingleProductView,
				isSingleProductVariable: isVariable,
				isRequesting: isProductsRequesting,
				isError: isProductsError,
			};
		}

		return {
			query,
			isSingleProductView,
		};
	} )
)( ProductsReport );
