/**
 * External dependencies
 */
import type { ChangeEvent } from 'react';
import { FormFileUpload, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { upload } from '@wordpress/icons';
import { uploadMedia } from '@wordpress/media-utils';
import type { Attachment } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import type { UploadFilesMenuItemProps } from './types';

export function UploadFilesMenuItem( {
	// UploadMediaOptions
	allowedTypes,
	maxUploadFileSize,
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
	const resolvedMaxUploadFileSize =
		maxUploadFileSize ||
		window.productBlockEditorSettings?.maxUploadFileSize ||
		10 * 1024 * 1024; // 10 MB by default if not set and not provided by the settings

	function handleFormFileUploadChange(
		event: ChangeEvent< HTMLInputElement >
	) {
		const filesList = Array.from( event.currentTarget.files ?? [] );

		uploadMedia( {
			allowedTypes,
			filesList,
			maxUploadFileSize: resolvedMaxUploadFileSize,
			additionalData,
			wpAllowedMimeTypes: wpAllowedMimeTypes ?? undefined,
			onFileChange( files ) {
				const isUploading = files.some( ( file ) => ! file.id );

				if ( isUploading ) {
					onUploadProgress?.( files as Attachment[] );
					return;
				}

				onUploadSuccess( files as Attachment[] );
			},
			// Native OnErrorHandler expects (error: Error) => void, but our callback uses a richer type.
			onError: onUploadError as unknown as ( error: Error ) => void,
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
