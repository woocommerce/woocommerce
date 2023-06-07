/**
 * External dependencies
 */
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import {
	ALLOW_TRACKING_OPTION_NAME,
	useCustomerEffortScoreModal,
} from '@woocommerce/customer-effort-score';
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

			const allowTrackingOption =
				getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';

			const resolving =
				! hasFinishedResolution( 'getOption', [
					PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME,
				] ) ||
				! hasFinishedResolution( 'getOption', [
					ALLOW_TRACKING_OPTION_NAME,
				] );

			return {
				shouldShowFeedbackBar:
					! resolving &&
					allowTrackingOption === 'yes' &&
					! isCesModalOptionsLoading &&
					! wasPreviouslyShown(
						PRODUCT_EDITOR_FEEDBACK_CES_ACTION
					) &&
					showFeedbackBarOption === 'yes',
			};
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ isCesModalOptionsLoading ]
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

		const allowTrackingOption = await getOption(
			ALLOW_TRACKING_OPTION_NAME
		);

		return {
			showFeedbackBarOption,
			allowTrackingOption,
		};
	};

	const maybeShowFeedbackBar = async () => {
		const { allowTrackingOption, showFeedbackBarOption } =
			await getOptions();

		if (
			allowTrackingOption === 'yes' &&
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
