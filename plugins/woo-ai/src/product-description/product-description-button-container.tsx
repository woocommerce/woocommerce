/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	__experimentalUseCompletion as useCompletion,
	UseCompletionError,
} from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import {
	MAX_TITLE_LENGTH,
	MIN_TITLE_LENGTH_FOR_DESCRIPTION,
	WOO_AI_PLUGIN_FEATURE_NAME,
} from '../constants';
import { StopCompletionBtn, WriteItForMeBtn } from '../components';
import { useFeedbackSnackbar, useTinyEditor } from '../hooks';
import {
	getProductName,
	getPostId,
	getCategories,
	getTags,
	getAttributes,
	recordTracksFactory,
} from '../utils';
import { Attribute } from '../utils/types';

const DESCRIPTION_MAX_LENGTH = 300;

const getApiError = ( error: string ) => {
	switch ( error ) {
		case 'connection_error':
			return __(
				'❗ We were unable to reach the experimental service. Please check back in shortly.',
				'woocommerce'
			);
		default:
			return __(
				`❗ We're currently experiencing high demand for our experimental feature. Please check back in shortly.`,
				'woocommerce'
			);
	}
};

const recordDescriptionTracks = recordTracksFactory(
	'description_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

export function WriteItForMeButtonContainer() {
	const titleEl = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	const [ fetching, setFetching ] = useState< boolean >( false );
	const [ productTitle, setProductTitle ] = useState< string >(
		titleEl.current?.value || ''
	);
	const tinyEditor = useTinyEditor();
	const shortTinyEditor = useTinyEditor( 'excerpt' );

	const { showSnackbar, removeSnackbar } = useFeedbackSnackbar();
	const { requestCompletion, completionActive, stopCompletion } =
		useCompletion( {
			feature: WOO_AI_PLUGIN_FEATURE_NAME,
			onStreamMessage: ( content ) => {
				// This prevents printing out incomplete HTML tags.
				const ignoreRegex = new RegExp( /<\/?\w*[^>]*$/g );
				if ( ! ignoreRegex.test( content ) ) {
					tinyEditor.setContent( content );
				}
			},
			onStreamError: ( error ) =>
				tinyEditor.setContent( getApiError( error.code ?? '' ) ),
			onCompletionFinished: ( reason, content ) => {
				recordDescriptionTracks( 'stop', {
					reason,
					character_count: content.length,
					current_title: productTitle,
				} );

				setFetching( false );

				if ( reason === 'finished' ) {
					showSnackbar( {
						label: __(
							'Was the AI-generated description helpful?',
							'woocommerce'
						),
						onPositiveResponse: () => {
							recordDescriptionTracks( 'feedback', {
								response: 'positive',
							} );
						},
						onNegativeResponse: () => {
							recordDescriptionTracks( 'feedback', {
								response: 'negative',
							} );
						},
					} );
				}
			},
		} );

	const { requestCompletion: requestShortCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamMessage: ( content ) => shortTinyEditor.setContent( content ),
		onStreamError: ( error ) =>
			shortTinyEditor.setContent( getApiError( error.code ?? '' ) ),
		onCompletionFinished: ( reason, content ) => {
			if ( reason === 'finished' ) {
				shortTinyEditor.setContent( content );
			}
		},
	} );

	useEffect( () => {
		const title = titleEl.current;

		const updateTitleHandler = ( e: Event ) => {
			setProductTitle(
				( e.target as HTMLInputElement ).value.trim() || ''
			);
		};

		title?.addEventListener( 'keyup', updateTitleHandler );
		title?.addEventListener( 'change', updateTitleHandler );

		return () => {
			title?.removeEventListener( 'keyup', updateTitleHandler );
			title?.removeEventListener( 'change', updateTitleHandler );
		};
	}, [ titleEl ] );

	useEffect( () => {
		recordDescriptionTracks( 'view_button' );
	}, [] );

	const writeItForMeEnabled =
		! fetching && productTitle.length >= MIN_TITLE_LENGTH_FOR_DESCRIPTION;

	const buildPrompt = (): string => {
		const productName: string = getProductName();
		const productCategories: string[] = getCategories();
		const productTags: string[] = getTags();
		const productAttributes: Attribute[] = getAttributes();

		const includedProps: string[] = [];
		const productPropsInstructions: string[] = [];
		if ( productCategories.length > 0 ) {
			productPropsInstructions.push(
				`Falling into the categories: ${ productCategories.join(
					', '
				) }.`
			);
			includedProps.push( 'categories' );
		}
		if ( productTags.length > 0 ) {
			productPropsInstructions.push(
				`Tagged with: ${ productTags.join( ', ' ) }.`
			);
			includedProps.push( 'categories' );
		}
		productAttributes.forEach( ( { name, values } ) => {
			productPropsInstructions.push(
				`${ name }: ${ values.join( ', ' ) }.`
			);
			includedProps.push( name );
		} );

		return [
			`Compose an engaging product description for a product named "${ productName.slice(
				0,
				MAX_TITLE_LENGTH
			) }".`,
			...productPropsInstructions,
			'Identify the language used in the product name, and craft the description in the same language.',
			`Ensure the description is concise, containing no more than ${ DESCRIPTION_MAX_LENGTH } words.`,
			'Structure the content into paragraphs using <p> tags, and use HTML elements like <strong> and <em> for emphasis.',
			'Only if appropriate, use <ul> and <li> for listing product features.',
			`Avoid including the properties (${ includedProps.join(
				', '
			) }) directly in the description, but utilize them to create an engaging and enticing portrayal of the product.`,
			'Do not include a top-level heading at the beginning description.',
		].join( ' ' );
	};

	const onWriteItForMeClick = async () => {
		setFetching( true );
		removeSnackbar();

		const prompt = buildPrompt();
		recordDescriptionTracks( 'start', {
			prompt,
		} );

		try {
			await requestCompletion( prompt );
			await requestShortCompletion(
				`Please provide a brief, 1-2 sentence summary of the following product description in fewer than 50 words:\n ${ tinyEditor.getContent() }`
			);
		} catch ( err ) {
			tinyEditor.setContent(
				getApiError( ( err as UseCompletionError ).code ?? '' )
			);
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
