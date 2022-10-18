/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, DropZone } from '@wordpress/components';
import { createElement } from 'react';
import {
	MediaItem,
	MediaUpload,
	uploadMedia as wpUploadMedia,
	UploadMediaOptions,
	UploadMediaErrorCode,
} from '@wordpress/media-utils';

const DEFAULT_ALLOWED_MEDIA_TYPES = [ 'image' ];

type MediaUploaderProps = {
	allowedMediaTypes?: string[];
	buttonText?: string;
	hasDropZone?: boolean;
	icon?: JSX.Element;
	label?: string | JSX.Element;
	maxUploadFileSize?: number;
	MediaUploadComponent?: < T extends boolean = false >(
		props: MediaUpload.Props< T >
	) => JSX.Element;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	onSelect?: ( value: { id: number } & { [ k: string ]: any } ) => void;
	onError?: ( error: {
		code: UploadMediaErrorCode;
		message: string;
		file: File;
	} ) => void;
	onUpload?: ( files: MediaItem[] ) => void;
	uploadMedia?: ( options: UploadMediaOptions ) => Promise< void >;
};

export const MediaUploader = ( {
	allowedMediaTypes = DEFAULT_ALLOWED_MEDIA_TYPES,
	buttonText = __( 'Choose images', 'woocommerce' ),
	hasDropZone = true,
	label = __( 'Drag images here or click to upload', 'woocommerce' ),
	maxUploadFileSize = 10000000,
	MediaUploadComponent = MediaUpload,
	onError = () => null,
	onUpload = () => null,
	onSelect = () => null,
	uploadMedia = wpUploadMedia,
}: MediaUploaderProps ) => {
	return (
		<div className="woocommerce-media-uploader">
			<div className="woocommerce-media-uploader__label">{ label }</div>

			<MediaUploadComponent
				onSelect={ onSelect }
				allowedTypes={ allowedMediaTypes }
				render={ ( { open } ) => (
					<Button variant="secondary" onClick={ open }>
						{ buttonText }
					</Button>
				) }
			/>

			{ hasDropZone && (
				<DropZone
					onFilesDrop={ ( files ) =>
						uploadMedia( {
							filesList: files,
							onError,
							onFileChange: onUpload,
							maxUploadFileSize,
						} )
					}
				/>
			) }
		</div>
	);
};
