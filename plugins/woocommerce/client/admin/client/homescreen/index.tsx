/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { useDispatch, withSelect } from '@wordpress/data';
import type { SelectFunction } from '@wordpress/data/build-types/types';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { onboardingStore, withOnboardingHydration } from '@woocommerce/data';
import { getHistory, getNewPath, useQuery } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Layout from './layout';
import { getAdminSetting } from '~/utils/admin-settings';
import { STORE_LOCATION_NOT_SAVED_QUERY_ARG } from '~/core-profiler/utils/store-location-not-saved';

type HomescreenProps = ReturnType< typeof withSelectHandler > & {
	hasFinishedResolution: boolean;
};

const onboardingData = getAdminSetting( 'onboarding', {} );

// The preloaded profile does not depend on the REST API. When the profile request fails
// before hydration runs, the store only holds empty defaults.
const isProfilerDoneOnPageLoad = () =>
	!! ( onboardingData.profile?.completed || onboardingData.profile?.skipped );

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
			! profilerSkipped &&
			! isProfilerDoneOnPageLoad()
		) {
			getHistory().push( getNewPath( {}, '/setup-wizard', {} ) );
		}
	}, [ hasFinishedResolution, profilerCompleted, profilerSkipped ] );

	const query = useQuery();
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const storeLocationNotSaved =
		!! query[ STORE_LOCATION_NOT_SAVED_QUERY_ARG ];

	useEffect( () => {
		if ( ! storeLocationNotSaved ) {
			return;
		}
		createErrorNotice(
			__(
				'Your store location and currency were not saved. Set them in the general settings, since taxes and prices depend on them.',
				'woocommerce'
			),
			{
				id: STORE_LOCATION_NOT_SAVED_QUERY_ARG,
				explicitDismiss: true,
				actions: [
					{
						label: __( 'Go to settings', 'woocommerce' ),
						url: getAdminLink(
							'admin.php?page=wc-settings&tab=general'
						),
					},
				],
			}
		);
		// Drop the flag so a reload does not repeat the notice.
		getHistory().replace(
			getNewPath(
				{ [ STORE_LOCATION_NOT_SAVED_QUERY_ARG ]: undefined },
				'/',
				query
			)
		);
	}, [ storeLocationNotSaved, createErrorNotice, query ] );

	// @ts-expect-error -- TODO: convert Layout to TS component
	return <Layout query={ query } />;
};

const withSelectHandler = ( select: SelectFunction ) => {
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
