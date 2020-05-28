/**
 * External dependencies
 */
import { Component, Suspense, lazy } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { identity } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import {
	ONBOARDING_STORE_NAME,
	withOnboardingHydration,
} from '@woocommerce/data';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { isOnboardingEnabled } from 'dashboard/utils';

const CustomizableDashboard = lazy( () =>
	import( /* webpackChunkName: "customizable-dashboard" */ './customizable' )
);

const ProfileWizard = lazy( () =>
	import( /* webpackChunkName: "profile-wizard" */ '../profile-wizard' )
);

class Dashboard extends Component {
	render() {
		const { path, profileItems, query } = this.props;

		if (
			isOnboardingEnabled() &&
			! profileItems.completed &&
			! window.wcAdminFeatures.homepage
		) {
			return (
				<Suspense fallback={ <Spinner /> }>
					<ProfileWizard query={ query } />
				</Suspense>
			);
		}

		if ( window.wcAdminFeatures[ 'analytics-dashboard/customizable' ] ) {
			return (
				<Suspense fallback={ <Spinner /> }>
					<CustomizableDashboard query={ query } path={ path } />
				</Suspense>
			);
		}

		return null;
	}
}

export default compose(
	getSetting( 'onboarding', {} ).profile
		? withOnboardingHydration( getSetting( 'onboarding', {} ).profile )
		: identity,
	withSelect( ( select ) => {
		if ( ! isOnboardingEnabled() ) {
			return;
		}

		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		const profileItems = getProfileItems();

		return { profileItems };
	} )
)( Dashboard );
