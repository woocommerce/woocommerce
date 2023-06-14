/**
 * External dependencies
 */
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import { useCustomerEffortScoreModal } from '@woocommerce/customer-effort-score';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME,
	PRODUCT_EDITOR_FEEDBACK_CES_ACTION,
} from '../../constants';

export const useFeedbackBar = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const { wasPreviouslyShown, isLoading: isCesModalOptionsLoading } =
		useCustomerEffortScoreModal();

	const { shouldShowFeedbackBar } = useSelect(
		( select ) => {
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
					! isCesModalOptionsLoading &&
					! wasPreviouslyShown(
						PRODUCT_EDITOR_FEEDBACK_CES_ACTION
					) &&
					showFeedbackBarOption === 'yes',
			};
		},
		[ isCesModalOptionsLoading, wasPreviouslyShown ]
	);

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

		if (
			window.wcTracks?.isEnabled &&
			! wasPreviouslyShown( PRODUCT_EDITOR_FEEDBACK_CES_ACTION ) &&
			showFeedbackBarOption !== 'no'
		) {
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
