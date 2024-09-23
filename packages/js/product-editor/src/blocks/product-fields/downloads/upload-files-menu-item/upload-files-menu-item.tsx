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
		const filesList = event.currentTarget.files as FileList;

		uploadMedia( {
			allowedTypes,
			filesList,
			maxUploadFileSize: resolvedMaxUploadFileSize,
			onFileChange: onUploadSuccess,
			onError: onUploadError,
			additionalData: {
				type: 'downloadable_product',
			},
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
