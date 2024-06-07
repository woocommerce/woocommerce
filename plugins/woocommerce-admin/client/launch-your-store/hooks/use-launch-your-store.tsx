/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

export const useLaunchYourStore = () => {
	const {
		isLoading,
		launchYourStoreEnabled,
		comingSoon,
		storePagesOnly,
		privateLink,
		shareKey,
	} = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		const allOptionResolutionsFinished =
			! hasFinishedResolution( 'getOption', [
				'woocommerce_coming_soon',
			] ) &&
			! hasFinishedResolution( 'getOption', [
				'woocommerce_store_pages_only',
			] ) &&
			! hasFinishedResolution( 'getOption', [
				'woocommerce_private_link',
			] ) &&
			! hasFinishedResolution( 'getOption', [ 'woocommerce_share_key' ] );

		return {
			isLoading: allOptionResolutionsFinished,
			comingSoon: getOption( 'woocommerce_coming_soon' ),
			storePagesOnly: getOption( 'woocommerce_store_pages_only' ),
			privateLink: getOption( 'woocommerce_private_link' ),
			shareKey: getOption( 'woocommerce_share_key' ),
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
		launchYourStoreEnabled,
	};
};
