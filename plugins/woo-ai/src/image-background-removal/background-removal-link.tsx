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
import {
	uploadImageToLibrary,
	getCurrentAttachmentDetails,
} from './image_utils';
import { getPostId, recordTracksFactory } from '../utils';

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

const recordTracks = recordTracksFactory( 'image_background_removal', () => ( {
	post_id: getPostId(),
} ) );

export const BackgroundRemovalLink = () => {
	const { fetchImage } = useBackgroundRemoval();
	const [ state, setState ] = useState< '' | 'generating' | 'uploading' >(
		''
	);
	const [ displayError, setDisplayError ] = useState< string | null >( null );
	const [ viewed, setViewed ] = useState< boolean >( false );

	useEffect( () => {
		if ( ! viewed ) {
			setViewed( true );
			recordTracks( 'view_ui' );
		}
	}, [ viewed ] );

	const onRemoveBackgroundClick = async () => {
		try {
			recordTracks( 'click' );

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

			recordTracks( 'complete' );
		} catch ( err ) {
			//eslint-disable-next-line no-console
			console.error( err );
			const errorCode = ( err as { code?: string } )?.code ?? '';
			setDisplayError( getErrorMessage( errorCode ) );

			recordTracks( 'error', {
				error: errorCode,
			} );
		} finally {
			setState( '' );
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
