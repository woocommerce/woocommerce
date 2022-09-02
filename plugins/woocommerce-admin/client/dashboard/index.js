/**
 * External dependencies
 */
import { Component, Suspense, lazy } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';

const CustomizableDashboard = lazy( () =>
	import( /* webpackChunkName: "customizable-dashboard" */ './customizable' )
);

class Dashboard extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Suspense fallback={ <Spinner /> }>
				<CustomizableDashboard query={ query } path={ path } />
			</Suspense>
		);
	}
}

export default Dashboard;
