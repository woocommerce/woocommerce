/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { FormFileUpload, MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { upload } from '@wordpress/icons';
import { uploadMedia } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { UploadFilesMenuItemProps } from './types';

export function UploadFilesMenuItem( {
	allowedTypes,
	maxUploadFileSize,
	onUploadSuccess,
	onUploadError,
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
			// onFileChange expects Partial<Attachment>[] but our callback uses Attachment[].
			onFileChange: onUploadSuccess as unknown as (
				attachments: Partial<
					import('@wordpress/media-utils').Attachment
				>[]
			) => void,
			// Native OnErrorHandler expects (error: Error) => void, but our callback uses a richer type.
			onError: onUploadError as unknown as ( error: Error ) => void,
		} );
	}

	return (
		<FormFileUpload
			multiple
			onChange={ handleFormFileUploadChange }
			render={ ( { openFileDialog } ) => (
				<MenuItem
					icon={ upload }
					iconPosition="left"
					onClick={ openFileDialog }
					info={ __(
						'Select files from your device',
						'woocommerce'
					) }
				>
					{ __( 'Upload', 'woocommerce' ) }
				</MenuItem>
			) }
		/>
	);
}
