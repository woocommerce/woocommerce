/**
 * External dependencies
 */
import { useState, useEffect } from 'react';
import { __experimentalUseBackgroundRemoval as useBackgroundRemoval } from '@woocommerce/ai';
import { store as preferencesStore } from '@wordpress/preferences';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { useDispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import { FILENAME_APPEND, LINK_CONTAINER_ID } from './constants';
import { useFeedbackSnackbar } from '../hooks';
import { recordTracksFactory, getPostId, getProductImageCount } from '../utils';
import {
	uploadImageToLibrary,
	getCurrentAttachmentDetails,
} from './image_utils';
import { TourSpotlight } from '../components/';

const preferenceId = `spotlightDismissed-backgroundRemovalLink`;

const getErrorMessage = ( errorCode?: string ) => {
	switch ( errorCode ) {
		case 'invalid_image_file':
			return __( 'Invalid image', 'woocommerce' );
		case 'image_file_too_small':
			return __( 'Image too small', 'woocommerce' );
		case 'image_file_too_large':
			return __( 'Image too large', 'woocommerce' );
		default:
			return __( 'Something went wrong', 'woocommerce' );
	}
};

const recordBgRemovalTracks = recordTracksFactory(
	'background_removal',
	() => ( {
		post_id: getPostId(),
		image_count: getProductImageCount().total,
	} )
);

export const BackgroundRemovalLink = () => {
	const { fetchImage } = useBackgroundRemoval();
	const { showSnackbar, removeSnackbar } = useFeedbackSnackbar();
	const hasBeenDismissedBefore = select( preferencesStore ).get(
		'woo-ai-plugin',
		preferenceId
	);
	const { set } = useDispatch( preferencesStore );

	const [ state, setState ] = useState< 'none' | 'generating' | 'uploading' >(
		'none'
	);
	const [ displayError, setDisplayError ] = useState< string | null >( null );

	useEffect( () => {
		recordBgRemovalTracks( 'view_ui' );
	}, [] );

	const setSpotlightAsDismissed = () =>
		set( 'woo-ai-plugin', preferenceId, true );

	const onRemoveBackgroundClick = async () => {
		removeSnackbar();
		try {
			recordBgRemovalTracks( 'click' );

			setState( 'generating' );

			const { url: imgUrl, filename: imgFilename } =
				getCurrentAttachmentDetails();

			if ( ! imgUrl ) {
				setDisplayError( getErrorMessage() );
				return;
			}

			const originalBlob = await fetch( imgUrl ).then( ( res ) =>
				res.blob()
			);

			const bgRemoved = await fetchImage( {
				imageFile: new File( [ originalBlob ], imgFilename ?? '', {
					type: originalBlob.type,
				} ),
			} );

			setState( 'uploading' );

			await uploadImageToLibrary( {
				imageBlob: bgRemoved,
				libraryFilename: `${ imgFilename }${ FILENAME_APPEND }.${ bgRemoved.type
					.split( '/' )
					.pop() }`,
			} );

			recordBgRemovalTracks( 'complete' );

			setSpotlightAsDismissed();
			showSnackbar( {
				label: __( 'Was the generated image helpful?', 'woocommerce' ),
				onPositiveResponse: () => {
					recordBgRemovalTracks( 'feedback', {
						response: 'positive',
					} );
				},
				onNegativeResponse: () => {
					recordBgRemovalTracks( 'feedback', {
						response: 'negative',
					} );
				},
			} );
		} catch ( err ) {
			//eslint-disable-next-line no-console
			console.error( err );
			const { message: errMessage, code: errCode } = err as {
				code?: string;
				message?: string;
			};

			setDisplayError( getErrorMessage( errCode ) );

			recordBgRemovalTracks( 'error', {
				code: errCode ?? null,
				message: errMessage ?? null,
			} );
		} finally {
			setState( 'none' );
		}
	};

	if ( state === 'generating' ) {
		return <span>{ __( 'Generating…', 'woocommerce' ) }</span>;
	}

	if ( state === 'uploading' ) {
		return <span>{ __( 'Uploading…', 'woocommerce' ) }</span>;
	}

	return (
		<>
			<div className="background-link_actions">
				<button onClick={ () => onRemoveBackgroundClick() }>
					{ __( 'Remove background', 'woocommerce' ) }
				</button>
				<img src={ MagicIcon } alt="" />
			</div>
			{ ! hasBeenDismissedBefore && (
				<TourSpotlight
					id="backgroundRemovalLink"
					reference={ `#${ LINK_CONTAINER_ID }` }
					description={ __(
						'Effortlessly make your product images pop by removing the background using state-of-the-art AI technology. Just click the button and watch!',
						'woocommerce'
					) }
					title={ createInterpolateElement(
						__(
							'<NewBlock /> Remove backgrounds with AI',
							'woocommerce'
						),
						{
							NewBlock: (
								<span className="woo-ai-background-removal-link__new-block">
									{ __( 'NEW', 'woocommerce' ) }
								</span>
							),
						}
					) }
					placement="left"
					spotlightParent={
						( document.querySelector(
							`#${ LINK_CONTAINER_ID }`
						) as HTMLElement ) ?? document.body
					}
					onDismissal={ () => {
						recordBgRemovalTracks( 'spotlight_dismissed' );
						setSpotlightAsDismissed();
					} }
					onDisplayed={ () =>
						recordBgRemovalTracks( 'spotlight_displayed' )
					}
				/>
			) }
			{ displayError && (
				<Notice onRemove={ () => setDisplayError( null ) }>
					{ displayError }
				</Notice>
			) }
		</>
	);
};
