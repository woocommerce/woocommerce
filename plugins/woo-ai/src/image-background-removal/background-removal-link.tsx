/**
 * External dependencies
 */
import { __experimentalUseBackgroundRemoval as useBackgroundRemoval } from '@woocommerce/ai';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MagicIcon from '../../assets/images/icons/magic.svg';

declare const ajaxurl: string;

export const BackgroundRemovalLink = () => {
	const { fetchImage, loading, error } = useBackgroundRemoval();

	const onRemoveClick = async () => {
		const imgUrl = (
			document.querySelector(
				'.attachment-details-copy-link'
			) as HTMLInputElement | null
		 )?.value;
		const originalFilename =
			imgUrl?.split( '/' ).pop()?.split( '.' )[ 0 ] ?? '';

		if ( ! imgUrl ) {
			return;
		}

		const originalBlob = await fetch( imgUrl ).then( ( res ) =>
			res.blob()
		);

		const bgRemoved = await fetchImage( {
			returnedImageType: 'png',
			imageFile: new File( [ originalBlob ], originalFilename, {
				type: originalBlob.type,
			} ),
		} );

		let _fileId = 0;
		const fileObj = new File(
			[ bgRemoved ],
			`${ originalFilename }_bg_removed.png`
		);
		await wp.mediaUtils.uploadMedia( {
			allowedTypes: 'image',
			filesList: [ fileObj ],
			onError: ( e ) => console.log( e ),
			onFileChange: ( files ) => {
				if ( files.length > 0 && files[ 0 ] && files[ 0 ].id ) {
					_fileId = files[ 0 ].id;
				}
			},
		} );
		if ( _fileId !== 0 ) {
			const nonceValue =
				(
					document.querySelector(
						'#_wpnonce'
					) as HTMLInputElement | null
				 )?.value ?? '';
			const formData = new FormData();
			formData.append( 'action', 'get-attachment' );
			formData.append( 'id', _fileId );
			formData.append( '_ajax_nonce', nonceValue );

			await fetch( ajaxurl, {
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => res.json() )
				.then( ( response ) => {
					if ( ! response.data ) return;
					const file = response.data;

					const attributes = _.extend( file, {
						file: fileObj,
						uploading: false,
						date: new Date(),
						filename: fileObj.name,
						menuOrder: 0,
						type: 'image',
						subtype: 'jpeg',
						uploadedTo: wp.media.model.settings.post.id,
					} );

					file.attachment =
						wp.media.model.Attachment.create( attributes );

					wp.media.model.Attachment.get( file.id, file.attachment );

					wp.Uploader.queue.add( file.attachment );

					wp.Uploader.queue.reset();
				} );
		}
	};

	if ( error ) {
		return (
			<span>
				{ __( 'Error. Please try again later.', 'woocommerce' ) }
			</span>
		);
	}

	if ( loading ) {
		return <span>{ __( 'Generating…', 'woocommerce' ) }</span>;
	}

	return (
		<>
			<button onClick={ () => onRemoveClick() }>Remove background</button>
			<img src={ MagicIcon } alt="" />
		</>
	);
};
