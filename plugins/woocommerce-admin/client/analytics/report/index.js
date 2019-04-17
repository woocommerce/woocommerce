/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { find } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { useFilters } from '@woocommerce/components';
import { getQuery, getSearchWords } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import Header from 'header';
import OrdersReport from './orders';
import ProductsReport from './products';
import RevenueReport from './revenue';
import CategoriesReport from './categories';
import CouponsReport from './coupons';
import TaxesReport from './taxes';
import DownloadsReport from './downloads';
import StockReport from './stock';
import CustomersReport from './customers';
import ReportError from 'analytics/components/report-error';
import { searchItemsByString } from 'wc-api/items/utils';
import withSelect from 'wc-api/with-select';

const REPORTS_FILTER = 'woocommerce_admin_reports_list';

const getReports = () => {
	const reports = [
		{
			report: 'revenue',
			title: __( 'Revenue', 'woocommerce-admin' ),
			component: RevenueReport,
		},
		{
			report: 'products',
			title: __( 'Products', 'woocommerce-admin' ),
			component: ProductsReport,
		},
		{
			report: 'orders',
			title: __( 'Orders', 'woocommerce-admin' ),
			component: OrdersReport,
		},
		{
			report: 'categories',
			title: __( 'Categories', 'woocommerce-admin' ),
			component: CategoriesReport,
		},
		{
			report: 'coupons',
			title: __( 'Coupons', 'woocommerce-admin' ),
			component: CouponsReport,
		},
		{
			report: 'taxes',
			title: __( 'Taxes', 'woocommerce-admin' ),
			component: TaxesReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'woocommerce-admin' ),
			component: DownloadsReport,
		},
		'yes' === wcSettings.manageStock
			? {
					report: 'stock',
					title: __( 'Stock', 'woocommerce-admin' ),
					component: StockReport,
				}
			: null,
		{
			report: 'customers',
			title: __( 'Customers', 'woocommerce-admin' ),
			component: CustomersReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'woocommerce-admin' ),
			component: DownloadsReport,
		},
	].filter( Boolean );

	return applyFilters( REPORTS_FILTER, reports );
};

class Report extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			hasError: false,
		};
	}

	componentDidCatch( error ) {
		this.setState( {
			hasError: true,
		} );
		/* eslint-disable no-console */
		console.warn( error );
		/* eslint-enable no-console */
	}

	render() {
		if ( this.state.hasError ) {
			return null;
		}

		const { params, isError } = this.props;

		if ( isError ) {
			return <ReportError isError />;
		}

		const report = find( getReports(), { report: params.report } );
		if ( ! report ) {
			return null;
		}
		const Container = report.component;
		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics/revenue', __( 'Analytics', 'woocommerce-admin' ) ],
						report.title,
					] }
				/>
				<Container { ...this.props } />
			</Fragment>
		);
	}
}

Report.propTypes = {
	params: PropTypes.object.isRequired,
};

export default compose(
	useFilters( REPORTS_FILTER ),
	withSelect( ( select, props ) => {
		const query = getQuery();
		const { search } = query;

		if ( ! search ) {
			return {};
		}

		const { report } = props.params;
		const searchWords = getSearchWords( query );
		// Single Category view in Categories Report uses the products endpoint, so search must also.
		const mappedReport =
			'categories' === report && 'single_category' === query.filter ? 'products' : report;
		const itemsResult = searchItemsByString( select, mappedReport, searchWords );
		const { isError, isRequesting, items } = itemsResult;
		const ids = Object.keys( items );
		if ( ! ids.length ) {
			return {
				isError,
				isRequesting,
			};
		}

		return {
			isError,
			isRequesting,
			query: {
				...props.query,
				[ mappedReport ]: ids.join( ',' ),
			},
		};
	} )
)( Report );
