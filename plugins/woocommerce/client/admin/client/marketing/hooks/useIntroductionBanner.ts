/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

type UseIntroductionBanner = {
	loading: boolean;
	isIntroductionBannerDismissed: boolean;
	dismissIntroductionBanner: () => void;
};

const OPTION_NAME_BANNER_DISMISSED =
	'woocommerce_marketing_overview_multichannel_banner_dismissed';
const OPTION_VALUE_YES = 'yes';

export const useIntroductionBanner = (): UseIntroductionBanner => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const dismissIntroductionBanner = () => {
		updateOptions( {
			[ OPTION_NAME_BANNER_DISMISSED ]: OPTION_VALUE_YES,
		} );
		recordEvent( 'marketing_multichannel_banner_dismissed', {} );
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
		isIntroductionBannerDismissed: data === OPTION_VALUE_YES,
		dismissIntroductionBanner,
	};
};
