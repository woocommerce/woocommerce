/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	ONBOARDING_STORE_NAME,
	withOnboardingHydration,
	WCDataSelector,
} from '@woocommerce/data';
import { getHistory, getNewPath, useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Layout from './layout';
import { getAdminSetting } from '~/utils/admin-settings';

type HomescreenProps = ReturnType< typeof withSelectHandler > & {
	hasFinishedResolution: boolean;
};

const Homescreen = ( {
	profileItems: {
		completed: profilerCompleted,
		skipped: profilerSkipped,
	} = {},
	hasFinishedResolution,
}: HomescreenProps ) => {
	useEffect( () => {
		if (
			hasFinishedResolution &&
			! profilerCompleted &&
			! profilerSkipped
		) {
			getHistory().push( getNewPath( {}, '/setup-wizard', {} ) );
		}
	}, [ hasFinishedResolution, profilerCompleted, profilerSkipped ] );

	const query = useQuery();
	// @ts-expect-error Layout is a pure JS component
	return <Layout query={ query } />;
};

const onboardingData = getAdminSetting( 'onboarding', {} );

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
	withOnboardingHydration( {
		profileItems: onboardingData.profile,
	} ),
	withSelect( withSelectHandler )
)( Homescreen );
