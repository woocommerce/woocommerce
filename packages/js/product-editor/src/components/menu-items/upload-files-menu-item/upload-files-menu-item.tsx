/**
 * External dependencies
 */
import type { ChangeEvent } from 'react';
import { FormFileUpload, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { upload } from '@wordpress/icons';
import { uploadMedia } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import type { UploadFilesMenuItemProps } from './types';

export function UploadFilesMenuItem( {
	// UploadMediaOptions
	allowedTypes,
	maxUploadFileSize = 10000000,
	wpAllowedMimeTypes,
	additionalData,
	// MenuItem.Props
	icon,
	iconPosition,
	text,
	info,
	// Handlers
	onUploadProgress,
	onUploadSuccess,
	onUploadError,
	// FormFileUpload.Props
	...props
}: UploadFilesMenuItemProps ) {
	function handleFormFileUploadChange(
		event: ChangeEvent< HTMLInputElement >
	) {
		const filesList = event.currentTarget.files as FileList;

		uploadMedia( {
			allowedTypes,
			filesList,
			maxUploadFileSize,
			additionalData,
			wpAllowedMimeTypes,
			onFileChange( files ) {
				const isUploading = files.some( ( file ) => ! file.id );

				if ( isUploading ) {
					onUploadProgress?.( files );
					return;
				}

				onUploadSuccess( files );
			},
			onError: onUploadError,
		} );
	}

	return (
		<FormFileUpload
			{ ...props }
			onChange={ handleFormFileUploadChange }
			render={ ( { openFileDialog } ) => (
				<MenuItem
					icon={ icon ?? upload }
					iconPosition={ iconPosition ?? 'left' }
					onClick={ openFileDialog }
					info={
						info ??
						__( 'Select files from your device', 'woocommerce' )
					}
				>
					{ text ?? __( 'Upload', 'woocommerce' ) }
				</MenuItem>
			) }
		/>
	);
}
