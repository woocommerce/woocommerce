/**
 * External dependencies
 */
import { Component, Suspense, lazy } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	OrderAttributionInstallBanner,
	OrderAttributionInstallBannerImage,
} from '~/order-attribution-install-banner';
import './style.scss';

const CustomizableDashboard = lazy( () =>
	import( /* webpackChunkName: "customizable-dashboard" */ './customizable' )
);

class Dashboard extends Component {
	render() {
		const { path, query } = this.props;

		return (
			<Suspense fallback={ <Spinner /> }>
				<OrderAttributionInstallBanner
					title={ __(
						'Discover what drives your sales',
						'woocommerce'
					) }
					description={ __(
						'Understand what truly drives revenue with our powerful order attribution extension. Use it to track your sales journey, identify your most effective marketing channels, and optimize your sales strategy.',
						'woocommerce'
					) }
					buttonText={ __( 'Try it now', 'woocommerce' ) }
					badgeText={ __( 'New', 'woocommerce' ) }
					bannerImage={ <OrderAttributionInstallBannerImage /> }
					dismissable
				/>
				<CustomizableDashboard query={ query } path={ path } />
			</Suspense>
		);
	}
}

export default Dashboard;
