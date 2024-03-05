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

			const isLoading =
				! hasFinishedResolution( 'getOption', [ 'launch-status' ] ) &&
				! hasFinishedResolution( 'getOption', [
					'woocommerce_coming_soon',
				] ) &&
				! hasFinishedResolution( 'getOption', [
					'woocommerce_store_pages_only',
				] ) &&
				! hasFinishedResolution( 'getOption', [
					'woocommerce_private_link',
				] ) &&
				! hasFinishedResolution( 'getOption', [
					'woocommerce_share_key',
				] );

			return {
				isLoading,
				launchStatus: getOption( 'launch-status' ),
				comingSoon: getOption( 'woocommerce_coming_soon' ),
				storePagesOnly: getOption( 'woocommerce_store_pages_only' ),
				privateLink: getOption( 'woocommerce_private_link' ),
				shareKey: getOption( 'woocommerce_share_key' ),
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
