/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { Icon, moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

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
	return (
		<div className="woocommerce-lys-status">
			<div className="woocommerce-lys-status-pill-wrapper">
				<div className="woocommerce-lys-status-pill">
					<span>Coming soon</span>
					<Icon icon={ moreVertical } size={ 18 } />
				</div>
			</div>
		</div>
	);
};
