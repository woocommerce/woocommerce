/**
 * External dependencies
 */
import { Component, Suspense, lazy } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { OrderAttributionInstallBanner } from '../order-attribution';

const CustomizableDashboard = lazy( () =>
	import( /* webpackChunkName: "customizable-dashboard" */ './customizable' )
);

class Dashboard extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Suspense fallback={ <Spinner /> }>
				<OrderAttributionInstallBanner />
				<CustomizableDashboard query={ query } path={ path } />
			</Suspense>
		);
	}
}

export default Dashboard;
