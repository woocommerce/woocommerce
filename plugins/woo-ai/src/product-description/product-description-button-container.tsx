/**
 * External dependencies
 */
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useTinyEditor, useCompletion } from '../hooks';
import MagicIcon from '../../assets/images/icons/magic.svg';

const DESCRIPTION_MAX_LENGTH = 300;
const MIN_TITLE_LENGTH = 20;

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

export function WriteItForMeButtonContainer() {
	const titleEl = useRef< HTMLInputElement >(
		document.querySelector( '#title' )
	);
	const [ fetching, setFetching ] = useState< boolean >( false );
	const [ productTitle, setProductTitle ] = useState< string >(
		titleEl.current?.value || ''
	);
	const tinyEditor = useTinyEditor();
	const { requestCompletion, completionActive, stopCompletion } =
		useCompletion( {
			onStreamMessage: ( content ) => {
				// This prevents printing out incomplete HTML tags.
				const ignoreRegex = new RegExp( /<\/?\w*[^>]*$/g );
				if ( ! ignoreRegex.test( content ) ) {
					tinyEditor.setContent( content );
				}
			},
			onStreamError: ( error ) => {
				// eslint-disable-next-line no-console
				console.debug( 'Streaming error encountered', error );

				tinyEditor.setContent( getApiError() );
			},
			onCompletionFinished: () => {
				setFetching( false );
			},
		} );

	useEffect( () => {
		const title = titleEl.current;
		const titleKeyupHandler = ( e: KeyboardEvent ) =>
			setProductTitle( ( e.target as HTMLInputElement ).value || '' );

		title?.addEventListener( 'keyup', titleKeyupHandler );

		return () => {
			title?.removeEventListener( 'keyup', titleKeyupHandler );
		};
	}, [ titleEl ] );

	const writeItForMeDisabled =
		fetching || ! productTitle || productTitle.length < MIN_TITLE_LENGTH;

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

	if ( completionActive ) {
		return (
			<button
				className="button wp-media-button woo-ai-write-it-for-me-btn"
				type="button"
				title={ __(
					'Stop generating the description.',
					'woocommerce'
				) }
				onClick={ () => {
					stopCompletion();
				} }
			>
				<img src={ MagicIcon } alt="" />
				{ __( 'Stop writing…', 'woocommerce' ) }
			</button>
		);
	}

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
			onClick={ () => {
				setFetching( true );
				requestCompletion( buildPrompt() );
			} }
		>
			<img src={ MagicIcon } alt="" />
			{ __( 'Write it for me', 'woocommerce' ) }
		</button>
	);
}
