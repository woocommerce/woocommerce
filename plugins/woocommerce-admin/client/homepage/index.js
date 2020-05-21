/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { Suspense, lazy } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { isOnboardingEnabled } from 'dashboard/utils';

const ProfileWizard = lazy( () =>
	import( /* webpackChunkName: "profile-wizard" */ '../profile-wizard' )
);
import Layout from './layout';

const Homepage = ( { profileItems, query } ) => {
	if ( isOnboardingEnabled() && ! profileItems.completed ) {
		return (
			<Suspense fallback={ <Spinner /> }>
				<ProfileWizard query={ query } />
			</Suspense>
		);
	}

	return <Layout query={ query } />;
};

export default compose(
	withSelect( ( select ) => {
		if ( ! isOnboardingEnabled() ) {
			return;
		}

		const { getProfileItems } = select( 'wc-api' );
		const profileItems = getProfileItems();

		return { profileItems };
	} )
)( Homepage );
