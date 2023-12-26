/**
 * External dependencies
 */
import { resolveSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME } from '../../constants';

export const useFeedbackBar = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ shouldShowFeedbackBar, setShouldShowFeedbackBar ] =
		useState( false );

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

	const showFeedbackBarOnce = () => {
		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'no',
		} );
		setShouldShowFeedbackBar( true );
	};

	const fetchShowFeedbackBarOption = useCallback( () => {
		return resolveSelect( OPTIONS_STORE_NAME )
			.getOption< string >( PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME )
			.then( ( showFeedbackBarOption ) => {
				if (
					window.wcTracks?.isEnabled &&
					showFeedbackBarOption === 'yes'
				) {
					showFeedbackBarOnce();
				}
			} );
	}, [] );

	useEffect( () => {
		fetchShowFeedbackBarOption();
	}, [] );

	return {
		shouldShowFeedbackBar,
		maybeShowFeedbackBar,
	};
};
