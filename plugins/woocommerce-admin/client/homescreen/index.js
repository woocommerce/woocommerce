/**
 * External dependencies
 */
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
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { isOnboardingEnabled } from 'dashboard/utils';

import Layout from './layout';

const Homescreen = ( { profileItems, query } ) => {
	const { completed: profilerCompleted, skipped: profilerSkipped } =
		profileItems || {};
	if ( isOnboardingEnabled() && ! profilerCompleted && ! profilerSkipped ) {
		getHistory().push( getNewPath( {}, `/profiler`, {} ) );
	}

	return <Layout query={ query } />;
};

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
)( Homescreen );
