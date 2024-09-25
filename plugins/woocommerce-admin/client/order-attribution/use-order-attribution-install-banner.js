/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

const OPTION_NAME_BANNER_DISMISSED =
	'woocommerce_order_attribution_install_banner_dismissed';
const OPTION_VALUE_YES = 'yes';

const useOrderAttributionInstallBanner = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const dismissOrderAttributionInstallBanner = () => {
		updateOptions( {
			[ OPTION_NAME_BANNER_DISMISSED ]: OPTION_VALUE_YES,
		} );
		recordEvent( 'order_attribution_install_banner_dismissed', {} );
	};

	const { loading, data } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			loading: ! hasFinishedResolution( 'getOption', [
				OPTION_NAME_BANNER_DISMISSED,
			] ),
			data: getOption( OPTION_NAME_BANNER_DISMISSED ),
		};
	}, [] );

	return {
		loading,
		isOrderAttributionInstallBannerDismissed: data === OPTION_VALUE_YES,
		dismissOrderAttributionInstallBanner,
	};
};

export default useOrderAttributionInstallBanner;
