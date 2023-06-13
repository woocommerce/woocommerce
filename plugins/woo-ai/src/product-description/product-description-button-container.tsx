/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	MAX_TITLE_LENGTH,
	MIN_TITLE_LENGTH_FOR_DESCRIPTION,
} from '../constants';
import { WriteItForMeBtn, StopCompletionBtn } from '../components';
import { useTinyEditor, useCompletion, useFeedbackSnackbar } from '../hooks';
import { recordTracksFactory, getPostId } from '../utils';

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
	const { showSnackbar, removeSnackbar } = useFeedbackSnackbar();
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

				tinyEditor.setContent( getApiError( error ) );
			},
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

	const writeItForMeEnabled =
		! fetching && productTitle.length >= MIN_TITLE_LENGTH_FOR_DESCRIPTION;

	const buildPrompt = () => {
		const instructions = [
			`Write a product description with the following product title: "${ productTitle.slice(
				0,
				MAX_TITLE_LENGTH
			) }."`,
			'Identify the language used in this product title and use the same language in your response.',
			'Use a 9th grade reading level.',
			`Make the description ${ DESCRIPTION_MAX_LENGTH } words or less.`,
			'Structure the description into paragraphs using standard HTML <p> tags.',
			'Only if appropriate, use <ul> and <li> tags to list product features.',
			'When appropriate, use <strong> and <em> tags to emphasize text.',
			'Do not include a top-level heading at the beginning description.',
		];

		return instructions.join( '\n' );
	};

	const onWriteItForMeClick = () => {
		setFetching( true );
		removeSnackbar();

		const prompt = buildPrompt();
		recordDescriptionTracks( 'start', {
			prompt,
		} );
		requestCompletion( prompt );
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
