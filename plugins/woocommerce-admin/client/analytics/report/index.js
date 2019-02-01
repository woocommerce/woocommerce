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
import { getQuery } from '@woocommerce/navigation';

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
import { searchItemsByString } from 'wc-api/items/utils';
import withSelect from 'wc-api/with-select';

const REPORTS_FILTER = 'woocommerce-reports-list';

const getReports = () => {
	const reports = applyFilters( REPORTS_FILTER, [
		{
			report: 'revenue',
			title: __( 'Revenue', 'wc-admin' ),
			component: RevenueReport,
		},
		{
			report: 'products',
			title: __( 'Products', 'wc-admin' ),
			component: ProductsReport,
		},
		{
			report: 'orders',
			title: __( 'Orders', 'wc-admin' ),
			component: OrdersReport,
		},
		{
			report: 'categories',
			title: __( 'Categories', 'wc-admin' ),
			component: CategoriesReport,
		},
		{
			report: 'coupons',
			title: __( 'Coupons', 'wc-admin' ),
			component: CouponsReport,
		},
		{
			report: 'taxes',
			title: __( 'Taxes', 'wc-admin' ),
			component: TaxesReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'wc-admin' ),
			component: DownloadsReport,
		},
		{
			report: 'stock',
			title: __( 'Stock', 'wc-admin' ),
			component: StockReport,
		},
		{
			report: 'customers',
			title: __( 'Customers', 'wc-admin' ),
			component: CustomersReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'wc-admin' ),
			component: DownloadsReport,
		},
	] );

	return reports;
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

		const { params } = this.props;
		const report = find( getReports(), { report: params.report } );
		if ( ! report ) {
			return null;
		}
		const Container = report.component;
		return (
			<Fragment>
				<Header
					sections={ [ [ '/analytics/revenue', __( 'Analytics', 'wc-admin' ) ], report.title ] }
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
		const { search } = getQuery();

		if ( ! search ) {
			return {};
		}

		const { report } = props.params;
		const items = searchItemsByString( select, report, search );
		const ids = Object.keys( items );
		if ( ! ids.length ) {
			return {}; // @TODO if no results were found, we should avoid making a server request.
		}

		return {
			query: {
				...props.query,
				[ report ]: ids.join( ',' ),
			},
		};
	} )
)( Report );
