/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

export const useLaunchYourStore = () => {
	const { isLoading, launchStatus, showLaunchYourStore } = useSelect(
		( select ) => {
			const { hasFinishedResolution, getOption } =
				select( OPTIONS_STORE_NAME );

			return {
				isLoading: ! hasFinishedResolution( 'getOption', [
					'launch-status',
				] ),
				launchStatus: getOption( 'launch-status' ),
				showLaunchYourStore:
					window.wcAdminFeatures[ 'launch-your-store' ],
			};
		}
	);

	return {
		isLoading,
		launchStatus,
		showLaunchYourStore,
	};
};

export const LaunchYourStoreStatus = () => {
	return <div>Launch Your Store Status</div>;
};
