/**
 * External dependencies
 */
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME } from '../../constants';

export const useFeedbackBar = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const { shouldShowFeedbackBar } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const showFeedbackBarOption = getOption(
			PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME
		) as string;

		const resolving = ! hasFinishedResolution( 'getOption', [
			PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME,
		] );

		return {
			shouldShowFeedbackBar:
				! resolving &&
				window.wcTracks?.isEnabled &&
				showFeedbackBarOption === 'yes',
		};
	}, [] );

	const showFeedbackBar = () => {
		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'yes',
		} );
	};

	const getOptions = async () => {
		const { getOption } = resolveSelect( OPTIONS_STORE_NAME );

		const showFeedbackBarOption = ( await getOption(
			PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME
		) ) as string;

		return {
			showFeedbackBarOption,
		};
	};

	const maybeShowFeedbackBar = async () => {
		const { showFeedbackBarOption } = await getOptions();

		if ( window.wcTracks?.isEnabled && showFeedbackBarOption !== 'no' ) {
			showFeedbackBar();
		}
	};

	const hideFeedbackBar = () => {
		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'no',
		} );
	};

	return {
		shouldShowFeedbackBar,
		maybeShowFeedbackBar,
		hideFeedbackBar,
	};
};
