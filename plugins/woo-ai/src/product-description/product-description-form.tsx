/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

type WooApiMessage = {
	role: string;
	content: string;
};

type WooApiResponse = {
	generated_text: string;
	previous_messages: WooApiMessage[];
};
type TinyContent = { setContent: ( str: string ) => void };
declare const tinymce: { get: ( str: string ) => TinyContent };

const generatingContentPhrases = [
	__( 'Please wait…', 'woocommerce' ),
	__( 'Just a little longer…', 'woocommerce' ),
	__( 'Almost there…', 'woocommerce' ),
];

const getToneDescription = ( tone: string ) =>
	( {
		casual: __(
			'Relaxed, informal, conversational tone. Like chatting with a friend.',
			'woocommerce'
		),
		formal: __(
			'A polished, well-structured product description, adhering to professional standards.',
			'woocommerce'
		),
		flowery: __(
			'Ornate, elaborate, poetic tone. Rich in descriptive language.',
			'woocommerce'
		),
		convincing: __(
			'A persuasive, compelling product description, designed to influence decisions and drive action.',
			'woocommerce'
		),
	}[ tone ] );

const getGeneratingContentPhrase = () =>
	generatingContentPhrases[
		Math.floor( Math.random() * generatingContentPhrases.length )
	];

export function ProductDescriptionForm() {
	const [ fetching, setFetching ] = useState( false );
	const [ userProductDescription, setUserProductDescription ] =
		useState< string >( '' );
	const [ tone, setTone ] = useState< string >( 'casual' );
	const generatingContentPhraseInterval = useRef< number >();

	const setTinyContent = ( str: string ) => {
		const contentTinyMCE =
			typeof tinymce === 'object' ? tinymce.get( 'content' ) : null;

		if ( contentTinyMCE ) {
			contentTinyMCE.setContent( str );
		} else {
			{
				const el: HTMLInputElement | null = document.querySelector(
					'#wp-content-editor-container .wp-editor-area'
				);
				if ( el ) {
					el.value = str;
				}
			}
		}
	};

	useEffect( () => {
		if ( fetching ) {
			setTinyContent( getGeneratingContentPhrase() );
			generatingContentPhraseInterval.current = setInterval(
				() => setTinyContent( getGeneratingContentPhrase() ),
				2000
			);
		} else {
			clearInterval( generatingContentPhraseInterval.current );
		}
	}, [ fetching ] );

	return (
		<div className="woocommerce-gpt-integration">
			<button
				id="woocommerce-product-description-gpt-hide"
				title="Hide"
				onClick={ ( e ) => e.preventDefault() }
			></button>

			{ /* eslint-disable-next-line */ }
			<label htmlFor="woocommerce-product-description-gpt-about">
				{ __( 'About your product', 'woocommerce' ) }
			</label>
			<div
				id="woocommerce-product-description-gpt-about-wrapper"
				className="textarea-wrapper field"
			>
				<textarea
					value={ userProductDescription }
					onChange={ ( e ) =>
						setUserProductDescription( e.target.value )
					}
					id="woocommerce-product-description-gpt-about"
					placeholder=" e.g. organic and sustainable skin cleanser for women '"
				></textarea>
			</div>

			{ /* eslint-disable-next-line */ }
			<label htmlFor="woocommerce-product-description-gpt-voice-tone">
				{ __( 'Tone of voice', 'woocommerce' ) }
			</label>
			<div
				id="woocommerce-product-description-gpt-voice-tone-wrapper"
				className="select-wrapper"
			>
				<select
					id="woocommerce-product-description-gpt-voice-tone"
					className="field"
					value={ tone }
					onChange={ ( e ) => setTone( e.target.value ) }
					aria-describedby="woocommerce-product-description-gpt-voice-tone-description"
				>
					<option value="casual">Casual</option>
					<option value="formal">Formal</option>
					<option value="flowery">Flowery</option>
					<option value="convincing">Convincing</option>
				</select>
				<p id="woocommerce-product-description-gpt-voice-tone-description">
					{ getToneDescription( tone ) }
				</p>
			</div>
			<div className="woocommerce-gpt-actions-wrapper">
				<button
					id="woocommerce-product-description-gpt-action-accept"
					className="button button-primary gpt-action"
					type="button"
					disabled={ fetching }
					onClick={ async () => {
						try {
							setFetching( true );
							const response = await apiFetch< WooApiResponse >( {
								path: '/wooai/text-generation',
								method: 'POST',
								data: {
									prompt: `Write a product description with ${ tone } tone, from the following features: ${ userProductDescription }.`,
								},
							} );
							setTinyContent( response.generated_text || '' );
						} catch ( e ) {
							setTinyContent(
								__(
									'Error encountered, please try again.',
									'woocommerce'
								)
							);
						}
						setFetching( false );
					} }
				>
					{ fetching
						? __( 'Writing description…', 'woocommerce' )
						: __( 'Write description', 'woocommerce' ) }
				</button>
			</div>
		</div>
	);
}
