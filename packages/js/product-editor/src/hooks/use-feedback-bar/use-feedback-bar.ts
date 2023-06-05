/**
 * External dependencies
 */
import { resolveSelect, useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME } from '../../constants';

async function wasFeedbackBarPreviouslyHidden(): Promise< boolean > {
	const optionValue: string = await resolveSelect(
		OPTIONS_STORE_NAME
	).getOption( PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME );
	return optionValue === 'no';
}

export const useFeedbackBar = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const showFeedbackBar = () => {
		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'yes',
		} );
	};

	const hideFeedbackBar = () => {
		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'no',
		} );
	};

	const onSaveDraft = async () => {
		if ( ( await wasFeedbackBarPreviouslyHidden() ) === false ) {
			showFeedbackBar();
		}
	};

	const onPublish = async () => {
		if ( ( await wasFeedbackBarPreviouslyHidden() ) === false ) {
			showFeedbackBar();
		}
	};

	return { onSaveDraft, onPublish, showFeedbackBar, hideFeedbackBar };
};
