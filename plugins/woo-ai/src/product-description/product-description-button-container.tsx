// declare window._tkq as a global var
declare global {
	interface Window {
		_tkq: any;
	}
}
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

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

type WPAPIError = {
	code: string;
	message: string;
	data: {
		status: number;
	};
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
		const title = titleEl.current;
		const titleKeyupHandler = ( e: KeyboardEvent ) =>
			setProductTitle( ( e.target as HTMLInputElement ).value || '' );

		title?.addEventListener( 'keyup', titleKeyupHandler );

		return () => {
			title?.removeEventListener( 'keyup', titleKeyupHandler );
		};
	}, [ titleEl ] );

	useEffect( () => {
		clearInterval( generatingContentPhraseInterval.current );

		if ( fetching ) {
			tinyEditor.setContent( getGeneratingContentPhrase() );
			generatingContentPhraseInterval.current = setInterval(
				() => tinyEditor.setContent( getGeneratingContentPhrase() ),
				2000
			);
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

	const getApiError = ( status?: number ) => {
		const errorMessagesByStatus: Record< number, string > = {
			429: __(
				'There have been too many requests. Please wait for a few minutes and try again.',
				'woocommerce'
			),
			408: __(
				'It seems the server is taking too long to respond. This is an experimental feature, so please try again later.',
				'woocommerce'
			),
		};

		if ( status && errorMessagesByStatus[ status ] ) {
			return errorMessagesByStatus[ status ];
		}

		return __(
			`Apologies, this is an experimental feature and there was an error with this service. Please try again.`,
			'woocommerce'
		);
	};

	const generateDescription = async () => {
		const prompt = buildPrompt();
		recordEvent( 'woo_ai_product_description_generator_button_click', {
			prompt,
		} );
		try {
			setFetching( true );
			const response = await apiFetch< WooApiResponse >( {
				path: '/wooai/text-generation',
				method: 'POST',
				data: {
					prompt,
				},
			} );
			tinyEditor.setContent( response.generated_text || '' );
		} catch ( e ) {
			if ( ! ( e as WPAPIError )?.data?.status ) {
				tinyEditor.setContent( getApiError() );
			}

			const apiError = getApiError( ( e as WPAPIError ).data.status );

			tinyEditor.setContent(
				apiError
			);
			/* eslint-disable no-console */
			console.error( e );
			recordEvent( 'woo_ai_product_description_generator_failed', {
				prompt,
				raw_error: e,
				api_error: apiError,
			} );
		}
		setFetching( false );
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
			onClick={ generateDescription }
		>
			<img src={ MagicIcon } alt="magic button icon" />
			{ __( 'Write it for me', 'woocommerce' ) }
		</button>
	);
}
