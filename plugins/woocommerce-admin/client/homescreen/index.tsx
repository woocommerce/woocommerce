/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { identity } from 'lodash';
import { getSetting } from '@woocommerce/wc-admin-settings';
import {
	ONBOARDING_STORE_NAME,
	withOnboardingHydration,
	WCDataSelector,
} from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import type { History } from 'history';
/**
 * Internal dependencies
 */
import Layout from './layout';

type HomescreenProps = ReturnType< typeof withSelectHandler > & {
	hasFinishedResolution: boolean;
	query: Record< string, string >;
};

const Homescreen = ( {
	profileItems: {
		completed: profilerCompleted,
		skipped: profilerSkipped,
	} = {},
	hasFinishedResolution,
	query,
}: HomescreenProps ) => {
	if ( hasFinishedResolution && ! profilerCompleted && ! profilerSkipped ) {
		( getHistory() as History ).push(
			getNewPath( {}, '/setup-wizard', {} )
		);
	}

	return <Layout query={ query } />;
};

const onboardingData = getSetting( 'onboarding', {} );

const withSelectHandler = ( select: WCDataSelector ) => {
	const { getProfileItems, hasFinishedResolution } = select(
		ONBOARDING_STORE_NAME
	);

	return {
		profileItems: getProfileItems(),
		hasFinishedResolution: hasFinishedResolution( 'getProfileItems', [] ),
	};
};

export default compose(
	onboardingData.profile
		? withOnboardingHydration( {
				profileItems: onboardingData.profile,
		  } )
		: identity,
	withSelect( withSelectHandler )
)( Homescreen );
