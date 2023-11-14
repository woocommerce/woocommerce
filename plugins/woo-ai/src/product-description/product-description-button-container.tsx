/**
 * External dependencies
 */
import { useDispatch, select } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { store as preferencesStore } from '@wordpress/preferences';
import { __, sprintf } from '@wordpress/i18n';
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
	DESCRIPTION_MAX_LENGTH,
	WOO_AI_PLUGIN_FEATURE_NAME,
} from '../constants';
import {
	StopCompletionBtn,
	WriteItForMeBtn,
	TourSpotlight,
} from '../components';
import { useFeedbackSnackbar, useStoreBranding, useTinyEditor } from '../hooks';
import {
	getProductName,
	getPostId,
	getCategories,
	getTags,
	getAttributes,
	recordTracksFactory,
} from '../utils';
import { Attribute } from '../utils/types';
import { translateApiErrors as getApiError } from '../utils/apiErrors';
import { buildShortDescriptionPrompt } from '../product-short-description/product-short-description-button-container';

const preferenceId = 'modalDismissed-shortDescriptionGenerated';

const recordDescriptionTracks = recordTracksFactory(
	'description_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

export function WriteItForMeButtonContainer() {
	const { createWarningNotice } = useDispatch( 'core/notices' );

	const titleEl = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	const [ fetching, setFetching ] = useState< boolean >( false );
	const [ shortDescriptionGenerated, setShortDescriptionGenerated ] =
		useState< boolean >( false );
	const [ productTitle, setProductTitle ] = useState< string >(
		titleEl.current?.value || ''
	);

	const hasBeenDismissedBefore = select( preferencesStore ).get(
		'woo-ai-plugin',
		preferenceId
	);
	const { set } = useDispatch( preferencesStore );

	const { createErrorNotice } = useDispatch( noticesStore );
	const [ errorNoticeDismissed, setErrorNoticeDismissed ] = useState( false );
	const { data: brandingData } = useStoreBranding( {
		onError: () => {
			if ( ! errorNoticeDismissed ) {
				createErrorNotice(
					__(
						'Error fetching branding data, content generation may be degraded.',
						'woocommerce'
					),
					{
						id: 'woo-ai-branding-error',
						type: 'snackbar',
						isDismissible: true,
						onDismiss: () => setErrorNoticeDismissed( true ),
					}
				);
			}
		},
	} );

	const tinyEditor = useTinyEditor();
	const shortTinyEditor = useTinyEditor( 'excerpt' );

	const { showSnackbar, removeSnackbar } = useFeedbackSnackbar();

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
				// This prevents printing out incomplete HTML tags.
				const ignoreRegex = new RegExp( /<\/?\w*[^>]*$/g );
				if ( ! ignoreRegex.test( content ) ) {
					tinyEditor.setContent( content );
				}
			},
			onStreamError: handleUseCompletionError,
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
		onStreamError: handleUseCompletionError,
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

		// We have to keep track of manually typing, pasting, undo/redo, and when description is generated.
		const eventsToTrack = [ 'keyup', 'change', 'undo', 'redo', 'paste' ];
		for ( const event of eventsToTrack ) {
			title?.addEventListener( event, updateTitleHandler );
		}

		return () => {
			for ( const event of eventsToTrack ) {
				title?.removeEventListener( event, updateTitleHandler );
			}
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
			includedProps.push( 'tags' );
		}
		productAttributes.forEach( ( { name, values } ) => {
			productPropsInstructions.push(
				`${ name }: ${ values.join( ', ' ) }.`
			);
			includedProps.push( name );
		} );

		// WooCommerce doesn't set a limit for the product title. Set a limit to control the token usage.
		const truncatedProductName = productName.slice( 0, MAX_TITLE_LENGTH );

		const instructions = [
			`Compose an engaging product description for a product named "${ truncatedProductName }."`,
			...productPropsInstructions,
			`Use a 9th grade reading level.`,
			`Ensure the description is concise, containing no more than ${ DESCRIPTION_MAX_LENGTH } words.`,
			'Structure the content into paragraphs using <p> tags, and use HTML elements like <strong> and <em> for emphasis.',
			'Identify the language used in the product name, and craft the description in the same language.',
			'Only if appropriate, use <ul> and <li> tags to list product features.',
			'Do not include a top-level heading at the beginning of the description.',
		];

		if ( includedProps.length > 0 ) {
			instructions.push(
				`Avoid including the properties (${ includedProps.join(
					', '
				) }) directly in the description, but utilize them to create an engaging and enticing portrayal of the product.`
			);
		}

		if (
			brandingData?.toneOfVoice &&
			brandingData?.toneOfVoice !== 'neutral'
		) {
			instructions.push(
				`Generate the description using a ${ brandingData.toneOfVoice } tone.`
			);
		}

		if ( brandingData?.businessDescription ) {
			instructions.push(
				`For more context on the business, refer to the following business description: "${ brandingData.businessDescription }"`
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
			const longDescription = tinyEditor.getContent();
			if ( ! shortTinyEditor.getContent() || shortDescriptionGenerated ) {
				const shortDescriptionPrompt =
					buildShortDescriptionPrompt( longDescription );
				await requestShortCompletion( shortDescriptionPrompt );
				setShortDescriptionGenerated( true );
			}
		} catch ( err ) {
			handleUseCompletionError( err as UseCompletionError );
		}
	};

	return completionActive ? (
		<StopCompletionBtn onClick={ stopCompletion } />
	) : (
		<>
			<WriteItForMeBtn
				disabled={ ! writeItForMeEnabled }
				onClick={ onWriteItForMeClick }
				disabledMessage={ sprintf(
					/* translators: %d: Message shown when short description button is disabled because of a minimum description length */
					__(
						'Please create a product title before generating a description. It must be at least %d characters long.',
						'woocommerce'
					),
					MIN_TITLE_LENGTH_FOR_DESCRIPTION
				) }
			/>
			{ shortDescriptionGenerated && ! hasBeenDismissedBefore && (
				<TourSpotlight
					id="shortDescriptionGenerated"
					reference="#postexcerpt"
					// message should be translatable.
					description={ __(
						'The short description was automatically generated by AI using the long description. This normally appears at the top of your product pages.',
						'woocommerce'
					) }
					// title should also be translatable.
					title={ __( 'Short Description Generated', 'woocommerce' ) }
					placement="top"
					onDismissal={ () =>
						set( 'woo-ai-plugin', preferenceId, true )
					}
				/>
			) }
		</>
	);
}
