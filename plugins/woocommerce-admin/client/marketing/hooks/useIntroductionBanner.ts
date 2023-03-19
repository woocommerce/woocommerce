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

const OPTION_NAME =
	'woocommerce_marketing_overview_multichannel_banner_dismissed';
const OPTION_VALUE = 'yes';

export const useIntroductionBanner = (): UseIntroductionBanner => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const dismissIntroductionBanner = () => {
		updateOptions( {
			[ OPTION_NAME ]: OPTION_VALUE,
		} );
		recordEvent( 'marketing_multichannel_banner_dismissed', {} );
	};

	return useSelect( ( select ) => {
		const { getOption, isOptionsUpdating, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		const isUpdateRequesting = isOptionsUpdating();

		return {
			loading: ! hasFinishedResolution( 'getOption', [ OPTION_NAME ] ),
			isIntroductionBannerDismissed:
				getOption( OPTION_NAME ) === OPTION_VALUE || isUpdateRequesting,
			dismissIntroductionBanner,
		};
	}, [] );
};
