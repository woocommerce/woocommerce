/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useTinyEditor } from '../hooks/useTinyEditor';
import MagicIcon from '../../assets/images/icons/magic.svg';

type WooApiMessage = {
	role: string;
	content: string;
};

type WooApiResponse = {
	generated_text: string;
	previous_messages: WooApiMessage[];
};

const generatingContentPhrases = [
	__( 'Please wait…', 'woocommerce' ),
	__( 'Just a little longer…', 'woocommerce' ),
	__( 'Almost there…', 'woocommerce' ),
];

const getGeneratingContentPhrase = () =>
	generatingContentPhrases[
		Math.floor( Math.random() * generatingContentPhrases.length )
	];

const DESCRIPTION_MAX_LENGTH = 300;
const MIN_TITLE_LENGTH = 20;

export function WriteItForMeButtonContainer() {
	const [ fetching, setFetching ] = useState( false );
	const generatingContentPhraseInterval = useRef< number >();
	const titleEl = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	const [ productTitle, setProductTitle ] = useState< string >(
		titleEl.current?.value || ''
	);
	const tinyEditor = useTinyEditor();

	const writeItForMeDisabled =
		fetching || ! productTitle || productTitle.length < MIN_TITLE_LENGTH;

	useEffect( () => {
		titleEl.current?.addEventListener( 'keyup', ( e ) =>
			setProductTitle( ( e.target as HTMLInputElement ).value || '' )
		);
	}, [ titleEl ] );

	useEffect( () => {
		if ( fetching ) {
			tinyEditor.setContent( getGeneratingContentPhrase() );
			generatingContentPhraseInterval.current = setInterval(
				() => tinyEditor.setContent( getGeneratingContentPhrase() ),
				2000
			);
		} else {
			clearInterval( generatingContentPhraseInterval.current );
		}
	}, [ fetching ] );

	const buildPrompt = () => {
		const instructions = [
			`Write a product description with the following product title: "${ productTitle }."`,
			'Use a 9th grade reading level.',
			`Make the description ${ DESCRIPTION_MAX_LENGTH } words or less.`,
			'Structure the description using standard HTML paragraph, strong and list tags.',
			'Do not include a heading at the very top of the description.',
		];

		return instructions.join( '\n' );
	};

	return (
		<button
			className="button wp-media-button woo-ai-write-it-for-me-btn"
			type="button"
			disabled={ writeItForMeDisabled }
			title={
				writeItForMeDisabled
					? sprintf(
							/* translators: %d: Minimum characters for product title */
							__(
								'Please create a product title before generating a description. It must be %d characters or longer.',
								'woocommerce'
							),
							MIN_TITLE_LENGTH
					  )
					: undefined
			}
			onClick={ async () => {
				try {
					setFetching( true );
					const response = await apiFetch< WooApiResponse >( {
						path: '/wooai/text-generation',
						method: 'POST',
						data: {
							prompt: buildPrompt(),
						},
					} );
					tinyEditor.setContent( response.generated_text || '' );
				} catch ( e ) {
					tinyEditor.setContent(
						__(
							'Error encountered, please try again.',
							'woocommerce'
						)
					);
					/* eslint-disable no-console */
					console.error( e );
				}
				setFetching( false );
			} }
		>
			<img src={ MagicIcon } alt="magic button icon" />
			{ __( 'Write it for me', 'woocommerce' ) }
		</button>
	);
}
