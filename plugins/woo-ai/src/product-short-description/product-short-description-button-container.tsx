/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
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
	MIN_DESC_LENGTH_FOR_SHORT_DESC,
	WOO_AI_PLUGIN_FEATURE_NAME,
} from '../constants';
import { StopCompletionBtn, WriteItForMeBtn } from '../components';
import { useTinyEditor } from '../hooks';
import { getPostId, recordTracksFactory } from '../utils';
import { translateApiErrors as getApiError } from '../utils/apiErrors';

const recordShortDescriptionTracks = recordTracksFactory(
	'short_description_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

export function buildShortDescriptionPrompt( longDesc: string ) {
	return [
		'Please write a high-converting Meta Description for the WooCommerce product description below.',
		'It should strictly adhere to the following guidelines:',
		'It should entice someone from a search results page to click on the product link.',
		'It should be no more than 155 characters so that the entire meta description fits within the space provided by the search engine result without being cut off or truncated.',
		'It should explain what users will see if they click on the product page link.',
		'Do not wrap in double quotes or use any other special characters.',
		'It should include the target keyword for the product.',
		`Here is the full product description: \n${ longDesc }`,
	].join( '\n' );
}

export function WriteShortDescriptionButtonContainer() {
	const { createWarningNotice } = useDispatch( 'core/notices' );

	const [ fetching, setFetching ] = useState< boolean >( false );
	const tinyEditor = useTinyEditor();
	const shortTinyEditor = useTinyEditor( 'excerpt' );
	const [ postContent, setPostContent ] = useState( '' );

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

	// This effect sets up the 'init' listener to update the 'isEditorReady' state
	useEffect( () => {
		const editor = tinyEditor.getEditorObject();
		if ( editor ) {
			// Set the content on initial page load.
			setPostContent( tinyEditor.getContent() );
			// Register a listener for the tinyMCE editor to update the postContent state.
			const changeHandler = () => {
				setPostContent( tinyEditor.getContent() );
			};
			editor.on( 'input', changeHandler );
			editor.on( 'change', changeHandler );
			return () => {
				editor.off( 'input', changeHandler );
				editor.off( 'change', changeHandler );
			};
		}
	}, [ tinyEditor ] );

	const writeItForMeEnabled =
		! fetching && postContent.length >= MIN_DESC_LENGTH_FOR_SHORT_DESC;
	const onWriteItForMeClick = async () => {
		setFetching( true );

		const prompt = buildShortDescriptionPrompt( tinyEditor.getContent() );
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
			disabledMessage={ sprintf(
				/* translators: %d: Message shown when short description button is disabled because of a minimum description length */
				__(
					'Please write a product description before generating a short description. It must be at least %d characters long.',
					'woocommerce'
				),
				MIN_DESC_LENGTH_FOR_SHORT_DESC
			) }
		/>
	);
}
