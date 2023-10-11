/**
 * External dependencies
 */

import { MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { media } from '@wordpress/icons';
import { MediaItem, MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { MediaLibraryMenuItemProps } from './types';

export function MediaLibraryMenuItem( {
	allowedTypes,
	onUploadSuccess,
}: MediaLibraryMenuItemProps ) {
	function handleMediaUploadSelect( value: unknown ) {
		onUploadSuccess( value as MediaItem[] );
	}

	return (
		<MediaUpload
			onSelect={ handleMediaUploadSelect }
			allowedTypes={ allowedTypes }
			// @ts-expect-error - TODO multiple also accepts string.
			multiple={ 'add' }
			render={ ( { open } ) => (
				<MenuItem
					icon={ media }
					iconPosition="left"
					onClick={ open }
					info={ __( 'Choose from uploaded media', 'woocommerce' ) }
				>
					{ __( 'Media Library', 'woocommerce' ) }
				</MenuItem>
			) }
		/>
	);
}
