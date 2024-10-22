/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import {
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	useUser,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getPath } from '@woocommerce/navigation';

const OPTION_NAME_BANNER_DISMISSED =
	'woocommerce_order_attribution_install_banner_dismissed';
const OPTION_VALUE_YES = 'yes';

/**
 * A utility hook designed specifically for the order attribution install banner,
 * which determines if the banner should be displayed, checks if it has been dismissed, and provides a function to dismiss it.
 */
export const useOrderAttributionInstallBanner = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { currentUserCan } = useUser();

	const dismiss = ( eventContext = 'analytics-overview' ) => {
		updateOptions( {
			[ OPTION_NAME_BANNER_DISMISSED ]: OPTION_VALUE_YES,
		} );
		recordEvent( 'order_attribution_install_banner_dismissed', {
			path: getPath(),
			context: eventContext,
		} );
	};

	const { canUserInstallPlugins, orderAttributionInstallState } = useSelect(
		( select ) => {
			const { getPluginInstallState } = select( PLUGINS_STORE_NAME );
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

	const { loading, isBannerDismissed } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			loading: ! hasFinishedResolution( 'getOption', [
				OPTION_NAME_BANNER_DISMISSED,
			] ),
			isBannerDismissed: getOption( OPTION_NAME_BANNER_DISMISSED ),
		};
	}, [] );

	return {
		loading,
		isDismissed: isBannerDismissed === OPTION_VALUE_YES,
		dismiss,
		shouldShowBanner:
			! loading &&
			canUserInstallPlugins &&
			! [ 'installed', 'activated' ].includes(
				orderAttributionInstallState
			),
	};
};
