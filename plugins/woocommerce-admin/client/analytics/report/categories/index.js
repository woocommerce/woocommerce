/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { advancedFilters, charts, filters } from './config';
import CategoriesReportTable from './table';
import getSelectedChart from '../../../lib/get-selected-chart';
import ReportChart from '../../components/report-chart';
import ReportSummary from '../../components/report-summary';
import ProductsReportTable from '../products/table';
import ReportFilters from '../../components/report-filters';

class CategoriesReport extends Component {
	getChartMeta() {
		const { query } = this.props;
		const isCompareView =
			query.filter === 'compare-categories' &&
			query.categories &&
			query.categories.split( ',' ).length > 1;
		const isSingleCategoryView =
			query.filter === 'single_category' && !! query.categories;

		const mode =
			isCompareView || isSingleCategoryView
				? 'item-comparison'
				: 'time-comparison';
		const itemsLabel = isSingleCategoryView
			? /* translators: %d: number of products */
			  __( '%d products', 'woocommerce' )
			: /* translators: %d: number of categories */
			  __( '%d categories', 'woocommerce' );

		return {
			isSingleCategoryView,
			itemsLabel,
			mode,
		};
	}

	render() {
		const { isRequesting, query, path } = this.props;
		const { mode, itemsLabel, isSingleCategoryView } = this.getChartMeta();

		const chartQuery = {
			...query,
		};

		if ( mode === 'item-comparison' ) {
			chartQuery.segmentby = isSingleCategoryView
				? 'product'
				: 'category';
		}

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedFilters={ advancedFilters }
					report="categories"
				/>
				<ReportSummary
					charts={ charts }
					endpoint="products"
					limitProperties={
						isSingleCategoryView
							? [ 'products', 'categories' ]
							: [ 'categories' ]
					}
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
					filters={ filters }
					advancedFilters={ advancedFilters }
					report="categories"
				/>
				<ReportChart
					charts={ charts }
					filters={ filters }
					advancedFilters={ advancedFilters }
					mode={ mode }
					endpoint="products"
					limitProperties={
						isSingleCategoryView
							? [ 'products', 'categories' ]
							: [ 'categories' ]
					}
					path={ path }
					query={ chartQuery }
					isRequesting={ isRequesting }
					itemsLabel={ itemsLabel }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				{ isSingleCategoryView ? (
					<ProductsReportTable
						isRequesting={ isRequesting }
						query={ chartQuery }
						baseSearchQuery={ { filter: 'single_category' } }
						hideCompare={ isSingleCategoryView }
						filters={ filters }
						advancedFilters={ advancedFilters }
					/>
				) : (
					<CategoriesReportTable
						isRequesting={ isRequesting }
						query={ query }
						filters={ filters }
						advancedFilters={ advancedFilters }
					/>
				) }
			</Fragment>
		);
	}
}

CategoriesReport.propTypes = {
	query: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
};

export default CategoriesReport;
