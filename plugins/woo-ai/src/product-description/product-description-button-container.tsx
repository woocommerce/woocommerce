/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';
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
import { useStoreBranding } from '../hooks/useStoreBranding';

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
	const { data: brandingData, isError: isBrandingError } = useStoreBranding();
	const { createErrorNotice } = useDispatch( noticesStore );
	const [ brandingErrorDismissed, setBrandingErrorDismissed ] =
		useState< boolean >( false );
	if ( isBrandingError && ! brandingErrorDismissed ) {
		createErrorNotice(
			__(
				'Error fetching branding data, content generation may be degraded.',
				'woocommerce'
			),
			{
				id: 'woo-ai-branding-error',
				isDismissible: true,
				type: 'snackbar',
				onDismiss: () => setBrandingErrorDismissed( true ),
			}
		);
	}

	const tinyEditor = useTinyEditor();

	const handleCompletionError = ( error: UseCompletionError ) =>
		tinyEditor.setContent( getApiError( error.code ?? '' ) );

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
			onStreamError: handleCompletionError,
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

		const instructions = [
			`Compose an engaging product description for a product named "${ productName.slice(
				0,
				MAX_TITLE_LENGTH
			) }."`,
			`Use a 9th grade reading level.`,
			`Make the description ${ DESCRIPTION_MAX_LENGTH } words or less.`,
			'Structure the description into paragraphs using standard HTML <p> tags.',
			'Identify the language used in this product title and use the same language in your response.',
			'Only if appropriate, use <ul> and <li> tags to list product features.',
			'When appropriate, use <strong> and <em> tags to emphasize text.',
			'Do not include a top-level heading at the beginning description.',
		];

		if ( brandingData?.toneOfVoice ) {
			instructions.push(
				`Use a ${ brandingData.toneOfVoice } tone of voice.`
			);
		}

		if ( brandingData?.businessDescription ) {
			instructions.push(
				`For more context on the business, refer to the following business description: "${ brandingData.businessDescription }."`
			);
		}

		return instructions.join( '\n' );
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
		} catch ( err ) {
			handleCompletionError( err as UseCompletionError );
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
