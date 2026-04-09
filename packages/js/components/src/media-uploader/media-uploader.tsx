/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, DropZone, FormFileUpload } from '@wordpress/components';
import { Fragment, createElement } from 'react';
import {
	type Attachment,
	MediaUpload,
	uploadMedia as wpUploadMedia,
} from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import type { ErrorType, MediaUploadComponentType } from './types';

const DEFAULT_ALLOWED_MEDIA_TYPES = [ 'image' ];

export type MediaUploaderErrorCallback = ( error: ErrorType ) => void;

type MediaUploaderProps = {
	allowedMediaTypes?: string[];
	buttonText?: string;
	buttonProps?: React.ComponentProps< typeof Button >;
	hasDropZone?: boolean;
	icon?: JSX.Element;
	label?: string | JSX.Element;
	maxUploadFileSize?: number;
	MediaUploadComponent?: MediaUploadComponentType;
	multipleSelect?: boolean | string;
	value?: number | number[];
	onSelect?: (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		value: ( { id: number } & { [ k: string ]: any } ) | Attachment[]
	) => void;
	onError?: MediaUploaderErrorCallback;
	onMediaGalleryOpen?: () => void;
	onUpload?: ( files: Attachment | Attachment[] ) => void;
	onFileUploadChange?: ( files: Attachment | Attachment[] ) => void;
	uploadMedia?: typeof wpUploadMedia;
	additionalData?: Record< string, unknown >;
};

export const MediaUploader = ( {
	allowedMediaTypes = DEFAULT_ALLOWED_MEDIA_TYPES,
	buttonText = __( 'Choose images', 'woocommerce' ),
	buttonProps,
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
	additionalData,
}: MediaUploaderProps ) => {
	const multiple = Boolean( multipleSelect );

	return (
		<FormFileUpload
			accept={ allowedMediaTypes.toString() }
			multiple={ multiple }
			onChange={ ( { currentTarget } ) => {
				uploadMedia( {
					allowedTypes: allowedMediaTypes,
					filesList: Array.from( currentTarget.files ?? [] ),
					maxUploadFileSize,
					// Runtime passes UploadError (with code + file), not plain Error.
					onError: onError as unknown as ( error: Error ) => void,
					onFileChange( files ) {
						onFileUploadChange(
							multiple
								? ( files as Attachment[] )
								: ( files as Attachment[] )[ 0 ]
						);
					},
					additionalData,
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
						// is the click on the button from MediaUploadComponent or on the div?
						if ( ! ( target as HTMLElement ).closest( 'button' ) ) {
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
							multiple={ multipleSelect }
							render={ ( { open }: { open: () => void } ) =>
								buttonText || buttonProps ? (
									<Button
										variant="secondary"
										onClick={ () => {
											onMediaGalleryOpen();
											open();
										} }
										{ ...buttonProps }
									>
										{ buttonText }
									</Button>
								) : (
									<Fragment />
								)
							}
						/>

						{ hasDropZone && (
							<DropZone
								onFilesDrop={ ( droppedFiles ) =>
									uploadMedia( {
										allowedTypes: allowedMediaTypes,
										filesList: droppedFiles,
										maxUploadFileSize,
										// Runtime passes UploadError (with code + file), not plain Error.
										onError: onError as unknown as (
											error: Error
										) => void,
										onFileChange( files ) {
											onUpload(
												multiple
													? ( files as Attachment[] )
													: (
															files as Attachment[]
													   )[ 0 ]
											);
										},
										additionalData,
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
