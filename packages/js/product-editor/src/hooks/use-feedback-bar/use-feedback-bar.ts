/**
 * External dependencies
 */
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import {
	ALLOW_TRACKING_OPTION_NAME,
	SHOWN_FOR_ACTIONS_OPTION_NAME,
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

	const { shouldShowFeedbackBar } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const showFeedbackBarOption = getOption(
			PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME
		) as string;

		const shownForActions =
			( getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) as string[] ) || [];

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';

		const resolving =
			! hasFinishedResolution( 'getOption', [
				SHOWN_FOR_ACTIONS_OPTION_NAME,
			] ) ||
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
				! shownForActions.includes(
					PRODUCT_EDITOR_FEEDBACK_CES_ACTION
				) &&
				showFeedbackBarOption === 'yes',
			resolving,
		};
	} );

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
		const shownForActions =
			( ( await getOption(
				SHOWN_FOR_ACTIONS_OPTION_NAME
			) ) as string[] ) || [];

		const allowTrackingOption = await getOption(
			ALLOW_TRACKING_OPTION_NAME
		);

		return {
			showFeedbackBarOption,
			shownForActions,
			allowTrackingOption,
		};
	};

	const maybeShowFeedbackBar = async () => {
		const { allowTrackingOption, shownForActions, showFeedbackBarOption } =
			await getOptions();

		if (
			allowTrackingOption === 'yes' &&
			! shownForActions.includes( PRODUCT_EDITOR_FEEDBACK_CES_ACTION ) &&
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
