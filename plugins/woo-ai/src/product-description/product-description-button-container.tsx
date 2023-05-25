/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useTinyEditor, useCompletion } from '../hooks';
import { MIN_TITLE_LENGTH } from '../constants';
import { WriteItForMeBtn, StopCompletionBtn } from '../components';

const DESCRIPTION_MAX_LENGTH = 300;

const getPostId = () =>
	( document.querySelector( '#post_ID' ) as HTMLInputElement )?.value;

const getApiError = () => {
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
			onCompletionFinished: ( reason, content ) => {
				recordEvent( 'woo_ai_product_description_completion_stop', {
					post_id: getPostId(),
					reason,
					character_count: content.length,
				} );
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
			'Structure the description into standard paragraphs using standard HTML tags.',
			'Only if appropriate, use <ul> and <li> tags to list features.',
			'When appropriate, use <strong> and <em> tags to emphasize text.',
			'Do not include a top-level heading at the beginning description.',
		];

		return instructions.join( '\n' );
	};

	return completionActive ? (
		<StopCompletionBtn onClick={ stopCompletion } />
	) : (
		<WriteItForMeBtn
			disabled={ writeItForMeDisabled }
			onClick={ () => {
				setFetching( true );
				const prompt = buildPrompt();
				recordEvent( 'woo_ai_product_description_completion_start', {
					prompt,
					post_id: getPostId(),
				} );
				requestCompletion( prompt );
			} }
		/>
	);
}
