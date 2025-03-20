/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect, type select as WCDataSelector } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { onboardingStore, withOnboardingHydration } from '@woocommerce/data';
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

	// @ts-expect-error -- TODO: convert Layout to TS component
	return <Layout query={ query } />;
};

const onboardingData = getAdminSetting( 'onboarding', {} );

const withSelectHandler = ( select: typeof WCDataSelector ) => {
	const { getProfileItems, hasFinishedResolution } =
		select( onboardingStore );

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
