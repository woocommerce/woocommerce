/**
 * External dependencies
 */
import { useState, useEffect } from 'react';
import { __experimentalUseBackgroundRemoval as useBackgroundRemoval } from '@woocommerce/ai';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import { FILENAME_APPEND } from './constants';
import { useFeedbackSnackbar } from '../hooks';
import { recordTracksFactory, getPostId, getProductImageCount } from '../utils';
import {
	uploadImageToLibrary,
	getCurrentAttachmentDetails,
} from './image_utils';

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

	const [ state, setState ] = useState< 'none' | 'generating' | 'uploading' >(
		'none'
	);
	const [ displayError, setDisplayError ] = useState< string | null >( null );

	useEffect( () => {
		recordBgRemovalTracks( 'view_ui' );
	}, [] );

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
			{ displayError && (
				<Notice onRemove={ () => setDisplayError( null ) }>
					{ displayError }
				</Notice>
			) }
		</>
	);
};
