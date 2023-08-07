/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	__experimentalUseCompletion as useCompletion,
	UseCompletionError,
} from '@woocommerce/ai';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	MIN_TITLE_LENGTH_FOR_DESCRIPTION,
	WOO_AI_PLUGIN_FEATURE_NAME,
} from '../constants';
import { StopCompletionBtn, WriteItForMeBtn } from '../components';
import { useTinyEditor } from '../hooks';
import { getPostId, recordTracksFactory } from '../utils';

const recordShortDescriptionTracks = recordTracksFactory(
	'short_description_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

// @todo: refactor this to avoid duplication with product-description-button-container.
const getApiError = ( error?: string ) => {
	switch ( error ) {
		case 'connection_error':
			return __(
				'❗ We were unable to reach the experimental service. Please check back in shortly.',
				'woocommerce'
			);
		default:
			return __(
				`❗ We encountered an issue with this experimental feature. Please check back in shortly.`,
				'woocommerce'
			);
	}
};

export function WriteShortDescriptionButtonContainer() {
	const { createWarningNotice } = useDispatch( 'core/notices' );

	const [ fetching, setFetching ] = useState< boolean >( false );
	const tinyEditor = useTinyEditor();
	// @todo: does this actually work?
	const shortTinyEditor = useTinyEditor( 'excerpt' );

	const handleUseCompletionError = ( err: UseCompletionError ) => {
		createWarningNotice( getApiError( err.code ?? '' ) );
		setFetching( false );
		// eslint-disable-next-line no-console
		console.error( err );
	};

	const { requestCompletion, completionActive, stopCompletion } =
		useCompletion( {
			feature: WOO_AI_PLUGIN_FEATURE_NAME,
			onStreamMessage: ( content ) => {
				shortTinyEditor.setContent( content );
			},
			onStreamError: handleUseCompletionError,
			onCompletionFinished: ( reason, content ) => {
				recordShortDescriptionTracks( 'stop', {
					reason,
					character_count: content.length,
				} );

				setFetching( false );
			},
		} );

	useEffect( () => {
		recordShortDescriptionTracks( 'view_button' );
	}, [] );

	// @todo: what should be the minimum description length rather than hard-coded 50? add a new constant.
	const writeItForMeEnabled =
		! fetching && tinyEditor.getContent().length >= 0;

	const buildPrompt = (): string => {
		return [
			'Please write a high-converting Meta Description for the WooCommerce product description below.',
			'It should strictly adhere to the following guidelines:',
			'It should entice someone from a search results page to click on the product link.',
			'It should be no more than 155 characters so that the entire meta description fits within the space provided by the search engine result without being cut off or truncated.',
			'It should explain what users will see if they click on the product page link.',
			'Do not wrap in double quotes or use any other special characters.',
			'It should include the target keyword for the product.',
			`Here is the full product description: \n${ tinyEditor.getContent() }`,
		].join( '\n' );
	};

	const onWriteItForMeClick = async () => {
		setFetching( true );

		const prompt = buildPrompt();
		recordShortDescriptionTracks( 'start', {
			prompt,
		} );

		try {
			await requestCompletion( prompt );
		} catch ( err ) {
			handleUseCompletionError( err as UseCompletionError );
		}
	};

	return completionActive ? (
		<StopCompletionBtn onClick={ stopCompletion } />
	) : (
		<WriteItForMeBtn
			disabled={ ! writeItForMeEnabled }
			onClick={ onWriteItForMeClick }
		/>
	);
}
