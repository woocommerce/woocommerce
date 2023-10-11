/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, DropZone, FormFileUpload } from '@wordpress/components';
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
	multipleSelect?: boolean | string;
	value?: number | number[];
	onSelect?: (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		value: ( { id: number } & { [ k: string ]: any } ) | MediaItem[]
	) => void;
	onError?: ( error: {
		code: UploadMediaErrorCode;
		message: string;
		file: File;
	} ) => void;
	onMediaGalleryOpen?: () => void;
	onUpload?: ( files: MediaItem | MediaItem[] ) => void;
	onFileUploadChange?: ( files: MediaItem | MediaItem[] ) => void;
	uploadMedia?: ( options: UploadMediaOptions ) => Promise< void >;
};

export const MediaUploader = ( {
	allowedMediaTypes = DEFAULT_ALLOWED_MEDIA_TYPES,
	buttonText = __( 'Choose images', 'woocommerce' ),
	hasDropZone = true,
	label = __( 'Drag images here or click to upload', 'woocommerce' ),
	maxUploadFileSize = 10000000,
	MediaUploadComponent = MediaUpload,
	multipleSelect = false,
	value,
	onError = () => null,
	onFileUploadChange = () => null,
	onMediaGalleryOpen = () => null,
	onUpload = () => null,
	onSelect = () => null,
	uploadMedia = wpUploadMedia,
}: MediaUploaderProps ) => {
	const getFormFileUploadAcceptedFiles = () =>
		allowedMediaTypes.map( ( type ) => `${ type }/*` );

	const multiple = Boolean( multipleSelect );

	return (
		<FormFileUpload
			accept={ getFormFileUploadAcceptedFiles().toString() }
			multiple={ multiple }
			onChange={ ( { currentTarget } ) => {
				uploadMedia( {
					allowedTypes: allowedMediaTypes,
					filesList: currentTarget.files as FileList,
					maxUploadFileSize,
					onError,
					onFileChange( files ) {
						onFileUploadChange( multiple ? files : files[ 0 ] );
					},
				} );
			} }
			render={ ( { openFileDialog } ) => (
				<div
					className="woocommerce-form-file-upload"
					onKeyPress={ () => {} }
					tabIndex={ 0 }
					role="button"
					onClick={ (
						event: React.MouseEvent< HTMLDivElement, MouseEvent >
					) => {
						const { target } = event;
						if (
							( target as HTMLButtonElement )?.type !== 'button'
						) {
							openFileDialog();
						}
					} }
					onBlur={ () => {} }
				>
					<div className="woocommerce-media-uploader">
						<div className="woocommerce-media-uploader__label">
							{ label }
						</div>

						<MediaUploadComponent
							value={ value }
							onSelect={ onSelect }
							allowedTypes={ allowedMediaTypes }
							// @ts-expect-error - TODO multiple also accepts string.
							multiple={ multipleSelect }
							render={ ( { open } ) => (
								<Button
									variant="secondary"
									onClick={ () => {
										onMediaGalleryOpen();
										open();
									} }
								>
									{ buttonText }
								</Button>
							) }
						/>

						{ hasDropZone && (
							<DropZone
								onFilesDrop={ ( droppedFiles ) =>
									uploadMedia( {
										allowedTypes: allowedMediaTypes,
										filesList: droppedFiles,
										maxUploadFileSize,
										onError,
										onFileChange( files ) {
											onUpload(
												multiple ? files : files[ 0 ]
											);
										},
									} )
								}
							/>
						) }
					</div>
				</div>
			) }
		/>
	);
};
