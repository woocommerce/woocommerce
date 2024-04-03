/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { get } from 'lodash';

export const useLaunchYourStore = () => {
	const {
		isLoading,
		launchStatus,
		launchYourStoreEnabled,
		comingSoon,
		storePagesOnly,
		privateLink,
		shareKey,
		tourDismissed,
	} = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		const allOptionResolutionsFinished =
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
			] ) &&
			! hasFinishedResolution( 'getOption', [
				'woocommerce_lys_tour_dismissed',
			] );

		return {
			isLoading: allOptionResolutionsFinished,
			launchStatus: getOption( 'launch-status' ),
			comingSoon: getOption( 'woocommerce_coming_soon' ),
			storePagesOnly: getOption( 'woocommerce_store_pages_only' ),
			privateLink: getOption( 'woocommerce_private_link' ),
			shareKey: getOption( 'woocommerce_share_key' ),
			tourDismissed: getOption( 'woocommerce_lys_tour_dismissed' ),
			launchYourStoreEnabled:
				window.wcAdminFeatures[ 'launch-your-store' ],
		};
	} );

	return {
		isLoading,
		comingSoon,
		storePagesOnly,
		privateLink,
		shareKey,
		launchStatus,
		launchYourStoreEnabled,
		tourDismissed,
	};
};
