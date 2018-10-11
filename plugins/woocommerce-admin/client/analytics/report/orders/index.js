/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ReportFilters } from '@woocommerce/components';
import { filters, advancedFilterConfig } from './config';
import OrdersReportTable from './table';

class OrdersReport extends Component {
	constructor( props ) {
		super( props );
	}

	render() {
		const { isRequesting, orders, path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedConfig={ advancedFilterConfig }
				/>
				<OrdersReportTable isRequesting={ isRequesting } orders={ orders } query={ query } />
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getOrders } = select( 'wc-admin' );
		const orders = getOrders();
		const isRequesting = select( 'core/data' ).isResolving( 'wc-admin', 'getOrders' );
		return { isRequesting, orders };
	} )
)( OrdersReport );
