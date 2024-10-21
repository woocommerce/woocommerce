/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

type Props = {
	/** Set to false to disable this query, defaults to true to query the data */
	enabled?: boolean;
};

export const useLaunchYourStore = (
	{ enabled }: Props = {
		enabled: true,
	}
) => {
	const {
		isLoading,
		launchYourStoreEnabled,
		comingSoon,
		storePagesOnly,
		privateLink,
		shareKey,
	} = useSelect( ( select ) => {
		if ( ! enabled ) {
			return {
				isLoading: false,
				comingSoon: null,
				storePagesOnly: null,
				privateLink: null,
				shareKey: null,
				launchYourStoreEnabled: null,
			};
		}

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
