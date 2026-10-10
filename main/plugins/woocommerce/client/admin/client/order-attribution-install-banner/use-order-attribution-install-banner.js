/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';
import {
	optionsStore,
	pluginsStore,
	useUser,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getPath } from '@woocommerce/navigation';
import { isWcVersion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import '~/marketing/data';

const USER_META_BANNER_DISMISSED = 'order_attribution_install_banner_dismissed';
const OPTION_VALUE_YES = 'yes';
const OPTION_NAME_REMOTE_VARIANT_ASSIGNMENT =
	'woocommerce_remote_variant_assignment';

const getThreshold = ( percentages ) => {
	const defaultPercentages = [
		[ '9.7', 10 ], // 10%
		[ '9.6', 10 ], // 10%
		[ '9.5', 1 ], // 1%
	];

	if ( ! Array.isArray( percentages ) || percentages.length === 0 ) {
		percentages = defaultPercentages;
	}

	// Sort the percentages in descending order by version, to ensure we get the highest version first so the isWcVersion() check works correctly.
	// E.g. if we are on 9.7 but the percentages are in version ascending order, we would get 1% instead of 10%.
	percentages.sort( ( a, b ) => parseFloat( b[ 0 ] ) - parseFloat( a[ 0 ] ) );

	for ( let [ version, percentage ] of percentages ) {
		if ( isWcVersion( version, '>=' ) ) {
			percentage = parseInt( percentage, 10 );
			if ( isNaN( percentage ) ) {
				return 12; // Default to 10% if the percentage is not a number.
			}
			// Since remoteVariantAssignment ranges from 1 to 120, we need to convert the percentage to a number between 1 and 120.
			return ( percentage / 100 ) * 120;
		}
	}

	return 12; // Default to 10% if version is lower than 9.5
};

const shouldPromoteOrderAttribution = (
	remoteVariantAssignment,
	percentages
) => {
	remoteVariantAssignment = parseInt( remoteVariantAssignment, 10 );

	if ( isNaN( remoteVariantAssignment ) ) {
		return false;
	}

	const threshold = getThreshold( percentages );

	return remoteVariantAssignment <= threshold;
};

/**
 * A utility hook designed specifically for the order attribution install banner,
 * which determines if the banner should be displayed, checks if it has been dismissed, and provides a function to dismiss it.
 */
export const useOrderAttributionInstallBanner = ( { isInstalling } ) => {
	const { currentUserCan } = useUser();
	const {
		[ USER_META_BANNER_DISMISSED ]: bannerDismissed,
		updateUserPreferences,
	} = useUserPreferences();

	const dismiss = ( eventContext = 'analytics-overview' ) => {
		updateUserPreferences( {
			[ USER_META_BANNER_DISMISSED ]: OPTION_VALUE_YES,
		} );
		recordEvent( 'order_attribution_install_banner_dismissed', {
			path: getPath(),
			context: eventContext,
		} );
	};

	const { canUserInstallPlugins, orderAttributionInstallState } = useSelect(
		( select ) => {
			const { getPluginInstallState } = select( pluginsStore );
			const installState = getPluginInstallState(
				'woocommerce-analytics'
			);

			return {
				orderAttributionInstallState: installState,
				canUserInstallPlugins: currentUserCan( 'install_plugins' ),
			};
		},
		[ currentUserCan ]
	);

	const { loading, isBannerDismissed, remoteVariantAssignment } = useSelect(
		( select ) => {
			const { getOption, hasFinishedResolution } = select( optionsStore );

			return {
				loading: ! hasFinishedResolution( 'getOption', [
					OPTION_NAME_REMOTE_VARIANT_ASSIGNMENT,
				] ),
				isBannerDismissed: bannerDismissed,
				remoteVariantAssignment: getOption(
					OPTION_NAME_REMOTE_VARIANT_ASSIGNMENT
				),
			};
		},
		[ bannerDismissed ]
	);

	const { loadingRecommendations, recommendations } = useSelect(
		( select ) => {
			const { getMiscRecommendations, hasFinishedResolution } =
				select( STORE_KEY );

			return {
				loadingRecommendations:
					! canUserInstallPlugins ||
					! hasFinishedResolution( 'getMiscRecommendations' ),
				recommendations: canUserInstallPlugins
					? getMiscRecommendations()
					: [],
			};
		},
		[ canUserInstallPlugins ]
	);

	const percentages = useMemo( () => {
		if (
			loadingRecommendations ||
			! Array.isArray( recommendations ) ||
			recommendations.length === 0
		) {
			return null;
		}

		for ( const recommendation of recommendations ) {
			if ( recommendation.id === 'woocommerce-analytics' ) {
				return (
					recommendation?.order_attribution_promotion_percentage ||
					null
				);
			}
		}

		return null;
	}, [ loadingRecommendations, recommendations ] );

	const getShouldShowBanner = useCallback( () => {
		if ( ! canUserInstallPlugins || loading ) {
			return false;
		}

		if ( isInstalling ) {
			return true;
		}

		const isPluginInstalled = [ 'installed', 'activated' ].includes(
			orderAttributionInstallState
		);

		if ( isPluginInstalled ) {
			return false;
		}

		return shouldPromoteOrderAttribution(
			remoteVariantAssignment,
			percentages
		);
	}, [
		loading,
		canUserInstallPlugins,
		orderAttributionInstallState,
		remoteVariantAssignment,
		percentages,
		isInstalling,
	] );

	return {
		loading,
		isDismissed: isBannerDismissed === OPTION_VALUE_YES,
		dismiss,
		shouldShowBanner: getShouldShowBanner(),
	};
};
