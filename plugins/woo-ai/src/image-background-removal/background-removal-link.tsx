/**
 * External dependencies
 */
import { useState } from 'react';
import { __experimentalUseBackgroundRemoval as useBackgroundRemoval } from '@woocommerce/ai';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';
import { uploadImageToLibrary } from './image_utils';

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

export const BackgroundRemovalLink = () => {
	const { fetchImage } = useBackgroundRemoval();
	const [ state, setState ] = useState< '' | 'generating' | 'uploading' >(
		''
	);
	const [ error, setError ] = useState< string | null >( null );

	const onRemoveClick = async () => {
		try {
			setState( 'generating' );
			const imgUrl = (
				document.querySelector(
					'.attachment-details-copy-link'
				) as HTMLInputElement | null
			 )?.value;

			if ( ! imgUrl ) {
				return;
			}

			const originalFilename =
				imgUrl?.split( '/' ).pop()?.split( '.' )[ 0 ] ?? '';

			const originalBlob = await fetch( imgUrl ).then( ( res ) =>
				res.blob()
			);

			const bgRemoved = await fetchImage( {
				imageFile: new File( [ originalBlob ], originalFilename, {
					type: originalBlob.type,
				} ),
			} );

			setState( 'uploading' );

			await uploadImageToLibrary( {
				imageBlob: bgRemoved,
				libraryFilename: `${ originalFilename }_on_white.png`,
			} );
			setState( '' );
		} catch ( err ) {
			setError(
				getErrorMessage( ( error as { code?: string } )?.code ?? '' )
			);
		}
	};

	if ( error ) {
		return <span className="background-removal-error">{ error }</span>;
	}

	if ( state === 'generating' ) {
		return <span>{ __( 'Generating…', 'woocommerce' ) }</span>;
	}

	if ( state === 'uploading' ) {
		return <span>{ __( 'Uploading…', 'woocommerce' ) }</span>;
	}

	return (
		<>
			<button onClick={ () => onRemoveClick() }>
				{ __( 'Remove background', 'woocommerce' ) }
			</button>
			<img src={ MagicIcon } alt="" />
		</>
	);
};
