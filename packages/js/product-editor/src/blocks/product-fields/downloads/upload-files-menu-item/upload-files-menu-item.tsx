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
	maxUploadFileSize = 10000000,
	onUploadSuccess,
	onUploadError,
}: UploadFilesMenuItemProps ) {
	function handleFormFileUploadChange(
		event: ChangeEvent< HTMLInputElement >
	) {
		const filesList = event.currentTarget.files as FileList;

		uploadMedia( {
			allowedTypes,
			filesList,
			maxUploadFileSize,
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
